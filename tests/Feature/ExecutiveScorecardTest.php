<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\Accounting\ExecutiveScorecardData;
use App\Models\BudgetAllocation;
use App\Models\User;
use App\Services\Accounting\ExecutiveScorecardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ExecutiveScorecardTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_scorecard_renders_on_financial_dashboard(): void
    {
        $cfo = User::factory()->create([
            'email' => 'cfo@hospital.gov.ph',
            'name'  => 'Chief Financial Officer',
            'role'  => 'CFO',
        ]);

        $response = $this->actingAs($cfo)->get(route('accounting.dashboard'));

        $response->assertSee('Executive Hospital Financial & Operational Scorecard');
        $response->assertSee('Operating Profit Margin');
        $response->assertSee('Operating Expense Ratio');
        $response->assertSee('Quick Cash-to-AP Coverage');
        $response->assertSee('COA 2021-014 Intact Remittance');
        $response->assertSee('UHC Third-Party Coverage Share');
        $response->assertSee('Malasakit GL Liquidation (RA 11463)');
        $response->assertSee('GL Double-Entry Invariance');
        $response->assertSee('Departmental Budget Discipline');
        $response->assertSee('Standards Met');
    }

    public function test_scorecard_service_evaluates_healthy_hospital_financials(): void
    {
        $service = new ExecutiveScorecardService();

        $metrics = [
            'totalRevenue'           => 1000000.00,
            'totalExpense'           => 800000.00,
            'netIncome'              => 200000.00, // 20.0% margin (Target >= 10%)
            'cashOnHand'             => 300000.00,
            'cashInBank'             => 700000.00,
            'outstandingAR'          => 150000.00,
            'outstandingAP'          => 500000.00, // Cash 1M vs AP 500k = 2.0x coverage (Target >= 1.0x)
            'isBalanced'             => true,
            'undepositedCollections' => 5000.00,   // <= ₱10,000 (Compliant)
            'philhealthTotal'        => 500000.00,
            'malasakitGlTotal'       => 200000.00,
            'malasakitGlUtilized'    => 180000.00, // 90% utilization (Target >= 75%)
            'hmoTotal'               => 150000.00,
            'directCopayTotal'       => 100000.00,
            'totalFundSources'       => 930000.00, // (500k + 180k + 150k) / 930k = 89.2% UHC (Target >= 65%)
            'overallBurnRate'        => 65.0,      // <= 85%
        ];

        $scorecard = $service->computeScorecard($metrics, collect());

        $this->assertInstanceOf(ExecutiveScorecardData::class, $scorecard);
        $this->assertSame(8, $scorecard->totalMetrics);
        $this->assertSame(8, $scorecard->optimalCount);
        $this->assertSame(0, $scorecard->warningCount);
        $this->assertSame(0, $scorecard->criticalCount);
        $this->assertEquals(100.0, $scorecard->compositeScorePct);
        $this->assertStringContainsString('Grade A', $scorecard->compositeGrade);
        $this->assertSame('emerald', $scorecard->compositeBadgeColor);
    }

    public function test_scorecard_service_correctly_flags_overdue_coa_and_budget_exhaustion(): void
    {
        $service = new ExecutiveScorecardService();

        $metrics = [
            'totalRevenue'           => 500000.00,
            'totalExpense'           => 600000.00, // Deficit
            'netIncome'              => -100000.00,
            'cashOnHand'             => 50000.00,
            'cashInBank'             => 100000.00,
            'outstandingAR'          => 200000.00,
            'outstandingAP'          => 400000.00, // 150k / 400k = 0.38x (< 0.7x critical)
            'isBalanced'             => false,     // Ledger imbalance
            'undepositedCollections' => 25000.00,  // > ₱10,000 (Overdue)
            'philhealthTotal'        => 100000.00,
            'malasakitGlTotal'       => 100000.00,
            'malasakitGlUtilized'    => 40000.00,  // 40% (< 50% critical)
            'hmoTotal'               => 50000.00,
            'directCopayTotal'       => 200000.00,
            'totalFundSources'       => 390000.00,
            'overallBurnRate'        => 96.5,      // >= 95% critical
        ];

        $criticalBudgets = collect([
            (object) ['department' => 'Emergency Room', 'allocated_amount' => 100000, 'spent_amount' => 96000],
        ]);

        $scorecard = $service->computeScorecard($metrics, $criticalBudgets);

        $this->assertGreaterThan(0, $scorecard->criticalCount);
        $this->assertLessThan(60.0, $scorecard->compositeScorePct);
        $this->assertStringContainsString('Grade D', $scorecard->compositeGrade);
        $this->assertSame('rose', $scorecard->compositeBadgeColor);
    }

    public function test_scorecard_handles_zero_activity_without_division_by_zero(): void
    {
        $service = new ExecutiveScorecardService();

        $metrics = [
            'totalRevenue'           => 0.00,
            'totalExpense'           => 0.00,
            'netIncome'              => 0.00,
            'cashOnHand'             => 0.00,
            'cashInBank'             => 0.00,
            'outstandingAR'          => 0.00,
            'outstandingAP'          => 0.00,
            'isBalanced'             => true,
            'undepositedCollections' => 0.00,
            'philhealthTotal'        => 0.00,
            'malasakitGlTotal'       => 0.00,
            'malasakitGlUtilized'    => 0.00,
            'hmoTotal'               => 0.00,
            'directCopayTotal'       => 0.00,
            'totalFundSources'       => 0.00,
            'overallBurnRate'        => 0.0,
        ];

        $scorecard = $service->computeScorecard($metrics, collect());

        $this->assertSame(8, $scorecard->totalMetrics);
        $this->assertSame(0, $scorecard->criticalCount);
    }
}
