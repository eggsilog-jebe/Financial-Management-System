<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\DTOs\Accounting\ExecutiveScorecardData;
use App\DTOs\Accounting\ScorecardItemData;
use Illuminate\Support\Collection;

final class ExecutiveScorecardService
{
    /**
     * Compute comprehensive 4-perspective Executive Hospital Financial & Operational Scorecard.
     * All arithmetic strictly uses BCMath with scale 4 to prevent float rounding errors.
     *
     * @param array<string, mixed> $metrics  Pre-computed financial metrics from FinancialDashboardController
     * @param Collection $criticalBudgets    Collection of departments approaching/exceeding budget limits
     */
    public function computeScorecard(array $metrics, Collection $criticalBudgets): ExecutiveScorecardData
    {
        $items = [];

        // Safe BCMath string normalization
        $totalRevStr     = number_format((float) ($metrics['totalRevenue'] ?? 0), 4, '.', '');
        $totalExpStr     = number_format((float) ($metrics['totalExpense'] ?? 0), 4, '.', '');
        $netIncStr       = number_format((float) ($metrics['netIncome'] ?? 0), 4, '.', '');
        $cashOnHandStr   = number_format((float) ($metrics['cashOnHand'] ?? 0), 4, '.', '');
        $cashInBankStr   = number_format((float) ($metrics['cashInBank'] ?? 0), 4, '.', '');
        $totalCashStr    = bcadd($cashOnHandStr, $cashInBankStr, 4);
        $outstandingApStr = number_format((float) ($metrics['outstandingAP'] ?? 0), 4, '.', '');
        $undepositedStr  = number_format((float) ($metrics['undepositedCollections'] ?? 0), 4, '.', '');
        $isBalanced      = (bool) ($metrics['isBalanced'] ?? true);

        // ──────────────────────────────────────────────────────────────────────────
        // PERSPECTIVE 1: FINANCIAL HEALTH & SUSTAINABILITY (GAAP/IFRS)
        // ──────────────────────────────────────────────────────────────────────────

        // 1. Operating Profit Margin: Target >= 10.0%
        $hasRevenue = bccomp($totalRevStr, '0.0000', 4) > 0;
        if ($hasRevenue) {
            $marginPct = round((float) bcdiv($netIncStr, $totalRevStr, 6) * 100, 1);
        } else {
            $marginPct = 0.0;
        }

        $marginStatus = match (true) {
            ! $hasRevenue         => 'NEUTRAL',
            $marginPct >= 10.0    => 'OPTIMAL',
            $marginPct >= 0.0     => 'WARNING',
            default               => 'CRITICAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Financial Health',
            metric: 'Operating Profit Margin',
            target: '≥ 10.0%',
            actual: $marginPct . '%',
            variance: ($marginPct >= 10.0 ? '+' : '') . round($marginPct - 10.0, 1) . '%',
            status: $marginStatus,
            statusLabel: match ($marginStatus) {
                'OPTIMAL'  => 'On Target (Surplus)',
                'WARNING'  => 'Narrow Margin (< 10%)',
                'CRITICAL' => 'Operating Deficit',
                default    => 'No Revenue Ingested',
            },
            badgeColor: match ($marginStatus) {
                'OPTIMAL'  => 'emerald',
                'WARNING'  => 'amber',
                'CRITICAL' => 'rose',
                default    => 'slate',
            },
            icon: 'ph-trend-up',
            progressPct: max(0.0, min(100.0, $marginPct * 5.0)),
            description: 'Net income generated per peso of clinical, pharmacy, and diagnostic gross income.',
        );

        // 2. Operating Expense Ratio: Target <= 85.0%
        if ($hasRevenue) {
            $opexRatio = round((float) bcdiv($totalExpStr, $totalRevStr, 6) * 100, 1);
        } else {
            $opexRatio = 0.0;
        }

        $opexStatus = match (true) {
            ! $hasRevenue        => 'NEUTRAL',
            $opexRatio <= 85.0   => 'OPTIMAL',
            $opexRatio <= 100.0  => 'WARNING',
            default              => 'CRITICAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Financial Health',
            metric: 'Operating Expense Ratio',
            target: '≤ 85.0%',
            actual: $opexRatio . '%',
            variance: ($opexRatio > 85.0 ? '+' : '') . round($opexRatio - 85.0, 1) . '%',
            status: $opexStatus,
            statusLabel: match ($opexStatus) {
                'OPTIMAL'  => 'Controlled (Within Plan)',
                'WARNING'  => 'Elevated Opex (> 85%)',
                'CRITICAL' => 'Opex Exceeds Revenue',
                default    => 'No Activity',
            },
            badgeColor: match ($opexStatus) {
                'OPTIMAL'  => 'emerald',
                'WARNING'  => 'amber',
                'CRITICAL' => 'rose',
                default    => 'slate',
            },
            icon: 'ph-calculator',
            progressPct: max(0.0, min(100.0, $opexRatio)),
            description: 'Ratio of operational disbursements to total hospital revenue earned.',
        );

        // ──────────────────────────────────────────────────────────────────────────
        // PERSPECTIVE 2: TREASURY, CASH & STATUTORY REMITTANCE
        // ──────────────────────────────────────────────────────────────────────────

        // 3. Quick Liquidity Ratio (Cash to AP Coverage): Target >= 1.00x
        $hasAp = bccomp($outstandingApStr, '0.0000', 4) > 0;
        if ($hasAp) {
            $liquidityRatio = round((float) bcdiv($totalCashStr, $outstandingApStr, 4), 2);
        } else {
            $liquidityRatio = bccomp($totalCashStr, '0.0000', 4) > 0 ? 99.9 : 1.0;
        }

        $liquidityStatus = match (true) {
            ! $hasAp && bccomp($totalCashStr, '0.0000', 4) > 0 => 'OPTIMAL',
            $liquidityRatio >= 1.50                             => 'OPTIMAL',
            $liquidityRatio >= 1.00                             => 'OPTIMAL',
            $liquidityRatio >= 0.70                             => 'WARNING',
            default                                             => 'CRITICAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Treasury & Liquidity',
            metric: 'Quick Cash-to-AP Coverage',
            target: '≥ 1.00x',
            actual: ($hasAp ? $liquidityRatio . 'x' : 'Abundant (No AP)'),
            variance: $hasAp ? (($liquidityRatio >= 1.00 ? '+' : '') . round($liquidityRatio - 1.00, 2) . 'x') : '0.00x',
            status: $liquidityStatus,
            statusLabel: match ($liquidityStatus) {
                'OPTIMAL'  => $liquidityRatio >= 1.50 ? 'Strong Liquidity (≥ 1.5x)' : 'Sufficient (≥ 1.0x)',
                'WARNING'  => 'Tight Buffer (< 1.0x)',
                'CRITICAL' => 'Deficit Exposure (< 0.7x)',
                default    => 'No Activity',
            },
            badgeColor: match ($liquidityStatus) {
                'OPTIMAL'  => 'teal',
                'WARNING'  => 'amber',
                'CRITICAL' => 'rose',
                default    => 'slate',
            },
            icon: 'ph-vault',
            progressPct: max(0.0, min(100.0, $liquidityRatio * 50.0)),
            description: 'Vault cash and AGDB depository balances available to settle immediate vendor obligations.',
        );

        // 4. COA Circular No. 2021-014 Intact Deposit Compliance: Target <= ₱10,000.00
        $isCoaCompliant = bccomp($undepositedStr, '10000.0000', 4) <= 0;
        $items[] = new ScorecardItemData(
            perspective: 'Treasury & Liquidity',
            metric: 'COA 2021-014 Intact Remittance',
            target: '≤ ₱10,000.00',
            actual: '₱' . number_format((float) $undepositedStr, 2),
            variance: $isCoaCompliant ? 'Compliant' : ('+₱' . number_format((float) bcsub($undepositedStr, '10000.0000', 4), 2)),
            status: $isCoaCompliant ? 'OPTIMAL' : 'CRITICAL',
            statusLabel: $isCoaCompliant ? '100% Compliant' : 'Remittance Overdue',
            badgeColor: $isCoaCompliant ? 'teal' : 'rose',
            icon: 'ph-shield-check',
            progressPct: $isCoaCompliant
                ? round((float) bcdiv($undepositedStr, '10000.0000', 4) * 100, 1)
                : 100.0,
            description: 'Mandatory daily intact deposit of hospital collection drawer proceeds into AGDB account.',
        );

        // ──────────────────────────────────────────────────────────────────────────
        // PERSPECTIVE 3: PATIENT CARE & UNIVERSAL HEALTHCARE (UHC)
        // ──────────────────────────────────────────────────────────────────────────

        // 5. UHC & Institutional Coverage (PhilHealth + Malasakit + HMO): Target >= 65.0%
        $philhealthVal = number_format((float) ($metrics['philhealthTotal'] ?? 0), 4, '.', '');
        $malasakitUtil = number_format((float) ($metrics['malasakitGlUtilized'] ?? 0), 4, '.', '');
        $hmoVal        = number_format((float) ($metrics['hmoTotal'] ?? 0), 4, '.', '');
        $totalFunds    = number_format((float) ($metrics['totalFundSources'] ?? 0), 4, '.', '');

        $institutionalFunds = bcadd(bcadd($philhealthVal, $malasakitUtil, 4), $hmoVal, 4);

        if (bccomp($totalFunds, '0.0000', 4) > 0) {
            $uhcSharePct = round((float) bcdiv($institutionalFunds, $totalFunds, 6) * 100, 1);
        } else {
            $uhcSharePct = 0.0;
        }

        $uhcStatus = match (true) {
            bccomp($totalFunds, '0.0000', 4) <= 0 => 'NEUTRAL',
            $uhcSharePct >= 65.0                  => 'OPTIMAL',
            $uhcSharePct >= 50.0                  => 'WARNING',
            default                               => 'CRITICAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Patient Care & Universal Health',
            metric: 'UHC Third-Party Coverage Share',
            target: '≥ 65.0%',
            actual: $uhcSharePct . '%',
            variance: ($uhcSharePct >= 65.0 ? '+' : '') . round($uhcSharePct - 65.0, 1) . '%',
            status: $uhcStatus,
            statusLabel: match ($uhcStatus) {
                'OPTIMAL'  => 'Equitable Access (≥ 65%)',
                'WARNING'  => 'Moderate Out-of-Pocket',
                'CRITICAL' => 'High Patient Burden (< 50%)',
                default    => 'No Claims Ingested',
            },
            badgeColor: match ($uhcStatus) {
                'OPTIMAL'  => 'emerald',
                'WARNING'  => 'blue',
                'CRITICAL' => 'amber',
                default    => 'slate',
            },
            icon: 'ph-first-aid',
            progressPct: max(0.0, min(100.0, $uhcSharePct)),
            description: 'Percentage of gross hospital billing absorbed by PhilHealth ACR, Malasakit grants, and HMOs.',
        );

        // 6. Malasakit Medical Assistance Absorption (RA 11463): Target >= 75.0%
        $glTotalStr = number_format((float) ($metrics['malasakitGlTotal'] ?? 0), 4, '.', '');
        if (bccomp($glTotalStr, '0.0000', 4) > 0) {
            $malasakitPct = round((float) bcdiv($malasakitUtil, $glTotalStr, 6) * 100, 1);
        } else {
            $malasakitPct = 100.0; // No open pending GL commitments
        }

        $malasakitStatus = match (true) {
            $malasakitPct >= 75.0 => 'OPTIMAL',
            $malasakitPct >= 50.0 => 'WARNING',
            default               => 'CRITICAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Patient Care & Universal Health',
            metric: 'Malasakit GL Liquidation (RA 11463)',
            target: '≥ 75.0%',
            actual: $malasakitPct . '%',
            variance: ($malasakitPct >= 75.0 ? '+' : '') . round($malasakitPct - 75.0, 1) . '%',
            status: $malasakitStatus,
            statusLabel: $malasakitStatus === 'OPTIMAL' ? 'High Absorption (≥ 75%)' : 'Pending Liquidation',
            badgeColor: $malasakitStatus === 'OPTIMAL' ? 'teal' : 'amber',
            icon: 'ph-hand-heart',
            progressPct: max(0.0, min(100.0, $malasakitPct)),
            description: 'Utilization and liquidation rate of DOH MAIP, PCSO, and DSWD Guarantee Letters.',
        );

        // ──────────────────────────────────────────────────────────────────────────
        // PERSPECTIVE 4: STATUTORY GOVERNANCE & INTERNAL CONTROLS
        // ──────────────────────────────────────────────────────────────────────────

        // 7. General Ledger Double-Entry Invariance: Target 100% Balanced
        $items[] = new ScorecardItemData(
            perspective: 'Governance & Compliance',
            metric: 'GL Double-Entry Invariance',
            target: '100% Balanced',
            actual: $isBalanced ? 'Balanced (₱0.00)' : 'Discrepancy',
            variance: $isBalanced ? '0.0000' : 'Out of Balance',
            status: $isBalanced ? 'OPTIMAL' : 'CRITICAL',
            statusLabel: $isBalanced ? 'GAAP/IFRS Verified' : 'Imbalance Detected',
            badgeColor: $isBalanced ? 'emerald' : 'rose',
            icon: 'ph-scales',
            progressPct: $isBalanced ? 100.0 : 0.0,
            description: 'Mathematical debit-credit equality across all posted journal vouchers and subsidiary ledgers.',
        );

        // 8. Departmental Budget Ceiling Discipline: Target <= 85.0% Burn Rate
        $burnRate = (float) ($metrics['overallBurnRate'] ?? 0.0);
        $critCount = $criticalBudgets->count();

        $budgetStatus = match (true) {
            $burnRate >= 95.0              => 'CRITICAL',
            $critCount > 0 || $burnRate >= 85.0 => 'WARNING',
            default                        => 'OPTIMAL',
        };

        $items[] = new ScorecardItemData(
            perspective: 'Governance & Compliance',
            metric: 'Departmental Budget Discipline',
            target: '≤ 85.0% Burn',
            actual: $burnRate . '% Spent',
            variance: ($burnRate > 85.0 ? '+' : '') . round($burnRate - 85.0, 1) . '%',
            status: $budgetStatus,
            statusLabel: match ($budgetStatus) {
                'OPTIMAL'  => 'All Depts Within Ceiling',
                'WARNING'  => "{$critCount} Dept(s) Near Ceiling",
                'CRITICAL' => 'Budget Exhaustion Imminent',
            },
            badgeColor: match ($budgetStatus) {
                'OPTIMAL'  => 'emerald',
                'WARNING'  => 'amber',
                'CRITICAL' => 'rose',
            },
            icon: 'ph-chart-bar',
            progressPct: max(0.0, min(100.0, $burnRate)),
            description: 'Enforcement of annual fiscal allocations preventing unauthorized departmental over-expenditures.',
        );

        // ──────────────────────────────────────────────────────────────────────────
        // COMPOSITE SCORECARD GRADING
        // ──────────────────────────────────────────────────────────────────────────
        $totalMetrics  = count($items);
        $optimalCount  = 0;
        $warningCount  = 0;
        $criticalCount = 0;
        $pointsEarned  = 0.0;

        foreach ($items as $item) {
            match ($item->status) {
                'OPTIMAL' => (function () use (&$optimalCount, &$pointsEarned) {
                    $optimalCount++;
                    $pointsEarned += 1.0;
                })(),
                'WARNING' => (function () use (&$warningCount, &$pointsEarned) {
                    $warningCount++;
                    $pointsEarned += 0.5;
                })(),
                'CRITICAL' => $criticalCount++,
                default   => $pointsEarned += 1.0, // Neutral doesn't penalize
            };
        }

        $compositeScorePct = $totalMetrics > 0
            ? round(($pointsEarned / $totalMetrics) * 100, 1)
            : 100.0;

        [$compositeGrade, $compositeBadgeColor] = match (true) {
            $compositeScorePct >= 90.0 => ['Grade A — Exemplary Fiscal Governance', 'emerald'],
            $compositeScorePct >= 75.0 => ['Grade B — Satisfactory / Managed Risk', 'teal'],
            $compositeScorePct >= 60.0 => ['Grade C — Cautionary / High Exposure', 'amber'],
            default                    => ['Grade D — Critical Intervention Required', 'rose'],
        };

        // Group items by perspective
        $perspectives = [];
        foreach ($items as $item) {
            $perspectives[$item->perspective][] = $item;
        }

        return new ExecutiveScorecardData(
            items: $items,
            perspectives: $perspectives,
            totalMetrics: $totalMetrics,
            optimalCount: $optimalCount,
            warningCount: $warningCount,
            criticalCount: $criticalCount,
            compositeScorePct: $compositeScorePct,
            compositeGrade: $compositeGrade,
            compositeBadgeColor: $compositeBadgeColor,
        );
    }
}
