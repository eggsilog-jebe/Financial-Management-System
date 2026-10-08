<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\GeneralLedger\ChartOfAccountsController;
use App\Http\Controllers\GeneralLedger\JournalEntryController;
use App\Http\Controllers\GeneralLedger\LedgerBookController;
use App\Http\Controllers\GeneralLedger\TrialBalanceController;
use App\Http\Controllers\GeneralLedger\FiscalPeriodController;
use App\Http\Controllers\GeneralLedger\PostJournalEntryController;
use App\Http\Controllers\IngestClinicalBillablesController;
use App\Http\Controllers\IngestPayrollRunController;
use App\Http\Controllers\IngestVendorBillController;
use App\Http\Controllers\ProcessPaymentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Accounting\FinancialDashboardController;
use App\Http\Controllers\Accounting\GeneralLedgerBrowserController;
use App\Http\Controllers\Accounting\FinancialReportsViewController;
use App\Http\Controllers\Accounting\PeriodClosingViewController;
use App\Http\Controllers\Accounting\AuditLogController;
use App\Http\Controllers\Accounting\Export\ExportAndPrintController;
use App\Http\Controllers\AccountsPayable\VendorController;
use App\Http\Controllers\AccountsPayable\VendorInvoiceController;
use App\Http\Controllers\AccountsPayable\PurchaseBillController;
use App\Http\Controllers\AccountsPayable\PayableAgingController;
use App\Http\Controllers\AccountsPayable\PaymentApprovalController;
use App\Http\Controllers\AccountsReceivable\PatientAccountController;
use App\Http\Controllers\AccountsReceivable\PatientInvoiceController;
use App\Http\Controllers\AccountsReceivable\ReceivableAgingController;
use App\Http\Controllers\AccountsReceivable\CreditNoteController;
use App\Http\Controllers\AccountsReceivable\CustomerStatementController;
use App\Http\Controllers\AccountsReceivable\MalasakitAssistanceController;
use App\Http\Controllers\Disbursement\PaymentRequestController;
use App\Http\Controllers\Disbursement\CheckRegisterController;
use App\Http\Controllers\Disbursement\EftTransferController;
use App\Http\Controllers\Disbursement\DisbursementApprovalController;
use App\Http\Controllers\Disbursement\PettyCashController;
use App\Http\Controllers\Disbursement\StorePayrollRunController;
use App\Http\Controllers\Collection\CashierDeskController;
use App\Http\Controllers\Collection\CashierShiftController;
use App\Http\Controllers\Collection\PaymentReceiptController;
use App\Http\Controllers\Collection\DepositSlipBatchController;
use App\Http\Controllers\Collection\BankDepositController;
use App\Http\Controllers\Collection\PaymentGatewayLogController;
use App\Http\Controllers\UserSecurity\UserManagementController;
use App\Http\Controllers\Auth\ChangePasswordController;

// ─── External Subsystem Integration API Endpoints ──────────────────────────────
// CSRF excluded for api/* in bootstrap/app.php. The bare /ingest-encounter-billing
// is an internal sidecar-only endpoint; throttled + idempotency-protected.
Route::post('/ingest-encounter-billing', \App\Http\Controllers\Api\V1\Ingestion\SimulateEncounterBillingApiController::class)
    ->middleware(['throttle:30,1', 'idempotency'])
    ->name('ingest-encounter-billing');
Route::post('/api/v1/ingest/encounter-billing', \App\Http\Controllers\Api\V1\Ingestion\SimulateEncounterBillingApiController::class)
    ->middleware('idempotency')
    ->name('api.v1.ingest.encounter-billing');

// ─── Public: Legal & Regulatory Compliance Framework ────────────────────────
Route::get('/terms', [\App\Http\Controllers\LegalComplianceController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [\App\Http\Controllers\LegalComplianceController::class, 'privacy'])->name('legal.privacy');

// ─── Public: Authentication Routes (no auth required) ────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:60,1')
        ->name('login.post');
});

// Workstation & 2FA Challenge — accessible after password auth
Route::middleware('auth')->group(function () {
    // Workstation / Computer Binding Authorization Holding Screen & Status
    Route::get('/workstation-authorization-pending', [\App\Http\Controllers\Auth\WorkstationAuthorizationController::class, 'show'])
        ->name('workstation.pending');
    Route::get('/workstation/status', [\App\Http\Controllers\Auth\WorkstationAuthorizationController::class, 'checkStatus'])
        ->name('workstation.status');
    Route::post('/workstation/cancel', [\App\Http\Controllers\Auth\WorkstationAuthorizationController::class, 'cancel'])
        ->name('workstation.cancel');

    Route::get('/two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'show'])
        ->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [\App\Http\Controllers\Auth\TwoFactorChallengeController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('two-factor.challenge.verify');

    // 2FA Setup / TOTP Enrollment (Google Authenticator)
    Route::get('/two-factor-setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'show'])
        ->name('two-factor.setup');
    Route::post('/two-factor-setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'store'])
        ->name('two-factor.setup.store');
    Route::post('/two-factor-setup/confirm', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'confirm'])
        ->name('two-factor.setup.confirm');
    Route::delete('/two-factor-setup', [\App\Http\Controllers\Auth\TwoFactorSetupController::class, 'destroy'])
        ->name('two-factor.setup.destroy');

    // Session Heartbeat — keeps the server session alive from the client-side idle monitor
    Route::post('/session/heartbeat', function (\Illuminate\Http\Request $request) {
        $request->session()->put('auth.last_activity_at', now()->toIso8601String());
        return response()->json(['status' => 'ok', 'remaining' => 900]);
    })->name('session.heartbeat');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');

// ─── Root redirect ────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }
    return redirect()->route('accounting.dashboard');
})->name('home');

// ─── Protected: All routes below require authentication ───────────────────────
Route::middleware(['auth'])->group(function () {

    // Legacy dashboard
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // 1. General Ledger Module
    Route::prefix('general-ledger')->name('gl.')->group(function () {
        // Chart of Accounts
        Route::get('/chart-of-accounts', [ChartOfAccountsController::class, 'index'])->name('chart-of-accounts');
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/chart-of-accounts', [ChartOfAccountsController::class, 'store'])->name('chart-of-accounts.store');
            Route::match(['put', 'patch'], '/chart-of-accounts/{id}', [ChartOfAccountsController::class, 'update'])->name('chart-of-accounts.update');
            Route::post('/chart-of-accounts/{id}/toggle-status', [ChartOfAccountsController::class, 'toggleStatus'])->name('chart-of-accounts.toggle-status');
        });

        // Journal Entries
        Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries');
        Route::post('/journal-entries', [JournalEntryController::class, 'store'])
            ->middleware('idempotency')
            ->name('journal-entries.store');
        Route::post('/post-entry', PostJournalEntryController::class)
            ->middleware('idempotency')
            ->name('post-entry');

        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/journal-entries/{id}/post', [JournalEntryController::class, 'post'])
                ->middleware('idempotency')
                ->name('journal-entries.post');
            Route::post('/journal-entries/{id}/reverse', [JournalEntryController::class, 'reverse'])->name('journal-entries.reverse');
        });

        // Ledger Books
        Route::get('/ledger-books', [LedgerBookController::class, 'index'])->name('ledger-books');
        Route::get('/ledger-books/export', [LedgerBookController::class, 'export'])->name('ledger-books.export');

        // Trial Balance
        Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial-balance');
        Route::get('/trial-balance/export', [TrialBalanceController::class, 'export'])->name('trial-balance.export');

        // Period-End Closing
        Route::get('/period-end-closing', [FiscalPeriodController::class, 'index'])->name('period-end-closing');
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/period-end-closing/initialize', [FiscalPeriodController::class, 'initialize'])->name('period-end-closing.initialize');
            Route::post('/period-end-closing/{id}/lock', [FiscalPeriodController::class, 'lock'])->name('period-end-closing.lock');
        });
        Route::middleware(['role:CFO,FinanceDirector'])->group(function () {
            Route::post('/period-end-closing/{id}/close', [FiscalPeriodController::class, 'close'])->name('period-end-closing.close');
        });
    });

    // 2. Accounts Payable
    Route::prefix('accounts-payable')->name('ap.')->group(function () {
        // Vendor Management
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor,BillingClerk'])->group(function () {
            Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
            // Backward-compat alias — sidebar links use this; do not remove.
            Route::get('/vendor-management', [VendorController::class, 'index'])->name('vendors');
            Route::get('/vendors/export', [VendorController::class, 'exportVendors'])->name('vendors.export');
        });
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
            Route::put('/vendors/{id}', [VendorController::class, 'update'])->name('vendors.update');
            Route::patch('/vendors/{id}/toggle', [VendorController::class, 'toggle'])->name('vendors.toggle');
            Route::post('/vendors/{id}/toggle-status', [VendorController::class, 'toggle'])->name('vendors.toggle-status');
        });

        // Invoices & Vouchers Hub
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/invoices-vouchers', [VendorInvoiceController::class, 'index'])->name('invoices');
            Route::get('/invoices-vouchers/export', [VendorInvoiceController::class, 'exportApRegister'])->name('invoices.export');
            Route::get('/invoices-vouchers/batch-2307', [VendorInvoiceController::class, 'batchBir2307'])->name('invoices.batch-2307');
            Route::post('/invoices-vouchers/{id}/quick-approve', [VendorInvoiceController::class, 'quickApprove'])->name('invoices.quick-approve');
            Route::post('/invoices-vouchers/prepare-voucher', [VendorInvoiceController::class, 'prepareVoucher'])->name('invoices.prepare-voucher');
            Route::post('/vouchers/prepare', [VendorInvoiceController::class, 'prepareVoucher'])->name('vouchers.prepare');
        });

        // Purchase Bills & 3-Way Matching
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/purchase-bills', [PurchaseBillController::class, 'index'])->name('purchase-bills');
        });
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/purchase-bills', [PurchaseBillController::class, 'store'])->name('purchase-bills.store');
            Route::post('/purchase-bills/sync-psm-sws', [PurchaseBillController::class, 'syncPsmSws'])->name('purchase-bills.sync');
            Route::post('/ingest-bill', IngestVendorBillController::class)->name('ingest-bill');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/purchase-bills/{id}/approve', [PurchaseBillController::class, 'approve'])->name('purchase-bills.approve');
        });

        // Payable Aging Schedule
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/payable-aging', [PayableAgingController::class, 'index'])->name('payable-aging');
            Route::get('/payable-aging/export', [PayableAgingController::class, 'export'])->name('payable-aging.export');
        });

        // AP Payment Approvals & Disbursement
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/payment-approvals', [PaymentApprovalController::class, 'index'])->name('payment-approvals.index');
            // Backward-compat aliases — do not remove; used by AP sidebar and table links.
            Route::get('/ap-payment-approvals', [PaymentApprovalController::class, 'index'])->name('ap-approvals');
            Route::get('/payment-approvals/alias', [PaymentApprovalController::class, 'index'])->name('approvals');
            Route::get('/payment-approvals/export-bank-batch', [PaymentApprovalController::class, 'exportBankBatch'])->name('payment-approvals.export-bank-batch');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-approvals/{id}/approve', [PaymentApprovalController::class, 'approve'])->name('payment-approvals.approve');
            Route::post('/payment-approvals/{id}/reject', [PaymentApprovalController::class, 'reject'])->name('payment-approvals.reject');
            Route::post('/payment-approvals/bulk-approve', [PaymentApprovalController::class, 'bulkApprove'])->name('payment-approvals.bulk-approve');
        });
        Route::middleware(['role:CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-approvals/{id}/release', [PaymentApprovalController::class, 'release'])->name('payment-approvals.release');
        });
    });

    // 3. Accounts Receivable
    Route::prefix('accounts-receivable')->name('ar.')->group(function () {
        // Patient Accounts
        Route::middleware(['role:StaffAccountant,BillingClerk,Cashier,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/patients', [PatientAccountController::class, 'index'])->name('patients.index');
            Route::get('/patient-accounts', [PatientAccountController::class, 'index'])->name('customers');
        });
        Route::middleware(['role:StaffAccountant,BillingClerk,Cashier,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/patients', [PatientAccountController::class, 'store'])->name('patients.store');
        });

        // Invoicing & Patient Billing
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/invoices', [PatientInvoiceController::class, 'index'])->name('invoices.index');
            Route::get('/invoicing-billing', [PatientInvoiceController::class, 'index'])->name('billing');
            Route::get('/invoices/{id}/print', [PatientInvoiceController::class, 'print'])->name('invoices.print');
        });
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/invoices', [PatientInvoiceController::class, 'store'])
                ->middleware('idempotency')
                ->name('invoices.store');
            Route::post('/ingest-billables', IngestClinicalBillablesController::class)
                ->middleware('idempotency')
                ->name('ingest-billables');
        });

        // Receivable Aging Schedule
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/receivable-aging', [ReceivableAgingController::class, 'index'])->name('ar-aging');
            Route::get('/receivable-aging/export', [ReceivableAgingController::class, 'export'])->name('ar-aging.export');
        });

        // Credit Notes & Statutory Discounts
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes');
        });
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/credit-notes', [CreditNoteController::class, 'store'])->name('credit-notes.store');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/credit-notes/{id}/approve', [CreditNoteController::class, 'approve'])->name('credit-notes.approve');
            Route::post('/credit-notes/{id}/post', [CreditNoteController::class, 'postCreditNote'])->name('credit-notes.post');
            Route::post('/credit-notes/{id}/void', [CreditNoteController::class, 'void'])->name('credit-notes.void');
        });

        // Customer Statements of Account (SOA)
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/customer-statements', [CustomerStatementController::class, 'index'])->name('statements');
            Route::get('/customer-statements/print', [CustomerStatementController::class, 'print'])->name('statements.print');
            Route::get('/customer-statements/export', [CustomerStatementController::class, 'export'])->name('statements.export');
        });

        // Malasakit Center & Government Assistance (RA 11463)
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/malasakit-assistance', [MalasakitAssistanceController::class, 'index'])->name('malasakit.index');
            Route::post('/malasakit-assistance/calculate', [MalasakitAssistanceController::class, 'calculate'])->name('malasakit.calculate');
        });
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/malasakit-assistance', [MalasakitAssistanceController::class, 'store'])->name('malasakit.store');
        });
    });

    // 4. Disbursement Management
    Route::prefix('disbursement-management')->name('disbursement.')->group(function () {
        // Payment Requests
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/payment-requests', [PaymentRequestController::class, 'index'])->name('payment-requests');
            Route::get('/payment-requests/export', [PaymentRequestController::class, 'export'])->name('payment-requests.export');
        });
        Route::middleware(['role:StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-requests', [PaymentRequestController::class, 'store'])
                ->middleware('idempotency')
                ->name('payment-requests.store');
        });
        Route::middleware(['role:Auditor,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-requests/{id}/audit', [PaymentRequestController::class, 'audit'])->name('payment-requests.audit');
            Route::post('/payment-requests/{id}/void', [PaymentRequestController::class, 'void'])->name('payment-requests.void');
        });
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payroll-runs', StorePayrollRunController::class)->name('payroll.store');
        });
        Route::post('/ingest-payroll', IngestPayrollRunController::class)->name('ingest-payroll');

        // Check Register & Printing
        Route::middleware(['role:StaffAccountant,Cashier,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/check-register', [CheckRegisterController::class, 'index'])->name('check-register');
            Route::get('/check-register/{id}/print', [CheckRegisterController::class, 'print'])->name('check-register.print');
        });
        Route::middleware(['role:StaffAccountant,Cashier,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/check-register', [CheckRegisterController::class, 'store'])->name('check-register.store');
            Route::post('/check-register/{id}/clear', [CheckRegisterController::class, 'clear'])->name('check-register.clear');
        });

        // EFT & Electronic Payouts
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/eft-transfers', [EftTransferController::class, 'index'])->name('eft-transfers');
            Route::get('/eft-transfers/export', [EftTransferController::class, 'export'])->name('eft-transfers.export');
        });
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/eft-transfers', [EftTransferController::class, 'store'])->name('eft-transfers.store');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/eft-transfers/{id}/approve', [EftTransferController::class, 'approve'])->name('eft-transfers.approve');
        });

        // Disbursement Approvals & Release Workstation
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/disbursement-approvals', [DisbursementApprovalController::class, 'index'])->name('disbursement-approval');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/disbursement-approvals/{id}/approve', [DisbursementApprovalController::class, 'approve'])->name('disbursement-approvals.approve');
        });
        Route::middleware(['role:CFO,FinanceDirector'])->group(function () {
            Route::post('/disbursement-approvals/{id}/release', [DisbursementApprovalController::class, 'release'])->name('disbursement-approvals.release');
        });

        // Petty Cash Custody & Replenishment
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/petty-cash', [PettyCashController::class, 'index'])->name('petty-cash');
        });
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/petty-cash/funds', [PettyCashController::class, 'storeFund'])->name('petty-cash.funds.store');
            Route::post('/petty-cash/expense', [PettyCashController::class, 'storeExpense'])->name('petty-cash.expense');
            Route::post('/petty-cash/replenish', [PettyCashController::class, 'replenish'])->name('petty-cash.replenish');
        });
    });

    // 5. Collection Management
    Route::prefix('collection-management')->name('collection.')->group(function () {
        // Cashier Desk & Shift Lifecycle
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/cashier-desk', [CashierDeskController::class, 'index'])->name('cashier-desk');
        });
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/cashier-desk/collect', [CashierDeskController::class, 'collect'])->name('cashier-desk.collect');
            Route::post('/shifts/open', [CashierShiftController::class, 'open'])->name('shifts.open');
            Route::post('/shifts/close', [CashierShiftController::class, 'close'])->name('shifts.close');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::post('/shifts/{id}/reconcile', [CashierShiftController::class, 'reconcile'])->name('shifts.reconcile');
        });

        // Payment Receipts Hub
        Route::middleware(['role:Cashier,StaffAccountant,BillingClerk,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/payment-receipts', [PaymentReceiptController::class, 'index'])->name('receipts');
            Route::get('/payment-receipts/{id}/print', [PaymentReceiptController::class, 'print'])->name('receipts.print');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-receipts/{id}/void', [PaymentReceiptController::class, 'voidReceipt'])->name('receipts.void');
        });
        Route::post('/process-payment', ProcessPaymentController::class)
            ->middleware('idempotency')
            ->name('process-payment');

        // Deposit Slips & Batching
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/deposit-slips', [DepositSlipBatchController::class, 'index'])->name('deposit-slips');
        });

        // Bank Deposits & Clearing
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/bank-deposits', [BankDepositController::class, 'index'])->name('bank-deposits');
        });
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/bank-deposits', [BankDepositController::class, 'store'])->name('bank-deposits.store');
        });
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/bank-deposits/{id}/clear', [BankDepositController::class, 'clear'])->name('bank-deposits.clear');
            Route::post('/bank-deposits/{id}/reject', [BankDepositController::class, 'reject'])->name('bank-deposits.reject');
        });

        // Payment Gateway Logs
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/payment-gateway-logs', [PaymentGatewayLogController::class, 'index'])->name('payment-gateways');
        });
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/payment-gateway-logs/{id}/retrigger-gl', [PaymentGatewayLogController::class, 'retriggerGl'])->name('payment-gateways.retrigger-gl');
        });
    });

    // 6. Budget Management
    Route::prefix('budget-management')->name('budget.')->group(function () {
        // Read-only Budget Workstations
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/fiscal-planning', [BudgetController::class, 'fiscalPlanning'])->name('fiscal-planning');
            Route::get('/budget-allocation', [BudgetController::class, 'budgetAllocation'])->name('budget-allocation');
            Route::get('/departmental-budgets', [BudgetController::class, 'departmentalBudgets'])->name('departmental-budgets');
            Route::get('/variance-analysis', [BudgetController::class, 'varianceAnalysis'])->name('variance-analysis');
            Route::get('/budget-reallocations', [BudgetController::class, 'budgetReallocations'])->name('reallocations');
        });

        // Allocation & Encumbrance Operations (Staff Accountants & Finance Managers)
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/allocations', [BudgetController::class, 'storeAllocation'])->name('allocations.store');
            Route::post('/encumber', [BudgetController::class, 'encumber'])->name('encumber');
            Route::post('/encumber/{id}/release', [BudgetController::class, 'releaseEncumbrance'])->name('encumber.release');
        });

        // Inter-Departmental Reallocation (Restricted to Finance Managers & CFO / Directors)
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/reallocate', [BudgetController::class, 'reallocate'])->name('reallocate');
        });
    });

    // 7. Accounting UI Interfaces
    Route::prefix('accounting')->name('accounting.')->group(function () {

        // Executive Dashboard (All authenticated roles)
        Route::get('/dashboard', FinancialDashboardController::class)->name('dashboard');

        // Cashier POS & Official Receipts
        Route::middleware(['role:Cashier,StaffAccountant,FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::get('/cashier', [CashierDeskController::class, 'index'])->name('cashier');
            Route::post('/cashier/pay', [CashierDeskController::class, 'collect'])->name('cashier.pay');
            Route::get('/print/or/{id}', [ExportAndPrintController::class, 'printOfficialReceipt'])->name('print.or');
        });

        // General Ledger Browser
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/general-ledger', [GeneralLedgerBrowserController::class, 'index'])->name('general-ledger.index');
        });

        // GL Reversals
        Route::middleware(['role:FinanceManager,CFO,FinanceDirector'])->group(function () {
            Route::post('/general-ledger/{id}/reverse', [GeneralLedgerBrowserController::class, 'reverse'])->name('general-ledger.reverse');
        });

        // Financial Reports & BIR Exports
        Route::middleware(['role:StaffAccountant,FinanceManager,CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/reports', [FinancialReportsViewController::class, 'index'])->name('reports.index');
            Route::get('/print/bir-2307/{id}', [ExportAndPrintController::class, 'printBir2307'])->name('print.bir2307');
            Route::get('/export/trial-balance-csv', [ExportAndPrintController::class, 'downloadTrialBalanceCsv'])->name('export.trial-balance-csv');
            Route::get('/export/general-ledger-csv', [ExportAndPrintController::class, 'downloadGeneralLedgerCsv'])->name('export.general-ledger-csv');
        });

        // Period-End Closing & Hard Locking
        Route::middleware(['role:CFO,FinanceDirector'])->group(function () {
            Route::get('/period-close', [PeriodClosingViewController::class, 'index'])->name('period-close.index');
            Route::post('/period-close/lock', [PeriodClosingViewController::class, 'lock'])->name('period-close.lock');
        });

        // System Audit Trail & Compliance (CFO and Auditor only)
        Route::middleware(['role:CFO,FinanceDirector,Auditor'])->group(function () {
            Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
            Route::post('/audit-log/acknowledge', [AuditLogController::class, 'acknowledgeAlert'])->name('audit-log.acknowledge');
        });
    });

    // 11. User & Security Management (CFO Only)
    Route::prefix('user-security')->name('user-security.')->middleware(['role:CFO,FinanceDirector'])->group(function () {
        // User Accounts
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

        // System Audit Trail (alias - same controller)
        Route::get('/audit-trail', [AuditLogController::class, 'index'])->name('audit-trail');
        Route::post('/audit-trail/acknowledge', [AuditLogController::class, 'acknowledgeAlert'])->name('audit-trail.acknowledge');


        // Workstation Binding & Active Session Security
        Route::get('/workstations', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'index'])->name('workstations');
        Route::get('/workstations/poll', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'pollData'])->name('workstations.poll');
        Route::post('/workstations/{workstation}/approve', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'approve'])->name('workstations.approve');
        Route::post('/workstations/{workstation}/reject', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'reject'])->name('workstations.reject');
        Route::delete('/workstations/{workstation}', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'revoke'])->name('workstations.revoke');
        Route::delete('/workstations/{workstation}/delete', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'destroy'])->name('workstations.destroy');
        Route::post('/workstations/reset-all', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'resetAll'])->name('workstations.reset-all');
        Route::post('/active-sessions/{session}/terminate', [\App\Http\Controllers\UserSecurity\WorkstationSecurityController::class, 'terminateSession'])->name('sessions.terminate');
    });

    // Change Password (for users with must_change_password flag)
    Route::get('/change-password', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    // Account Settings & Appearance Theme (All Authenticated Users)
    Route::prefix('account-settings')->name('account.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Profile\AccountSettingsController::class, 'index'])->name('settings');
        Route::post('/profile', [\App\Http\Controllers\Profile\AccountSettingsController::class, 'updateProfile'])->name('profile.update');
        Route::post('/photo', [\App\Http\Controllers\Profile\AccountSettingsController::class, 'updatePhoto'])->name('photo.update');
        Route::post('/photo/remove', [\App\Http\Controllers\Profile\AccountSettingsController::class, 'removePhoto'])->name('photo.remove');
        Route::post('/theme', [\App\Http\Controllers\Profile\AccountSettingsController::class, 'updateTheme'])->name('theme.update');
    });

    // Live System Alerts Notification Hub
    Route::prefix('system-alerts')->name('system-alerts.')->group(function () {
        Route::get('/feed', [\App\Http\Controllers\Security\SystemAlertController::class, 'feed'])->name('feed');
        Route::post('/acknowledge', [\App\Http\Controllers\Security\SystemAlertController::class, 'acknowledge'])->name('acknowledge');
    });

    // Global & Contextual Search Endpoint (Omnibar & Submodule Scope)
    Route::get('/global-search', [\App\Http\Controllers\GlobalSearchController::class, 'search'])->name('global.search');

}); // end auth middleware group
