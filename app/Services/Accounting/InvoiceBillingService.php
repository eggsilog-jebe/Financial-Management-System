<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\DTOs\Accounting\PatientInvoiceCreateData;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalLineData;
use App\Models\Account;
use App\Models\HmoClaim;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PatientAccount;
use App\Models\PhilhealthClaim;
use App\Models\StatutoryDiscount;
use DomainException;
use Illuminate\Support\Facades\DB;

final class InvoiceBillingService
{
    public function __construct(
        private readonly JournalEntryService $journalEntryService,
        private readonly CasAuditTrailService $auditTrailService,
    ) {}

    /**
     * Ingest and generate patient discharge billing statement with statutory splits and balanced GL journal entry.
     */
    public function createPatientInvoice(PatientInvoiceCreateData $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $patient = PatientAccount::findOrFail($data->patientAccountId);

            // 1. Calculate Gross Total across departmental line items
            $grossTotal = '0.0000';
            $vatReliefTotal = '0.0000';
            $discountTotal = '0.0000';

            $isSeniorOrPwd = in_array($data->discountType, ['SENIOR_CITIZEN', 'PWD'], true);
            $calculatedItems = [];

            foreach ($data->items as $item) {
                $qty = is_array($item) ? (string) ($item['quantity'] ?? '1') : (string) $item->quantity;
                $price = is_array($item) ? (string) ($item['unit_price'] ?? '0') : (string) $item->unitPrice;
                $itemGross = bcmul($qty, $price, 4);
                $grossTotal = bcadd($grossTotal, $itemGross, 4);

                $isVatable = is_array($item) ? (bool) ($item['is_vatable'] ?? true) : (bool) $item->isVatable;
                $isEligible = is_array($item) ? (bool) ($item['is_senior_pwd_eligible'] ?? true) : (bool) $item->isSeniorPwdEligible;

                if ($isSeniorOrPwd && $isEligible) {
                    // RA 9994 / RA 10754: 12% VAT exemption followed by 20% discount
                    if ($isVatable) {
                        $rawNet = bcdiv($itemGross, '1.1200', 4);
                        $netOfVat = number_format(round((float) $rawNet, 2), 4, '.', '');
                        $vatRelief = bcsub($itemGross, $netOfVat, 4);
                        $vatReliefTotal = bcadd($vatReliefTotal, $vatRelief, 4);
                        $rawDiscount = bcmul($netOfVat, '0.2000', 4);
                        $discount = number_format(round((float) $rawDiscount, 2), 4, '.', '');
                    } else {
                        $rawDiscount = bcmul($itemGross, '0.2000', 4);
                        $discount = number_format(round((float) $rawDiscount, 2), 4, '.', '');
                    }
                    $discountTotal = bcadd($discountTotal, $discount, 4);
                } elseif (in_array($data->discountType, ['EMPLOYEE', 'EMPLOYEE_SUBSIDY', 'CHARITY'], true) && $isEligible) {
                    $rawDiscount = bcmul($itemGross, '0.2000', 4);
                    $discount = number_format(round((float) $rawDiscount, 2), 4, '.', '');
                    $discountTotal = bcadd($discountTotal, $discount, 4);
                }

                $calculatedItems[] = [
                    'itemCode'        => is_array($item) ? ($item['item_code'] ?? 'ITEM') : $item->itemCode,
                    'description'     => is_array($item) ? ($item['description'] ?? 'Clinical Charge') : $item->description,
                    'department'      => is_array($item) ? ($item['department'] ?? 'CLINICAL') : ($item->department ?? 'CLINICAL'),
                    'revenueCategory' => is_array($item) ? ($item['revenue_category'] ?? 'CLINICAL') : ($item->revenueCategory ?? 'CLINICAL'),
                    'quantity'        => $qty,
                    'unitPrice'       => $price,
                    'gross'           => $itemGross,
                    'isVatable'       => $isVatable,
                    'isEligible'      => $isEligible,
                ];
            }

            // Total Statutory Senior/PWD Deduction
            $totalSeniorPwdDeduction = bcadd($vatReliefTotal, $discountTotal, 4);
            $amountAfterDiscount = bcsub($grossTotal, $totalSeniorPwdDeduction, 4);

            // 2. PhilHealth Case Rate Deductions
            $philhealthTotal = bcadd($data->philhealthPrimaryCaseRateAmount, $data->philhealthSecondaryCaseRateAmount, 4);
            $philhealthDeduction = bccomp($amountAfterDiscount, $philhealthTotal, 4) >= 0 ? $philhealthTotal : $amountAfterDiscount;
            $amountAfterPhilhealth = bcsub($amountAfterDiscount, $philhealthDeduction, 4);

            // 3. Private HMO Coverage Deduction
            $hmoProvider = $data->hmoProvider ?: $patient->hmo_provider;
            $hmoDeduction = '0.0000';
            if (! empty($hmoProvider) && bccomp($data->hmoApprovedLimit, '0.0000', 4) > 0) {
                $hmoDeduction = bccomp($amountAfterPhilhealth, $data->hmoApprovedLimit, 4) >= 0
                    ? $data->hmoApprovedLimit
                    : $amountAfterPhilhealth;
            }

            $rawPatientPayable = bcsub($amountAfterPhilhealth, $hmoDeduction, 4);
            $patientPayable = number_format(round((float) $rawPatientPayable, 2), 4, '.', '');
            $insuranceCovered = bcadd($philhealthDeduction, $hmoDeduction, 4);

            // 4. Create Master Invoice
            $invoiceNumber = $data->invoiceNumber ?? ('INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))));
            $dueDate = $data->dueDate ?? date('Y-m-d', strtotime($data->invoiceDate . ' +30 days'));

            $invoice = Invoice::create([
                'invoice_number'     => $invoiceNumber,
                'patient_account_id' => $patient->id,
                'invoice_date'       => $data->invoiceDate,
                'due_date'           => $dueDate,
                'total_amount'       => $grossTotal,
                'insurance_covered'  => $insuranceCovered,
                'discount_amount'    => $totalSeniorPwdDeduction,
                'vat_amount'         => $vatReliefTotal,
                'patient_payable'    => $patientPayable,
                'paid_amount'        => '0.0000',
                'status'             => bccomp($patientPayable, '0.0000', 4) === 0 ? 'SETTLED' : 'UNPAID',
            ]);

            // 5. Persist Invoice Line Items
            foreach ($calculatedItems as $c) {
                InvoiceItem::create([
                    'invoice_id'             => $invoice->id,
                    'item_code'              => $c['itemCode'],
                    'description'            => $c['description'],
                    'department'             => $c['department'],
                    'revenue_category'       => $c['revenueCategory'],
                    'quantity'               => $c['quantity'],
                    'unit_price'             => $c['unitPrice'],
                    'gross_amount'           => $c['gross'],
                    'is_vatable'             => $c['isVatable'],
                    'is_senior_pwd_eligible' => $c['isEligible'],
                ]);
            }

            // 6. Record Statutory Discount
            if (bccomp($totalSeniorPwdDeduction, '0.0000', 4) > 0) {
                StatutoryDiscount::create([
                    'invoice_id'        => $invoice->id,
                    'discount_type'     => $data->discountType ?? 'SENIOR_CITIZEN',
                    'id_card_number'    => $data->idCardNumber,
                    'vat_exempt_amount' => $vatReliefTotal,
                    'discount_rate'     => '0.2000',
                    'discount_amount'   => $totalSeniorPwdDeduction,
                ]);
            }

            // 7. Record PhilHealth Claim
            if (bccomp($philhealthDeduction, '0.0000', 4) > 0) {
                $hfShare = bcmul($philhealthDeduction, '0.6000', 4);
                $pfShare = bcsub($philhealthDeduction, $hfShare, 4);

                PhilhealthClaim::create([
                    'invoice_id'                  => $invoice->id,
                    'claim_series_number'         => 'PHIC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                    'member_pin'                  => $data->philhealthMemberPin,
                    'patient_pin'                 => $data->philhealthMemberPin,
                    'membership_type'             => 'EMPLOYED',
                    'primary_icd_code'            => $data->philhealthPrimaryIcd,
                    'primary_case_rate_code'      => $data->philhealthPrimaryCaseCode,
                    'primary_case_rate_amount'    => $data->philhealthPrimaryCaseRateAmount,
                    'secondary_case_rate_code'    => $data->philhealthSecondaryCaseCode,
                    'secondary_case_rate_amount'  => $data->philhealthSecondaryCaseRateAmount,
                    'total_case_rate_amount'      => $philhealthDeduction,
                    'hospital_fee_share'          => $hfShare,
                    'professional_fee_share'      => $pfShare,
                    'claim_status'                => 'TRANSMITTED',
                    'transmitted_at'              => $data->invoiceDate,
                ]);
            }

            // 8. Record HMO Claim
            if (bccomp($hmoDeduction, '0.0000', 4) > 0 && ! empty($hmoProvider)) {
                HmoClaim::create([
                    'invoice_id'      => $invoice->id,
                    'hmo_provider'    => $hmoProvider,
                    'loa_number'      => $data->hmoLoaNumber ?? ('LOA-' . strtoupper(substr(uniqid(), -6))),
                    'card_number'     => $data->hmoCardNumber,
                    'approved_limit'  => $data->hmoApprovedLimit,
                    'claimed_amount'  => $hmoDeduction,
                    'settled_amount'  => '0.0000',
                    'status'          => 'SUBMITTED',
                ]);
            }

            // 9. Update Patient Account Balances using BCMath (no float arithmetic)
            $patient->update([
                'total_billed'    => bcadd((string) $patient->total_billed, $grossTotal, 4),
                'current_balance' => bcadd((string) $patient->current_balance, $patientPayable, 4),
            ]);

            // 10. Post Double-Entry Journal to General Ledger
            $invoice->load('items');
            $this->postRevenueDoubleEntry($invoice, $data->invoiceDate, $hmoProvider, $grossTotal, $patientPayable, $philhealthDeduction, $hmoDeduction, $totalSeniorPwdDeduction);

            // CAS Audit Trail
            $this->auditTrailService->logFinancialEvent(
                auditable: $invoice,
                action: 'INSERT',
                oldValues: null,
                newValues: $invoice->toArray(),
                userId: auth()->id(),
                userName: auth()->user()?->name ?? 'Billing Officer',
                ipAddress: request()?->ip() ?? '127.0.0.1',
            );

            return $invoice->loadMissing(['items', 'philhealthClaim', 'hmoClaims', 'statutoryDiscounts', 'patientAccount']);
        });
    }

    private function postRevenueDoubleEntry(
        Invoice $invoice,
        string $invoiceDate,
        ?string $hmoProvider,
        string $grossTotal,
        string $patientPayable,
        string $philhealthAmount,
        string $hmoAmount,
        string $discountAmount
    ): void {
        $arPatientAccount    = Account::firstOrCreate(['code' => '1110'], ['name' => 'Accounts Receivable - Patient Copay', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']);
        $arPhilhealthAccount = Account::firstOrCreate(['code' => '1120'], ['name' => 'Accounts Receivable - PhilHealth Claims', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']);
        $arHmoAccount        = Account::firstOrCreate(['code' => '1130'], ['name' => 'Accounts Receivable - HMO Claims', 'category' => 'ASSET', 'normal_balance' => 'DEBIT']);
        $discountExpenseAcc  = Account::firstOrCreate(['code' => '4910'], ['name' => 'Statutory Discounts Allowed (Senior/PWD)', 'category' => 'EXPENSE', 'normal_balance' => 'DEBIT']);
        $hospitalRevenueAcc  = Account::firstOrCreate(['code' => '4010'], ['name' => 'Inpatient Hospital Care Revenue', 'category' => 'REVENUE', 'normal_balance' => 'CREDIT']);

        $journalLines = [];

        // Debit: Patient Out-of-Pocket AR
        if (bccomp($patientPayable, '0.0000', 4) > 0) {
            $journalLines[] = new JournalLineData(
                accountId: $arPatientAccount->id,
                debit: $patientPayable,
                credit: '0.0000',
                memo: 'Patient copay balance on ' . $invoice->invoice_number
            );
        }

        // Debit: PhilHealth AR
        if (bccomp($philhealthAmount, '0.0000', 4) > 0) {
            $journalLines[] = new JournalLineData(
                accountId: $arPhilhealthAccount->id,
                debit: $philhealthAmount,
                credit: '0.0000',
                memo: 'PhilHealth ACR Claim on ' . $invoice->invoice_number
            );
        }

        // Debit: HMO AR
        if (bccomp($hmoAmount, '0.0000', 4) > 0) {
            $journalLines[] = new JournalLineData(
                accountId: $arHmoAccount->id,
                debit: $hmoAmount,
                credit: '0.0000',
                memo: 'HMO Claim on ' . $invoice->invoice_number . ' (' . ($hmoProvider ?? 'HMO') . ')'
            );
        }

        // Debit: Senior/PWD Statutory Discount Expense
        if (bccomp($discountAmount, '0.0000', 4) > 0) {
            $journalLines[] = new JournalLineData(
                accountId: $discountExpenseAcc->id,
                debit: $discountAmount,
                credit: '0.0000',
                memo: 'Statutory 20% Senior/PWD & VAT relief on ' . $invoice->invoice_number
            );
        }

        // Credit: PhilHealth Doctor Professional Fee (PF) Share Liability (Account 2040)
        $doctorPfShare = '0.0000';
        if (bccomp($philhealthAmount, '0.0000', 4) > 0) {
            $doctorPfShare = bcmul($philhealthAmount, '0.4000', 4);
            $doctorPfAcc = Account::firstOrCreate(
                ['code' => '2040'],
                ['name' => 'Due to Accredited Physicians (Doctor PF Holdback)', 'category' => 'LIABILITY', 'normal_balance' => 'CREDIT']
            );

            $journalLines[] = new JournalLineData(
                accountId: $doctorPfAcc->id,
                debit: '0.0000',
                credit: $doctorPfShare,
                memo: "PhilHealth 40% Physician PF share on {$invoice->invoice_number}"
            );
        }

        $netHospitalRevenue = bcsub($grossTotal, $doctorPfShare, 4);

        // Credit: Departmental Hospital Revenue Accounts (4010, 4020, 4030, 4040, 4050, 4060)
        $deptMap = [
            'ROOM_AND_BOARD' => ['code' => '4010', 'name' => 'Inpatient Hospital Care Revenue'],
            'INPATIENT'      => ['code' => '4010', 'name' => 'Inpatient Hospital Care Revenue'],
            'CONSULTATION'   => ['code' => '4020', 'name' => 'Outpatient & Consultation Revenue'],
            'OUTPATIENT'     => ['code' => '4020', 'name' => 'Outpatient & Consultation Revenue'],
            'EMERGENCY'      => ['code' => '4020', 'name' => 'Outpatient & Consultation Revenue'],
            'EERTS'          => ['code' => '4020', 'name' => 'Outpatient & Consultation Revenue'],
            'TOCS'           => ['code' => '4020', 'name' => 'Outpatient & Consultation Revenue'],
            'LABORATORY'     => ['code' => '4030', 'name' => 'Laboratory Services Revenue'],
            'LIS'            => ['code' => '4030', 'name' => 'Laboratory Services Revenue'],
            'RADIOLOGY'      => ['code' => '4040', 'name' => 'Radiology & Imaging Revenue'],
            'RIS'            => ['code' => '4040', 'name' => 'Radiology & Imaging Revenue'],
            'PHARMACY'       => ['code' => '4050', 'name' => 'Pharmacy & Medical Supplies Revenue'],
            'PMS'            => ['code' => '4050', 'name' => 'Pharmacy & Medical Supplies Revenue'],
            'SURGERY'        => ['code' => '4060', 'name' => 'Operating Room & Surgical Revenue'],
            'MISCELLANEOUS'  => ['code' => '4010', 'name' => 'Inpatient Hospital Care Revenue'],
        ];

        $items = $invoice->relationLoaded('items') ? $invoice->items : $invoice->items()->get();
        if ($items->isNotEmpty()) {
            $deptTotals = [];
            foreach ($items as $item) {
                $deptKey = strtoupper((string) ($item->department ?: 'ROOM_AND_BOARD'));
                $mapping = $deptMap[$deptKey] ?? ['code' => '4010', 'name' => 'Hospital Inpatient & Clinical Revenue'];
                $code = (string) $mapping['code'];
                $name = (string) $mapping['name'];

                $itemSubtotal = (string) ($item->subtotal ?: bcmul((string) ($item->quantity ?? 1), (string) ($item->unit_price ?? 0), 4));

                $key = 'CODE_' . $code;
                if (! isset($deptTotals[$key])) {
                    $deptTotals[$key] = ['code' => $code, 'name' => $name, 'total' => '0.0000'];
                }
                $deptTotals[$key]['total'] = bcadd($deptTotals[$key]['total'], $itemSubtotal, 4);
            }

            $creditedRevenue = '0.0000';
            $deptKeys = array_keys($deptTotals);
            $lastKey = end($deptKeys);

            foreach ($deptTotals as $key => $info) {
                if (bccomp($info['total'], '0.0000', 4) <= 0) {
                    continue;
                }

                if ($key === $lastKey) {
                    $deptCredit = bcsub($netHospitalRevenue, $creditedRevenue, 4);
                } else {
                    $ratio = bcdiv($info['total'], $grossTotal, 6);
                    $deptCredit = bcmul($netHospitalRevenue, $ratio, 4);
                    $creditedRevenue = bcadd($creditedRevenue, $deptCredit, 4);
                }

                if (bccomp($deptCredit, '0.0000', 4) > 0) {
                    $deptRevenueAcc = Account::firstOrCreate(
                        ['code' => (string) $info['code']],
                        ['name' => (string) $info['name'], 'category' => 'REVENUE', 'normal_balance' => 'CREDIT']
                    );

                    $journalLines[] = new JournalLineData(
                        accountId: $deptRevenueAcc->id,
                        debit: '0.0000',
                        credit: $deptCredit,
                        memo: "{$info['name']} recognition on {$invoice->invoice_number}"
                    );
                }
            }
        } else {
            $journalLines[] = new JournalLineData(
                accountId: $hospitalRevenueAcc->id,
                debit: '0.0000',
                credit: $netHospitalRevenue,
                memo: 'Clinical gross revenue recognition on ' . $invoice->invoice_number
            );
        }

        $entryData = new JournalEntryData(
            referenceNumber: 'JE-REV-' . $invoice->invoice_number,
            entryDate: $invoiceDate,
            description: 'Revenue recognition & PhilHealth/HMO split for ' . $invoice->invoice_number,
            type: 'GENERAL',
            postedBy: auth()->id(),
            lines: $journalLines
        );

        $this->journalEntryService->createAndPostEntry($entryData);
    }

    /**
     * Helper method for CLI/encounter ingestion to create invoice, post GL journal entries, and return summary metrics array.
     */
    public function createAndPostEncounterInvoice(array $data): array
    {
        $dto = PatientInvoiceCreateData::fromArray([
            'patient_account_id'                  => $data['patient_account_id'],
            'invoice_date'                        => $data['invoice_date'] ?? date('Y-m-d'),
            'discount_type'                       => $data['statutory_discount'] ?? $data['discount_type'] ?? null,
            'id_card_number'                      => $data['osca_pwd_id'] ?? $data['id_card_number'] ?? null,
            'philhealth_primary_case_rate_amount' => $data['philhealth_amount'] ?? $data['philhealth_primary_case_rate_amount'] ?? '0.0000',
            'hmo_provider'                        => $data['hmo_provider'] ?? null,
            'hmo_approved_limit'                  => $data['hmo_amount'] ?? $data['hmo_approved_limit'] ?? '0.0000',
            'items'                               => $data['items'] ?? [],
        ]);

        $invoice = $this->createPatientInvoice($dto);

        $philhealthClaim = $invoice->philhealthClaim;
        $hmoClaim = $invoice->hmoClaims->first();

        return [
            'patient_mrn'             => $invoice->patientAccount->patient_id_number,
            'invoice_number'          => $invoice->invoice_number,
            'gross_total'             => (float) $invoice->total_amount,
            'discount_total'          => (float) $invoice->discount_amount,
            'philhealth_total'        => (float) ($philhealthClaim?->total_case_rate_amount ?? 0),
            'hmo_total'               => (float) ($hmoClaim?->claimed_amount ?? 0),
            'patient_copay'           => (float) $invoice->patient_payable,
            'journal_entry_reference' => 'JE-REV-' . $invoice->invoice_number,
            'invoice'                 => $invoice,
        ];
    }
}
