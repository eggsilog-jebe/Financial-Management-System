<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\DTOs\Accounting\CashierShiftCloseData;
use App\DTOs\Accounting\CashierShiftOpenData;
use App\DTOs\Accounting\PosCollectionData;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalLineData;
use App\Models\Account;
use App\Models\CashierShift;
use App\Models\Invoice;
use App\Models\OfficialReceipt;
use App\Models\Payment;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CashierPaymentService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
        private readonly CasAuditTrailService $auditTrailService,
    ) {}

    /**
     * Open a new cashier shift with an opening cash float.
     */
    public function openShift(CashierShiftOpenData $dto): CashierShift
    {
        return DB::transaction(function () use ($dto): CashierShift {
            $existingOpen = CashierShift::where('cashier_id', $dto->cashierId)
                ->where('status', 'OPEN')
                ->first();

            if ($existingOpen) {
                throw new DomainException("Cashier already has an active open shift [{$existingOpen->shift_code}].");
            }

            $countToday = CashierShift::whereDate('opened_at', today())->count() + 1;
            $shiftCode = 'SHIFT-' . date('Ymd') . '-' . str_pad((string) $countToday, 3, '0', STR_PAD_LEFT);

            $shift = CashierShift::create([
                'shift_code'         => $shiftCode,
                'cashier_id'         => $dto->cashierId,
                'terminal_name'      => $dto->terminalName,
                'opened_at'          => now(),
                'opening_cash_float' => $dto->openingCashFloat,
                'expected_cash'      => $dto->openingCashFloat,
                'actual_cash_counted'=> '0.0000',
                'cash_variance'      => '0.0000',
                'total_digital_collections' => '0.0000',
                'total_collections'  => '0.0000',
                'status'             => 'OPEN',
            ]);

            $this->auditTrailService->logFinancialEvent(
                auditable: $shift,
                action: 'INSERT',
                oldValues: null,
                newValues: $shift->toArray(),
                userId: $dto->cashierId,
                userName: auth()->user()?->name ?? 'Cashier Officer',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $shift;
        });
    }

    /**
     * Settle POS payment against an invoice, issue Official Receipt, update shift totals,
     * and trigger balanced double-entry GL journal entry (DR 1011 / CR 1120).
     */
    public function collectPayment(PosCollectionData $dto): Payment
    {
        return DB::transaction(function () use ($dto): Payment {
            $invoice = Invoice::where('id', $dto->invoiceId)->lockForUpdate()->firstOrFail();
            $invoice->loadMissing('patientAccount');
            $patientId = $dto->patientAccountId ?: $invoice->patient_account_id;
            $paymentDate = $dto->paymentDate ?: date('Y-m-d');

            $amount = (string) $dto->amount;
            $openBalance = $invoice->balance_due;

            if (bccomp($amount, '0.0000', 4) <= 0) {
                throw new DomainException("Collection amount must be greater than zero.");
            }

            // Handle centavo rounding tolerance (e.g. physical cash/tender rounded to 2 decimals exceeding 4-decimal balance by <= ₱0.01)
            $balanceDiff = bcsub($amount, $openBalance, 4);
            if (bccomp($balanceDiff, '0.0000', 4) > 0 && bccomp($balanceDiff, '0.0100', 4) <= 0) {
                $amount = $openBalance;
            } elseif (bccomp($amount, $openBalance, 4) > 0) {
                throw new DomainException("Payment amount (₱" . number_format((float) $amount, 2) . ") exceeds open invoice balance (₱" . number_format((float) $openBalance, 2) . ").");
            }

            // Calculate change if cash tendered
            $tendered = $dto->tenderedAmount ?? $amount;
            $changeAmount = '0.0000';
            if (bccomp((string) $tendered, (string) $amount, 4) > 0) {
                $changeAmount = bcsub((string) $tendered, (string) $amount, 4);
            }

            // 1. Generate References
            $paymentCount = Payment::count() + 1;
            $paymentRef = 'PAY-' . date('Ymd') . '-' . str_pad((string) $paymentCount, 5, '0', STR_PAD_LEFT);
            $orCount = OfficialReceipt::count() + 1;
            $orNumber = 'OR-' . date('Y') . '-' . str_pad((string) $orCount, 6, '0', STR_PAD_LEFT);

            // Determine shift
            $shiftId = $dto->cashierShiftId;
            if (! $shiftId && auth()->check()) {
                $activeShift = CashierShift::where('cashier_id', auth()->id())
                    ->where('status', 'OPEN')
                    ->latest('opened_at')
                    ->first();
                $shiftId = $activeShift?->id;
            }

            // 2. Create Payment Record
            $isSplit = ($dto->paymentMethod === 'SPLIT_PAYMENT');
            $splitCash = $isSplit ? (string) ($dto->splitCashAmount ?? '0.0000') : ($dto->paymentMethod === 'CASH' ? (string) $amount : '0.0000');
            $splitDigital = $isSplit ? (string) ($dto->splitDigitalAmount ?? '0.0000') : ($dto->paymentMethod !== 'CASH' ? (string) $amount : '0.0000');
            $splitDigitalChannel = $dto->splitDigitalChannel ?? 'DIGITAL';
            $splitDigitalRef = $dto->splitDigitalRef ?? $dto->gatewayTransactionId;

            $chanRef = $dto->gatewayTransactionId ?: ($dto->gatewayProvider ?: $paymentRef);
            if ($isSplit) {
                $chanRef = "Split: Cash ₱" . number_format((float) $splitCash, 2) . " + " . $splitDigitalChannel . " ₱" . number_format((float) $splitDigital, 2) . ($splitDigitalRef ? " (Ref: {$splitDigitalRef})" : "");
            }

            $payment = Payment::create([
                'payment_reference'       => $paymentRef,
                'invoice_id'              => $invoice->id,
                'patient_account_id'      => $patientId,
                'cashier_shift_id'        => $shiftId,
                'payment_date'            => $paymentDate,
                'amount'                  => $amount,
                'payment_method'          => $dto->paymentMethod,
                'transaction_channel_ref' => $chanRef,
                'payment_type'            => 'PATIENT_COPAY',
            ]);

            // 3. Create Official Receipt (BIR Compliant)
            $payor = $dto->payorName ?: ($invoice->patientAccount?->full_name ?? 'Hospital Patient');
            $or = OfficialReceipt::create([
                'or_number'              => $orNumber,
                'payment_id'             => $payment->id,
                'invoice_id'             => $invoice->id,
                'patient_account_id'     => $patientId,
                'or_date'                => $paymentDate,
                'payor_name'             => $payor,
                'payor_tin'              => $dto->payorTin,
                'vatable_sales'          => '0.0000',
                'vat_exempt_sales'       => $amount,
                'zero_rated_sales'       => '0.0000',
                'vat_amount'             => '0.0000',
                'total_amount_collected' => $amount,
                'status'                 => 'VALID',
            ]);

            // 4. Update Invoice & Patient Balance (Preserve immutable patient_payable obligation)
            $newPaid = bcadd((string) $invoice->paid_amount, (string) $amount, 4);
            $isFullyPaid = bccomp($newPaid, (string) $invoice->patient_payable, 4) >= 0;
            
            $invoice->update([
                'paid_amount' => $newPaid,
                'status'      => $isFullyPaid ? 'SETTLED' : 'PARTIAL',
            ]);

            if ($invoice->patientAccount) {
                $patBal = (string) $invoice->patientAccount->current_balance;
                $newPatBal = bcsub($patBal, (string) $amount, 4);
                $invoice->patientAccount->update([
                    'current_balance' => bccomp($newPatBal, '0.0000', 4) < 0 ? '0.0000' : $newPatBal,
                ]);
            }

            // 5. Update Cashier Shift Totals
            if ($shiftId) {
                $shift = CashierShift::find($shiftId);
                if ($shift && $shift->status === 'OPEN') {
                    if (bccomp($splitCash, '0.0000', 4) > 0) {
                        $shift->expected_cash = bcadd((string) $shift->expected_cash, $splitCash, 4);
                    }
                    if (bccomp($splitDigital, '0.0000', 4) > 0) {
                        $shift->total_digital_collections = bcadd((string) $shift->total_digital_collections, $splitDigital, 4);
                    }
                    $shift->total_collections = bcadd((string) $shift->total_collections, (string) $amount, 4);
                    $shift->save();
                }
            }

            // 6. Balanced GL Journal Entry:
            // DR 1011 (Cashier Undeposited Cash)
            // DR 1002 (Digital Collections & POS Clearing)
            // CR 1120 (Accounts Receivable - Patients)
            $undepositedCashAcc = Account::firstOrCreate(
                ['code' => '1011'],
                ['name' => 'Cashier Undeposited Collections', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $digitalClearingAcc = Account::firstOrCreate(
                ['code' => '1002'],
                ['name' => 'Digital Collections & POS Clearing', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $patientArAcc = Account::firstOrCreate(
                ['code' => '1110'],
                ['name' => 'Accounts Receivable - Patient Copay', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $lines = [];

            if (bccomp($splitCash, '0.0000', 4) > 0) {
                $lines[] = new JournalLineData(
                    accountId: $undepositedCashAcc->id,
                    debit: $splitCash,
                    credit: '0.0000',
                    memo: "Cash collection portion [{$orNumber}]"
                );
            }

            if (bccomp($splitDigital, '0.0000', 4) > 0) {
                $lines[] = new JournalLineData(
                    accountId: $digitalClearingAcc->id,
                    debit: $splitDigital,
                    credit: '0.0000',
                    memo: "{$splitDigitalChannel} collection portion" . ($splitDigitalRef ? " (Ref: {$splitDigitalRef})" : "") . " [{$orNumber}]"
                );
            }

            $lines[] = new JournalLineData(
                accountId: $patientArAcc->id,
                debit: '0.0000',
                credit: (string) $amount,
                memo: "Patient AR settlement for Invoice #{$invoice->invoice_number}"
            );

            $this->journalEntryService->createAndPostEntry(new JournalEntryData(
                referenceNumber: 'JE-COL-' . $paymentRef,
                entryDate: $paymentDate,
                description: "Cashier Collection [{$orNumber}] for Invoice #{$invoice->invoice_number}",
                lines: $lines,
                type: 'GENERAL'
            ));

            $this->auditTrailService->logFinancialEvent(
                auditable: $payment,
                action: 'INSERT',
                oldValues: null,
                newValues: $payment->toArray(),
                userId: auth()->id() ?? 1,
                userName: auth()->user()?->name ?? 'Cashier Desk',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $payment->loadMissing(['invoice', 'patientAccount', 'officialReceipt', 'cashierShift']);
        });
    }

    /**
     * Close shift and calculate cash variance.
     */
    public function closeShift(int $shiftId, CashierShiftCloseData $dto): CashierShift
    {
        return DB::transaction(function () use ($shiftId, $dto): CashierShift {
            $shift = CashierShift::findOrFail($shiftId);

            if ($shift->status !== 'OPEN') {
                throw new DomainException("Shift [{$shift->shift_code}] is already {$shift->status}.");
            }

            $oldValues = $shift->toArray();
            $expectedCash = (string) $shift->expected_cash;
            $counted = (string) $dto->actualCashCounted;
            $variance = bcsub($counted, $expectedCash, 4); // positive = overage, negative = shortage

            $notes = $shift->notes;
            if ($dto->varianceReason) {
                $notes = ($notes ? $notes . ' | ' : '') . 'Variance Reason: ' . $dto->varianceReason;
            }

            $shift->update([
                'closed_at'           => now(),
                'actual_cash_counted' => $counted,
                'cash_variance'       => $variance,
                'status'              => 'CLOSED',
                'notes'               => $notes,
            ]);

            $this->auditTrailService->logFinancialEvent(
                auditable: $shift,
                action: 'UPDATE',
                oldValues: $oldValues,
                newValues: $shift->toArray(),
                userId: auth()->id() ?? 1,
                userName: auth()->user()?->name ?? 'Cashier Officer',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $shift;
        });
    }

    /**
     * Supervisor Reconciliation for a closed shift.
     */
    public function reconcileShift(int $shiftId, int $supervisorId): CashierShift
    {
        return DB::transaction(function () use ($shiftId, $supervisorId): CashierShift {
            $shift = CashierShift::findOrFail($shiftId);

            if ($shift->cashier_id === $supervisorId) {
                throw new DomainException("Segregation of Duties violation: Cashiers cannot reconcile or audit their own shift.");
            }

            if ($shift->status !== 'CLOSED') {
                throw new DomainException("Only CLOSED shifts can be reconciled.");
            }

            $oldValues = $shift->toArray();
            $shift->update([
                'status' => 'RECONCILED',
                'notes'  => ($shift->notes ? $shift->notes . ' | ' : '') . 'Reconciled by User ID #' . $supervisorId . ' at ' . now()->toDateTimeString(),
            ]);

            $this->auditTrailService->logFinancialEvent(
                auditable: $shift,
                action: 'UPDATE',
                oldValues: $oldValues,
                newValues: $shift->toArray(),
                userId: $supervisorId,
                userName: auth()->user()?->name ?? 'Treasury Supervisor',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $shift;
        });
    }

    /**
     * Void payment receipt, reverse invoice balance, and generate balanced reversing GL entry.
     */
    public function voidPayment(int $paymentId, string $reason, int $authorizedUserId): Payment
    {
        return DB::transaction(function () use ($paymentId, $reason, $authorizedUserId): Payment {
            $payment = Payment::with(['invoice', 'patientAccount', 'officialReceipt', 'cashierShift'])->findOrFail($paymentId);

            if ($payment->officialReceipt && $payment->officialReceipt->status === 'CANCELLED') {
                throw new DomainException("Payment #{$payment->payment_reference} is already voided.");
            }

            $oldValues = $payment->toArray();
            $amount = (string) $payment->amount;

            // 1. Mark Official Receipt as CANCELLED
            if ($payment->officialReceipt) {
                $payment->officialReceipt->update([
                    'status' => 'CANCELLED',
                ]);
            }

            // 2. Restore Invoice Paid Amount & Status (Preserve immutable patient_payable obligation)
            if ($payment->invoice) {
                $curPaid = (string) $payment->invoice->paid_amount;
                $restoredPaid = bcsub($curPaid, $amount, 4);
                if (bccomp($restoredPaid, '0.0000', 4) < 0) {
                    $restoredPaid = '0.0000';
                }
                $newStatus = bccomp($restoredPaid, '0.0000', 4) <= 0 ? 'ISSUED' : 'PARTIAL';
                $payment->invoice->update([
                    'paid_amount' => $restoredPaid,
                    'status'      => $newStatus,
                ]);
            }

            if ($payment->patientAccount) {
                $curPat = (string) $payment->patientAccount->current_balance;
                $payment->patientAccount->update([
                    'current_balance' => bcadd($curPat, $amount, 4),
                ]);
            }

            // 3. Reversal Double-Entry Lines:
            // Reverse the exact Asset accounts (1011 for Cash, 1002 for Digital / Cards)
            $undepositedCashAcc = Account::firstOrCreate(
                ['code' => '1011'],
                ['name' => 'Cashier Undeposited Collections', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $digitalClearingAcc = Account::firstOrCreate(
                ['code' => '1002'],
                ['name' => 'Digital Collections & POS Clearing', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $patientArAcc = Account::firstOrCreate(
                ['code' => '1110'],
                ['name' => 'Accounts Receivable - Patient Copay', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']
            );

            $orNumber = $payment->officialReceipt?->or_number ?? $payment->payment_reference;

            // Check original collection journal to mirror debit/credit lines exactly
            $originalJe = \App\Models\JournalEntry::where('reference_number', 'JE-COL-' . $payment->payment_reference)
                ->with('lines.account')
                ->first();

            $lines = [];
            $cashReversed = '0.0000';
            $digitalReversed = '0.0000';

            if ($originalJe && $originalJe->lines->isNotEmpty()) {
                // Mirror debit lines from original collection as credits in reversal
                foreach ($originalJe->lines as $origLine) {
                    if (bccomp((string) $origLine->debit, '0.0000', 4) > 0) {
                        $lines[] = new JournalLineData(
                            accountId: $origLine->account_id,
                            debit: '0.0000',
                            credit: (string) $origLine->debit,
                            memo: "Reversal of {$origLine->account->name} for [{$orNumber}]"
                        );

                        if ($origLine->account->code === '1011') {
                            $cashReversed = bcadd($cashReversed, (string) $origLine->debit, 4);
                        } else {
                            $digitalReversed = bcadd($digitalReversed, (string) $origLine->debit, 4);
                        }
                    }
                }

                // Debit Patient AR for total reversed
                $lines[] = new JournalLineData(
                    accountId: $patientArAcc->id,
                    debit: (string) $amount,
                    credit: '0.0000',
                    memo: "Reversal of collection [{$orNumber}]: {$reason}"
                );
            } else {
                // Fallback if no original JE exists
                $isCash = ($payment->payment_method === 'CASH');
                $lines = [
                    new JournalLineData(
                        accountId: $patientArAcc->id,
                        debit: (string) $amount,
                        credit: '0.0000',
                        memo: "Reversal of collection [{$orNumber}]: {$reason}"
                    ),
                    new JournalLineData(
                        accountId: $isCash ? $undepositedCashAcc->id : $digitalClearingAcc->id,
                        debit: '0.0000',
                        credit: (string) $amount,
                        memo: "Reversal of " . ($isCash ? 'undeposited cash' : 'digital clearing') . " for [{$orNumber}]"
                    ),
                ];

                if ($isCash) {
                    $cashReversed = $amount;
                } else {
                    $digitalReversed = $amount;
                }
            }

            $this->journalEntryService->createAndPostEntry(new JournalEntryData(
                referenceNumber: 'JE-REV-' . $payment->payment_reference,
                entryDate: date('Y-m-d'),
                description: "Void Payment Reversal [{$orNumber}] - Authorized by User #{$authorizedUserId}. Reason: {$reason}",
                lines: $lines,
                type: 'ADJUSTING'
            ));

            // 4. Adjust Cashier Shift Balances if shift is still OPEN
            if ($payment->cashierShift && $payment->cashierShift->status === 'OPEN') {
                $shift = $payment->cashierShift;
                if (bccomp($cashReversed, '0.0000', 4) > 0) {
                    $newExp = bcsub((string) $shift->expected_cash, $cashReversed, 4);
                    $shift->expected_cash = bccomp($newExp, '0.0000', 4) < 0 ? '0.0000' : $newExp;
                }
                if (bccomp($digitalReversed, '0.0000', 4) > 0) {
                    $newDig = bcsub((string) $shift->total_digital_collections, $digitalReversed, 4);
                    $shift->total_digital_collections = bccomp($newDig, '0.0000', 4) < 0 ? '0.0000' : $newDig;
                }
                $newTot = bcsub((string) $shift->total_collections, $amount, 4);
                $shift->total_collections = bccomp($newTot, '0.0000', 4) < 0 ? '0.0000' : $newTot;
                $shift->save();
            }

            $this->auditTrailService->logFinancialEvent(
                auditable: $payment,
                action: 'UPDATE',
                oldValues: $oldValues,
                newValues: $payment->toArray(),
                userId: $authorizedUserId,
                userName: auth()->user()?->name ?? 'Finance Approver',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $payment;
        });
    }
}
