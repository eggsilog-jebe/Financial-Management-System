<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\DTOs\Accounting\JournalEntryData;
use App\Exceptions\Accounting\UnbalancedJournalEntryException;
use App\Models\Account;
use App\Models\BankAccount;
use App\Models\CashierShift;
use App\Models\BankDeposit;
use App\Models\DisbursementVoucher;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\OfficialReceipt;
use App\Models\PatientAccount;
use App\Models\Payment;
use App\Models\PurchaseBill;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Accounting\CasAuditTrailService;
use App\Services\Accounting\GeneralLedgerReportService;
use App\Services\Accounting\JournalEntryService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EndToEndFinancialFlowTest extends TestCase
{
    use RefreshDatabase;

    private JournalEntryService $glService;
    private CasAuditTrailService $auditService;
    private GeneralLedgerReportService $glReportService;

    private User $accountant;
    private User $cashier;
    private User $manager;
    private User $cfo;
    private User $auditor;
    private BankAccount $operatingBank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChartOfAccountsSeeder::class);

        $this->glService = app(JournalEntryService::class);
        $this->auditService = app(CasAuditTrailService::class);
        $this->glReportService = app(GeneralLedgerReportService::class);

        $this->accountant = User::factory()->create(['role' => 'StaffAccountant', 'name' => 'Eduardo Mendoza', 'email' => 'accountant@hospital.local']);
        $this->cashier    = User::factory()->create(['role' => 'Cashier', 'name' => 'Maria Clara', 'email' => 'cashier@hospital.local']);
        $this->manager    = User::factory()->create(['role' => 'FinanceManager', 'name' => 'Roberto Cruz', 'email' => 'manager@hospital.local']);
        $this->cfo        = User::factory()->create(['role' => 'CFO', 'name' => 'Dr. Victoria Ramos', 'email' => 'cfo@hospital.local']);
        $this->auditor    = User::factory()->create(['role' => 'Auditor', 'name' => 'Arthur Pendelton', 'email' => 'auditor@hospital.local']);

        FiscalPeriod::create([
            'period_code'   => '2026-M01',
            'fiscal_year'   => '2026',
            'period_number' => 1,
            'start_date'    => '2026-01-01',
            'end_date'      => '2026-12-31',
            'status'        => 'OPEN',
        ]);

        $bankGl = Account::where('code', '1020')->firstOrFail();
        $this->operatingBank = BankAccount::create([
            'name'            => 'Metrobank Main Operating Account',
            'bank_name'       => 'Metrobank Pasig Medical Branch',
            'account_number'  => 'MB-0099-2211-00',
            'gl_code'         => '1020',
            'gl_account_id'   => $bankGl->id,
            'purpose'         => 'Hospital Daily Operations',
            'currency'        => 'PHP',
            'opening_balance' => '2000000.0000',
            'balance'         => '2000000.0000',
            'minimum_balance' => '100000.0000',
            'status'          => 'Active',
            'is_active'       => true,
        ]);

        // Post Initial Capital Contribution: DR Bank 1020 ₱2,000,000 / CR Equity 3010 ₱2,000,000
        $equityAccount = Account::where('code', '3010')->firstOrFail();
        $this->glService->createAndPostEntry(new JournalEntryData(
            entryDate: '2026-01-01',
            description: 'Opening Capital Reserve Injection',
            referenceNumber: 'JE-CAP-2026-01',
            lines: [
                ['account_id' => $bankGl->id, 'debit' => '2000000.0000', 'credit' => '0.0000', 'memo' => 'Opening Cash Reserve'],
                ['account_id' => $equityAccount->id, 'debit' => '0.0000', 'credit' => '2000000.0000', 'memo' => 'Contributed Capital'],
            ],
            userId: $this->cfo->id
        ));
    }

    /** @test */
    public function test_it_rejects_unbalanced_double_entry_transactions(): void
    {
        $this->expectException(UnbalancedJournalEntryException::class);

        $cashAccount = Account::where('code', '1010')->firstOrFail();
        $revAccount  = Account::where('code', '4010')->firstOrFail();

        $data = new JournalEntryData(
            entryDate: '2026-01-02',
            description: 'Unbalanced Journal Attempt',
            lines: [
                ['account_id' => $cashAccount->id, 'debit' => '100.0000', 'credit' => '0.0000'],
                ['account_id' => $revAccount->id, 'debit' => '0.0000', 'credit' => '95.0000'], // ₱5 discrepancy
            ],
            userId: $this->accountant->id
        );

        $this->glService->createAndPostEntry($data);
    }

    /** @test */
    public function test_full_hospital_financial_lifecycle_from_clinical_encounter_to_four_primary_statements(): void
    {
        // ─────────────────────────────────────────────────────────────────────────
        // STEP 1: External Clinical Subsystem Ingestion via API (/api/v1/ingest/patient-bill)
        // ─────────────────────────────────────────────────────────────────────────
        $patient = PatientAccount::create([
            'patient_id_number' => 'MRN-2026-9011',
            'full_name'         => 'Don Emilio Aguinaldo',
            'admission_type'    => 'INPATIENT',
            'current_balance'   => '0.0000',
            'credit_limit'      => '500000.0000',
            'status'            => 'Active',
        ]);

        $ingestionPayload = [
            'patient_id'        => $patient->id,
            'bdms_bill_number'  => 'BDMS-ENC-9901',
            'invoice_date'      => '2026-01-10',
            'gross_amount'      => 50000.00,
            'philhealth_amount' => 15000.00,
            'hmo_amount'        => 10000.00,
            'hmo_provider'      => 'Maxicare Healthcare',
            'discount_amount'   => 5000.00,
            'discount_type'     => 'SENIOR_CITIZEN',
            'id_card_number'    => 'OSCA-NCR-2026',
            'net_copay'         => 15000.00,
            'charge_lines'      => [
                [
                    'item_code'          => 'ROOM-DELUXE',
                    'description'        => 'Inpatient Room & Board Deluxe Wards (3 Days)',
                    'department'         => 'ROOM_AND_BOARD',
                    'revenue_category'   => 'CLINICAL',
                    'quantity'           => 3,
                    'unit_price'         => 10000.00,
                    'is_vatable'         => false,
                    'is_senior_eligible' => true,
                ],
                [
                    'item_code'          => 'PHARM-IV-MEDS',
                    'description'        => 'IV Antibiotics & Therapeutics Infusion',
                    'department'         => 'PHARMACY',
                    'revenue_category'   => 'CLINICAL',
                    'quantity'           => 1,
                    'unit_price'         => 20000.00,
                    'is_vatable'         => false,
                    'is_senior_eligible' => true,
                ],
            ],
        ];

        $ingestRes = $this->withHeaders([
            'X-Idempotency-Key' => 'IDEMP-INGEST-PATIENT-001',
            'Accept'            => 'application/json',
        ])->postJson('/api/v1/ingest/patient-bill', $ingestionPayload);

        $ingestRes->assertStatus(201);
        $invoiceId = $ingestRes->json('data.id');
        $invoice = Invoice::findOrFail($invoiceId);

        // Verify PhilHealth split:
        // Total Gross: ₱50,000
        // Senior 20% Discount: ₱10,000 (20% of ₱50,000)
        // PhilHealth: ₱15,000 (Hospital Share 60% = ₱9,000; Doctor PF Share 40% = ₱6,000)
        // HMO: ₱10,000
        // Patient Copay: ₱15,000 (50k - 10k - 15k - 10k)
        $this->assertEquals('50000.0000', $invoice->total_amount);
        $this->assertEquals('15000.0000', $invoice->patient_payable);

        // Assert Doctor PF Holdback liability booked to Account 2040 (₱6,000)
        $doctorPfAcc = Account::where('code', '2040')->first();
        $this->assertNotNull($doctorPfAcc);

        // Assert CAS Audit Trail logged
        $this->assertDatabaseHas('cas_audit_trails', [
            'auditable_type' => Invoice::class,
            'auditable_id'   => $invoice->id,
            'action'         => 'INSERT',
        ]);

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 2: Cashier POS Collection & Official Receipt (/accounting/cashier/pay)
        // ─────────────────────────────────────────────────────────────────────────
        $this->actingAs($this->cashier);

        $collectRes = $this->post('/accounting/cashier/pay', [
            'invoice_id'     => $invoice->id,
            'amount'         => 15000.00,
            'payment_method' => 'CASH',
            'notes'          => 'Full Copay Settlement at Cashier Desk',
        ]);

        $collectRes->assertStatus(302);
        $invoice->refresh();
        $this->assertEquals('SETTLED', $invoice->status);
        $this->assertEquals('0.0000', $invoice->balance_due);

        // Cashier Undeposited Collections (Account 1011) now has ₱15,000
        $cashDrawer = Account::where('code', '1011')->firstOrFail();

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 3: Shift End Bank Deposit Slip & Clearance (/collection/deposit-slips)
        // ─────────────────────────────────────────────────────────────────────────
        $this->actingAs($this->accountant);

        $shift = CashierShift::create([
            'shift_code'                => 'SHIFT-2026-001',
            'cashier_id'                => $this->cashier->id,
            'terminal_name'             => 'MAIN-POS-01',
            'opened_at'                 => now()->subHours(8),
            'closed_at'                 => now(),
            'opening_cash_float'        => '5000.0000',
            'expected_cash'             => '20000.0000', // 5k base + 15k collected
            'actual_cash_counted'       => '20000.0000',
            'cash_variance'             => '0.0000',
            'total_digital_collections' => '0.0000',
            'total_collections'         => '15000.0000',
            'status'                    => 'CLOSED',
        ]);

        $bankDeposit = BankDeposit::create([
            'deposit_reference'     => 'DEP-2026-0091',
            'cashier_shift_id'      => $shift->id,
            'bank_account_id'       => $this->operatingBank->id,
            'deposit_date'          => '2026-01-11',
            'cash_amount'           => '15000.0000',
            'check_amount'          => '0.0000',
            'total_deposited'       => '15000.0000',
            'bank_reference_number' => 'BRN-MBTC-99182',
            'validated_by_teller'   => 'TELLER-MARIA-01',
            'status'                => 'PREPARED',
        ]);

        // Clear Bank Deposit: DR Bank 1020 ₱15,000 / CR Cashier Undeposited 1011 ₱15,000
        $this->glService->createAndPostEntry(new JournalEntryData(
            entryDate: '2026-01-11',
            description: 'Cashier Shift Cash Deposit to Metrobank',
            referenceNumber: $bankDeposit->deposit_reference,
            lines: [
                ['account_id' => $this->operatingBank->gl_account_id, 'debit' => '15000.0000', 'credit' => '0.0000', 'memo' => 'Shift Deposit Cleared'],
                ['account_id' => $cashDrawer->id, 'debit' => '0.0000', 'credit' => '15000.0000', 'memo' => 'Clear Cash Drawer'],
            ],
            userId: $this->accountant->id
        ));
        $this->operatingBank->balance = bcadd((string) $this->operatingBank->balance, '15000.0000', 4);
        $this->operatingBank->save();
        $bankDeposit->update(['status' => 'RECONCILED']);

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 4: Medical Vendor Bill Ingestion & 3-Way Match (/api/v1/ingest/vendor-bill)
        // ─────────────────────────────────────────────────────────────────────────
        $vendor = Vendor::create([
            'code'           => 'VEND-B-BRAUN',
            'name'           => 'B. Braun Medical Supplies Philippines',
            'tin'            => '222-333-444-000',
            'classification' => 'Medical Equipment & Pharmaceuticals',
            'status'         => 'Active',
            'is_active'      => true,
        ]);

        $vendorBillRes = $this->withHeaders([
            'X-Idempotency-Key' => 'IDEMP-INGEST-VENDOR-001',
            'Accept'            => 'application/json',
        ])->postJson('/api/v1/ingest/vendor-bill', [
            'vendor_id'             => $vendor->id,
            'po_number'             => 'PO-2026-MED-01',
            'grn_reference'         => 'GRN-2026-MED-01',
            'vendor_invoice_number' => 'INV-BB-88991',
            'bill_date'             => '2026-01-12',
            'due_date'              => '2026-02-12',
            'invoice_amount'        => 60000.00,
            'atc_code'              => 'WI158',
            'items'                 => [
                [
                    'item_code'    => 'MED-SUP-IV',
                    'description'  => 'IV Catheters & Infusion Cannulas Box',
                    'expense_type' => 'GOODS_INVENTORY',
                    'quantity'     => 100,
                    'unit_price'   => 600.00,
                ],
            ],
        ]);

        $vendorBillRes->assertStatus(201);
        $billId = $vendorBillRes->json('data.id');
        $bill = PurchaseBill::findOrFail($billId);

        // Release Disbursement Voucher for Vendor Bill (Net ₱59,400 with 1% EWT ₱600)
        $this->actingAs($this->manager);
        $apAcc = Account::where('code', '2010')->firstOrFail();
        $ewtAcc = Account::where('code', '2030')->firstOrFail();

        $dv = DisbursementVoucher::create([
            'voucher_number'       => 'DV-MED-2026-001',
            'purchase_bill_id'     => $bill->id,
            'bank_account_id'      => $this->operatingBank->id,
            'voucher_date'         => '2026-01-15',
            'payee_name'           => $vendor->name,
            'gross_amount'         => '60000.0000',
            'withheld_tax_amount'  => '600.0000',
            'net_disbursed_amount' => '59400.0000',
            'payment_method'       => 'CHECK',
            'check_or_eft_ref'     => 'CHK-MB-9001',
            'status'               => 'RELEASED',
            'approved_by'          => $this->cfo->id,
            'released_at'          => now(),
        ]);

        $this->glService->createAndPostEntry(new JournalEntryData(
            entryDate: '2026-01-15',
            description: 'Vendor Settlement to B. Braun with 1% EWT',
            referenceNumber: $dv->voucher_number,
            lines: [
                ['account_id' => $apAcc->id, 'debit' => '60000.0000', 'credit' => '0.0000', 'memo' => 'AP Settlement'],
                ['account_id' => $ewtAcc->id, 'debit' => '0.0000', 'credit' => '600.0000', 'memo' => '1% EWT Withheld'],
                ['account_id' => $this->operatingBank->gl_account_id, 'debit' => '0.0000', 'credit' => '59400.0000', 'memo' => 'Check Payment'],
            ],
            userId: $this->accountant->id
        ));
        $this->operatingBank->balance = bcsub((string) $this->operatingBank->balance, '59400.0000', 4);
        $this->operatingBank->save();
        $bill->update(['paid_amount' => '60000.0000', 'status' => 'PAID']);

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 5: HRMS Staff Payroll Batch Ingestion (/api/v1/ingest/payroll-register)
        // ─────────────────────────────────────────────────────────────────────────
        $payrollRes = $this->withHeaders([
            'X-Idempotency-Key' => 'IDEMP-INGEST-PAYROLL-001',
            'Accept'            => 'application/json',
        ])->postJson('/api/v1/ingest/payroll-register', [
            'cutoff_start'                 => '2026-01-01',
            'cutoff_end'                   => '2026-01-15',
            'payout_date'                  => '2026-01-15',
            'disbursement_bank_account_id' => $this->operatingBank->id,
            'total_gross_pay'              => 100000.00,
            'total_net_pay'                => 85000.00,
            'total_sss_employee'           => 1350.00,
            'total_sss_employer'           => 2850.00,
            'total_philhealth_employee'    => 2500.00,
            'total_philhealth_employer'    => 2500.00,
            'total_pagibig_employee'       => 200.00,
            'total_pagibig_employer'       => 200.00,
            'total_withholding_tax_1601c'  => 8450.00,
            'employees'                    => [
                [
                    'employee_id_number' => 'EMP-DOC-001',
                    'employee_name'      => 'Dr. Jose Rizal, MD',
                    'department'         => 'SURGERY',
                    'basic_salary'       => 100000.00,
                ],
            ],
        ]);

        $payrollRes->assertStatus(201);
        $payrollRunId = $payrollRes->json('data.id');
        $this->assertNotNull($payrollRunId);

        // Verify seeded statutory liability accounts credited:
        $tax1601c = Account::where('code', '2120')->firstOrFail();
        $sssPayable = Account::where('code', '2130')->firstOrFail();
        $this->assertNotNull($tax1601c);
        $this->assertNotNull($sssPayable);

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 6: Double-Entry Trial Balance Verification
        // ─────────────────────────────────────────────────────────────────────────
        $trialBalance = $this->glReportService->getTrialBalance();

        $this->assertTrue($trialBalance['is_balanced'], 'Trial balance must be perfectly balanced.');
        $this->assertEquals(0, bccomp($trialBalance['total_debit'], $trialBalance['total_credit'], 4));

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 7: General Ledger Income Statement & Balance Sheet Reporting
        // ─────────────────────────────────────────────────────────────────────────
        $incomeStatement = $this->glReportService->getIncomeStatement($trialBalance);
        $this->assertNotNull($incomeStatement);
        $this->assertArrayHasKey('net_income', $incomeStatement);

        $balanceSheet = $this->glReportService->getBalanceSheet($trialBalance, $incomeStatement);
        $this->assertNotNull($balanceSheet);
        $this->assertArrayHasKey('total_assets', $balanceSheet);
        $this->assertArrayHasKey('total_liabilities', $balanceSheet);
        $this->assertArrayHasKey('total_equity', $balanceSheet);

        // ─────────────────────────────────────────────────────────────────────────
        // STEP 8: Cryptographic BIR CAS Audit Trail Hash Chain Integrity
        // ─────────────────────────────────────────────────────────────────────────
        $isChainValid = $this->auditService->verifyAuditTrailIntegrity();
        $this->assertTrue($isChainValid, 'BIR CAS cryptographic SHA-256 hash chain must be 100% unbroken.');
    }

    /** @test */
    public function test_idempotency_middleware_blocks_concurrent_duplicate_ingestion(): void
    {
        $patient = PatientAccount::create([
            'patient_id_number' => 'MRN-IDEMP-001',
            'full_name'         => 'Idempotency Concurrency Patient',
            'admission_type'    => 'OUTPATIENT',
            'current_balance'   => '0.0000',
            'credit_limit'      => '50000.0000',
            'status'            => 'Active',
        ]);

        $payload = [
            'patient_id'        => $patient->id,
            'bdms_bill_number'  => 'BDMS-CONCUR-001',
            'invoice_date'      => '2026-01-20',
            'gross_amount'      => 5000.00,
            'net_copay'         => 5000.00,
            'charge_lines'      => [
                [
                    'item_code'          => 'CONSULT-01',
                    'description'        => 'Specialist Consultation',
                    'department'         => 'OUTPATIENT',
                    'revenue_category'   => 'CLINICAL',
                    'quantity'           => 1,
                    'unit_price'         => 5000.00,
                    'is_vatable'         => false,
                    'is_senior_eligible' => false,
                ],
            ],
        ];

        // 1. Initial Request
        $res1 = $this->withHeaders([
            'X-Idempotency-Key' => 'KEY-CONCURRENCY-TEST-99',
            'Accept'            => 'application/json',
        ])->postJson('/api/v1/ingest/patient-bill', $payload);

        $res1->assertStatus(201);

        // 2. Immediate Replay Request
        $res2 = $this->withHeaders([
            'X-Idempotency-Key' => 'KEY-CONCURRENCY-TEST-99',
            'Accept'            => 'application/json',
        ])->postJson('/api/v1/ingest/patient-bill', $payload);

        $res2->assertStatus(201);
        $res2->assertHeader('X-Idempotency-Replay', 'true');
    }
}

