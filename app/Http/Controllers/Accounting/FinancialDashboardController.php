<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BankDeposit;
use App\Models\BudgetAllocation;
use App\Models\GuaranteeLetter;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Payment;
use App\Models\PhilhealthClaim;
use App\Models\PurchaseBill;
use App\Models\UserWorkstation;
use App\Services\Accounting\AccountingCacheService;
use App\Services\Accounting\ExecutiveScorecardService;
use App\Services\Accounting\GeneralLedgerReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

final class FinancialDashboardController extends Controller
{
    public function __invoke(
        GeneralLedgerReportService $reportService,
        AccountingCacheService $cacheService,
        ExecutiveScorecardService $scorecardService,
    ): View {
        // ─── 1. Heavy GL / Balance-Sheet calculations (cached 3 minutes) ───────────
        $financialMetrics = $cacheService->rememberDashboardMetrics(function () use ($reportService): array {
            $trial        = $reportService->getTrialBalance();
            $pnl          = $reportService->getIncomeStatement($trial);
            $balanceSheet = $reportService->getBalanceSheet($trial, $pnl);

            // Cash accounts (Account codes 1010 / 1011 = Cash on Hand, 1020 / 1021 = Cash in Bank)
            $cashOnHand = '0.0000';
            $cashInBank = '0.0000';
            foreach ($balanceSheet['assets'] as $asset) {
                if (in_array($asset['code'], ['1010', '1011'], true)) {
                    $cashOnHand = bcadd($cashOnHand, (string) $asset['balance'], 4);
                } elseif (in_array($asset['code'], ['1020', '1021'], true)) {
                    $cashInBank = bcadd($cashInBank, (string) $asset['balance'], 4);
                }
            }

            // AR & AP outstanding amounts
            $outstandingAR = (string) Invoice::whereIn('status', ['UNPAID', 'PARTIAL'])->sum('patient_payable');
            $outstandingAP = (string) PurchaseBill::whereIn('status', ['UNPAID', 'PARTIAL'])->sum('total_amount');

            // ── Payer Source breakdown (for Donut chart) ──
            $philhealthStats   = PhilhealthClaim::query()
                ->selectRaw('COALESCE(SUM(total_case_rate_amount), 0) as total, COUNT(*) as count')
                ->first();
            $philhealthTotal   = (string) ($philhealthStats->total ?? '0.0000');
            $philhealthCount   = (int) ($philhealthStats->count ?? 0);

            $glStats           = GuaranteeLetter::query()
                ->selectRaw('COALESCE(SUM(authorized_amount), 0) as total_auth, COALESCE(SUM(utilized_amount), 0) as total_util, COALESCE(SUM(remaining_amount), 0) as total_rem, COUNT(*) as count')
                ->first();
            $malasakitGlTotal    = (string) ($glStats->total_auth ?? '0.0000');
            $malasakitGlUtilized = (string) ($glStats->total_util ?? '0.0000');
            $malasakitRemaining  = (string) ($glStats->total_rem ?? '0.0000');
            $malasakitCount      = (int) ($glStats->count ?? 0);

            $copayStats        = Payment::query()
                ->selectRaw("COALESCE(SUM(amount), 0) as total, COUNT(*) as count, COALESCE(SUM(CASE WHEN payment_method = 'CASH' THEN amount ELSE 0 END), 0) as cash_total")
                ->first();
            $directCopayTotal  = (string) ($copayStats->total ?? '0.0000');
            $directCopayCount  = (int) ($copayStats->count ?? 0);
            $totalCashCollections = (string) ($copayStats->cash_total ?? '0.0000');

            // Private HMO / Insurance coverage deducted from patient bills
            $hmoTotal = (string) Invoice::query()
                ->selectRaw('COALESCE(SUM(insurance_covered), 0) as total')
                ->where('insurance_covered', '>', 0)
                ->value('total');

            // Compute payer share percentages using bcmath
            $totalFundSources = bcadd(bcadd($philhealthTotal, $malasakitGlUtilized, 4), bcadd($directCopayTotal, $hmoTotal, 4), 4);

            $philhealthSharePct  = bccomp($totalFundSources, '0.0000', 4) > 0
                ? round((float) bcdiv($philhealthTotal, $totalFundSources, 6) * 100, 1)
                : 0.0;
            $malasakitSharePct   = bccomp($totalFundSources, '0.0000', 4) > 0
                ? round((float) bcdiv($malasakitGlUtilized, $totalFundSources, 6) * 100, 1)
                : 0.0;
            $hmoSharePct         = bccomp($totalFundSources, '0.0000', 4) > 0
                ? round((float) bcdiv($hmoTotal, $totalFundSources, 6) * 100, 1)
                : 0.0;
            $directCopaySharePct = bccomp($totalFundSources, '0.0000', 4) > 0
                ? round((float) bcdiv($directCopayTotal, $totalFundSources, 6) * 100, 1)
                : 0.0;

            // ── COA Circular 2021-014: Intact Deposit Compliance ──
            $depositStats       = BankDeposit::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('DEPOSITED', 'RECONCILED') THEN total_deposited ELSE 0 END), 0) as deposited, COALESCE(SUM(CASE WHEN status = 'PREPARED' THEN total_deposited ELSE 0 END), 0) as pending")
                ->first();
            $totalDeposited          = (string) ($depositStats->deposited ?? '0.0000');
            $pendingDeposits         = (string) ($depositStats->pending ?? '0.0000');
            $undepositedCollections  = bccomp($totalCashCollections, $totalDeposited, 4) > 0
                ? bcsub($totalCashCollections, $totalDeposited, 4)
                : '0.0000';
            $isCoaIntactCompliant    = bccomp($undepositedCollections, '10000.0000', 4) <= 0;

            return [
                'totalRevenue'           => (float) $pnl['total_revenue'],
                'totalExpense'           => (float) $pnl['total_expense'],
                'netIncome'              => (float) $pnl['net_income'],
                'cashOnHand'             => (float) $cashOnHand,
                'cashInBank'             => (float) $cashInBank,
                'outstandingAR'          => (float) $outstandingAR,
                'outstandingAP'          => (float) $outstandingAP,
                'isBalanced'             => $balanceSheet['is_balanced'],
                // Payer donut chart data
                'philhealthTotal'        => (float) $philhealthTotal,
                'philhealthCount'        => $philhealthCount,
                'malasakitGlUtilized'    => (float) $malasakitGlUtilized,
                'malasakitCount'         => $malasakitCount,
                'hmoTotal'               => (float) $hmoTotal,
                'directCopayTotal'       => (float) $directCopayTotal,
                'directCopayCount'       => $directCopayCount,
                'totalFundSources'       => (float) $totalFundSources,
                'philhealthSharePct'     => $philhealthSharePct,
                'malasakitSharePct'      => $malasakitSharePct,
                'hmoSharePct'            => $hmoSharePct,
                'directCopaySharePct'    => $directCopaySharePct,
                // Treasury / COA compliance
                'totalCashCollections'   => (float) $totalCashCollections,
                'totalDeposited'         => (float) $totalDeposited,
                'pendingDeposits'        => (float) $pendingDeposits,
                'undepositedCollections' => (float) $undepositedCollections,
                'isCoaIntactCompliant'   => $isCoaIntactCompliant,
            ];
        }, 180);

        // ─── 2. Monthly Revenue vs Expense trend (last 6 months, live query) ────────
        // Pull monthly aggregates from JournalEntryLine joined to JournalEntry + Account
        $revenueByMonth  = $this->getMonthlyAmountByAccountCategory('REVENUE', 6);
        $expenseByMonth  = $this->getMonthlyAmountByAccountCategory('EXPENSE', 6);

        // Build aligned 6-month arrays for Chart.js
        $months        = [];
        $revenueChart  = [];
        $expenseChart  = [];

        for ($i = 5; $i >= 0; $i--) {
            $month          = now()->subMonths($i);
            $key            = $month->format('Y-m');
            $months[]       = $month->format('M Y');
            $revenueChart[] = round((float) ($revenueByMonth[$key] ?? 0), 2);
            $expenseChart[] = round((float) ($expenseByMonth[$key] ?? 0), 2);
        }

        // ─── 3. Recent Journal Postings (live, not cached — must reflect latest state) ─
        $recentJournals = JournalEntry::with(['lines.account'])
            ->latest('id')
            ->limit(5)
            ->get();

        // ─── 4. Security Telemetry (single-trip, live) ────────────────────────────────
        $acknowledgedAt = session('security_alert_acknowledged_at');

        $activityStats = ActivityLog::query()
            ->whereDate('created_at', now()->toDateString())
            ->selectRaw("
                COUNT(CASE WHEN event = 'failed_login' THEN 1 END) as failed_logins,
                COUNT(CASE WHEN event IN ('created', 'updated', 'deleted', 'posted', 'reversed') THEN 1 END) as mutations
            ")
            ->first();

        $failedLoginsToday = (int) ($activityStats->failed_logins ?? 0);
        $mutationsToday    = (int) ($activityStats->mutations ?? 0);

        // Check if there are failed logins that occurred after user's last acknowledgment
        $unacknowledgedFailedLogins = $failedLoginsToday;
        if ($acknowledgedAt) {
            $unacknowledgedFailedLogins = ActivityLog::where('event', 'failed_login')
                ->whereDate('created_at', now()->toDateString())
                ->where('created_at', '>', $acknowledgedAt)
                ->count();
        }

        // Security alert level: 0 = green, 1 = amber (pending ws), 2 = rose (unacknowledged failed logins >= 5)
        $securityAlertLevel = match (true) {
            $unacknowledgedFailedLogins >= 5 => 2,
            $unacknowledgedFailedLogins > 0  => 1,
            default                          => 0,
        };


        $recentAuditLogs = ActivityLog::query()
            ->latest('id')
            ->limit(5)
            ->get();

        // ─── 5. Departmental Fiscal Budgets (live) ──────────────────────────────────
        $budgetAllocations = BudgetAllocation::where('fiscal_year', '2026')->get();
        $totalBudgetAllocated = '0.0000';
        $totalBudgetSpent = '0.0000';

        foreach ($budgetAllocations as $budget) {
            $totalBudgetAllocated = bcadd($totalBudgetAllocated, (string) $budget->allocated_amount, 4);
            $totalBudgetSpent     = bcadd($totalBudgetSpent, (string) $budget->spent_amount, 4);
        }

        $overallBurnRate = bccomp($totalBudgetAllocated, '0.0000', 4) > 0
            ? round((float) bcdiv($totalBudgetSpent, $totalBudgetAllocated, 4) * 100, 1)
            : 0.0;

        $criticalBudgets = $budgetAllocations->filter(function (BudgetAllocation $budget) {
            if (bccomp((string) $budget->allocated_amount, '0.0000', 4) <= 0) {
                return false;
            }
            $pct = (float) bcdiv((string) $budget->spent_amount, (string) $budget->allocated_amount, 4) * 100;
            return $pct >= 85.0;
        });

        // ─── 6. Executive Hospital Financial & Operational Scorecard ─────────────────
        $scorecard = $scorecardService->computeScorecard(
            metrics: array_merge($financialMetrics, [
                'overallBurnRate' => $overallBurnRate,
            ]),
            criticalBudgets: $criticalBudgets,
        );

        // Pre-compute hybrid KPI-Scorecard helper properties
        $revStr = number_format((float) $financialMetrics['totalRevenue'], 4, '.', '');
        $netStr = number_format((float) $financialMetrics['netIncome'], 4, '.', '');
        $netMarginPct = bccomp($revStr, '0.0000', 4) > 0
            ? round((float) bcdiv($netStr, $revStr, 6) * 100, 1)
            : 0.0;

        $cashTotalStr = bcadd(
            number_format((float) $financialMetrics['cashOnHand'], 4, '.', ''),
            number_format((float) $financialMetrics['cashInBank'], 4, '.', ''),
            4
        );
        $apStr = number_format((float) $financialMetrics['outstandingAP'], 4, '.', '');
        $hasAp = bccomp($apStr, '0.0000', 4) > 0;
        $liquidityCoverage = $hasAp
            ? round((float) bcdiv($cashTotalStr, $apStr, 4), 2)
            : 99.9;

        // ─── 7. User Context (request-scoped) ────────────────────────────────────────
        $currentUser = auth()->user();

        return view('accounting.dashboard', array_merge($financialMetrics, [
            // Scorecard & Hybrid KPI properties
            'scorecard'            => $scorecard,
            'netMarginPct'         => $netMarginPct,
            'liquidityCoverage'    => $liquidityCoverage,
            'hasAp'                => $hasAp,
            // Chart data
            'chartMonths'   => json_encode($months, JSON_THROW_ON_ERROR),
            'chartRevenue'  => json_encode($revenueChart, JSON_THROW_ON_ERROR),
            'chartExpense'  => json_encode($expenseChart, JSON_THROW_ON_ERROR),
            // Payer donut labels and values
            'payerLabels'   => json_encode(['PhilHealth ACR', 'Malasakit/GL', 'Private HMO', 'Direct Cash'], JSON_THROW_ON_ERROR),
            'payerValues'   => json_encode([
                round($financialMetrics['philhealthSharePct'], 1),
                round($financialMetrics['malasakitSharePct'], 1),
                round($financialMetrics['hmoSharePct'], 1),
                round($financialMetrics['directCopaySharePct'], 1),
            ], JSON_THROW_ON_ERROR),
            // Journals and security
            'recentJournals'     => $recentJournals,
            'recentAuditLogs'    => $recentAuditLogs,
            'failedLoginsToday'  => $failedLoginsToday,
            'mutationsToday'     => $mutationsToday,
            'securityAlertLevel' => $securityAlertLevel,
            'currentUser'        => $currentUser,
            // Budget metrics
            'budgetAllocations'    => $budgetAllocations,
            'totalBudgetAllocated' => (float) $totalBudgetAllocated,
            'totalBudgetSpent'     => (float) $totalBudgetSpent,
            'overallBurnRate'      => $overallBurnRate,
            'criticalBudgets'      => $criticalBudgets,
        ]));
    }

    /**
     * Retrieve monthly credited/debited totals for a given Account category
     * (REVENUE or EXPENSE) for the last N months, keyed by 'YYYY-MM'.
     *
     * @return array<string, string>  ['2026-04' => '123456.7800', ...]
     */
    private function getMonthlyAmountByAccountCategory(string $category, int $months): array
    {
        $since = now()->subMonths($months - 1)->startOfMonth();

        $column  = $category === 'REVENUE' ? 'credit' : 'debit';
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', journal_entries.entry_date)",
            'pgsql'  => "TO_CHAR(journal_entries.entry_date, 'YYYY-MM')",
            default  => "DATE_FORMAT(journal_entries.entry_date, '%Y-%m')",
        };

        $results = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'POSTED')
            ->where('accounts.category', $category)
            ->where('journal_entries.entry_date', '>=', $since->toDateString())
            ->selectRaw("{$dateExpr} as month_key, COALESCE(SUM(journal_entry_lines.{$column}), 0) as total")
            ->groupByRaw($dateExpr)
            ->orderBy('month_key')
            ->pluck('total', 'month_key')
            ->toArray();

        return $results;
    }
}
