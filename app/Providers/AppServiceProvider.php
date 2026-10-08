<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useTailwind();

        // Force HTTPS for all URLs in production (Vercel terminates SSL at edge,
        // forwards requests as HTTP internally — without this, form actions and
        // route() helper generate http:// URLs which browsers block as insecure).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Share pending workstation authorization count with sidebar and dashboard for Super Admins
        view()->composer(['partials.sidebar', 'accounting.dashboard'], function ($view): void {
            if (auth()->check() && in_array(auth()->user()->role, ['CFO', 'FinanceDirector', 'SuperAdmin'], true)) {
                try {
                    $pendingCount = \App\Models\UserWorkstation::where('status', \App\Models\UserWorkstation::STATUS_PENDING)->count();
                    $view->with('pendingWorkstationsCount', $pendingCount);
                } catch (\Throwable) {
                    $view->with('pendingWorkstationsCount', 0);
                }
            } else {
                $view->with('pendingWorkstationsCount', 0);
            }
        });

        // Share live system alerts data with headbar bell notification
        view()->composer('partials.headbar', function ($view): void {
            if (auth()->check()) {
                try {
                    $alertService = app(\App\Services\Security\SystemAlertService::class);
                    $acknowledgedAt = session('security_alert_acknowledged_at');
                    $alertsData = $alertService->getLiveAlertsForUser(auth()->user(), $acknowledgedAt);
                    $view->with('systemAlertsData', $alertsData);
                } catch (\Throwable) {
                    $view->with('systemAlertsData', [
                        'alerts'           => [],
                        'count'            => 0,
                        'has_alerts'       => false,
                        'highest_severity' => 'none',
                    ]);
                }
            } else {
                $view->with('systemAlertsData', [
                    'alerts'           => [],
                    'count'            => 0,
                    'has_alerts'       => false,
                    'highest_severity' => 'none',
                ]);
            }
        });

        // Define Gates for Financial Segregation of Duties (SoD)
        \Illuminate\Support\Facades\Gate::before(function ($user, string $ability): ?bool {
            // In local development or demo exploration without login, allow full visibility
            if (! $user && app()->environment('local', 'testing')) {
                return true;
            }

            // CFO and FinanceDirector always have full superuser access to all modules
            if ($user && in_array($user->role, ['CFO', 'FinanceDirector'], true)) {
                return true;
            }

            return null;
        });

        \Illuminate\Support\Facades\Gate::define('access-cashier-pos', function ($user): bool {
            return in_array($user->role, ['Cashier', 'StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-ar-billing', function ($user): bool {
            return in_array($user->role, ['BillingClerk', 'StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-ap-procurement', function ($user): bool {
            return in_array($user->role, ['StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-disbursements', function ($user): bool {
            return in_array($user->role, ['StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-budget', function ($user): bool {
            return in_array($user->role, ['StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-general-ledger', function ($user): bool {
            return in_array($user->role, ['StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector', 'Auditor'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-period-closing', function ($user): bool {
            return in_array($user->role, ['CFO', 'FinanceDirector'], true);
        });

        \Illuminate\Support\Facades\Gate::define('access-user-management', function ($user): bool {
            return in_array($user->role, ['CFO', 'FinanceDirector'], true);
        });

        \Illuminate\Support\Facades\Gate::define('post-journal-entries', function ($user): bool {
            return in_array($user->role, ['StaffAccountant', 'FinanceManager', 'CFO', 'FinanceDirector'], true);
        });

        \Illuminate\Support\Facades\Gate::define('reverse-journal-entries', function ($user): bool {
            return in_array($user->role, ['FinanceManager', 'CFO', 'FinanceDirector'], true);
        });

        // Register Audit Trail Observers for all financial and security entities
        $observedModels = [
            // General Ledger & Chart of Accounts
            \App\Models\Account::class,
            \App\Models\JournalEntry::class,
            \App\Models\JournalEntryLine::class,
            \App\Models\FiscalPeriod::class,

            // Patient Billing & Accounts Receivable
            \App\Models\PatientAccount::class,
            \App\Models\Invoice::class,
            \App\Models\InvoiceItem::class,
            \App\Models\BillItem::class,
            \App\Models\CreditNote::class,
            \App\Models\DoctorProfile::class,
            \App\Models\StatutoryDiscount::class,

            // Accounts Payable & Procurement
            \App\Models\Vendor::class,
            \App\Models\PurchaseBill::class,
            \App\Models\ThreeWayMatch::class,
            \App\Models\Bir2307Certificate::class,

            // Disbursements & Payroll
            \App\Models\DisbursementVoucher::class,
            \App\Models\PaymentRequest::class,
            \App\Models\CheckRegister::class,
            \App\Models\PettyCashFund::class,
            \App\Models\PettyCashExpense::class,
            \App\Models\PayrollRun::class,
            \App\Models\PayrollItem::class,

            // Cashier POS & Collections
            \App\Models\CashierShift::class,
            \App\Models\Payment::class,
            \App\Models\OfficialReceipt::class,
            \App\Models\PaymentReceipt::class,

            // Bank Accounts & Deposits
            \App\Models\BankAccount::class,
            \App\Models\BankDeposit::class,

            // Fiscal Budgets
            \App\Models\BudgetAllocation::class,
            \App\Models\BudgetEncumbrance::class,
            \App\Models\BudgetReallocation::class,

            // Claims & Subsidies
            \App\Models\GuaranteeLetter::class,
            \App\Models\HmoClaim::class,
            \App\Models\PhilhealthClaim::class,

            // User & Terminal Security
            \App\Models\User::class,
            \App\Models\UserWorkstation::class,
            \App\Models\UserActiveSession::class,
        ];

        foreach ($observedModels as $modelClass) {
            $modelClass::observe(\App\Observers\ActivityLogObserver::class);
        }
    }
}
