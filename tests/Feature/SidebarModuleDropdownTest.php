<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SidebarModuleDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_contains_dropdowns_for_all_modules_and_user_security(): void
    {
        $user = User::factory()->create([
            'role' => 'CFO',
        ]);

        $this->actingAs($user);

        $html = Blade::render("@include('partials.sidebar')");

        // Verify all 7 modules have dropdown triggers in sidebar
        $this->assertStringContainsString('Accounts Payable', $html);
        $this->assertStringContainsString('Accounts Receivable', $html);
        $this->assertStringContainsString('Disbursement', $html);
        $this->assertStringContainsString('Collection', $html);
        $this->assertStringContainsString('Budget', $html);
        $this->assertStringContainsString('General Ledger', $html);
        $this->assertStringContainsString('User &amp; Security', $html);

        // Verify AP sub-items
        $this->assertStringContainsString('Vendor Directory', $html);
        $this->assertStringContainsString('Vendor Invoices &amp; Tax', $html);
        $this->assertStringContainsString('3-Way Verification / Bills', $html);
        $this->assertStringContainsString('Payable Aging', $html);
        $this->assertStringContainsString('Payment Approvals', $html);

        // Verify AR sub-items
        $this->assertStringContainsString('Invoicing &amp; Patient Billing', $html);
        $this->assertStringContainsString('Patient Accounts', $html);
        $this->assertStringContainsString('Credit Notes &amp; Adjustments', $html);
        $this->assertStringContainsString('Malasakit Subsidies (RA 11463)', $html);
        $this->assertStringContainsString('Receivable Aging Schedule', $html);
        $this->assertStringContainsString('Statements of Account (SOA)', $html);

        // Verify Disbursement sub-items
        $this->assertStringContainsString('Payment Vouchers (DV)', $html);
        $this->assertStringContainsString('Voucher Approvals &amp; Release', $html);
        $this->assertStringContainsString('Check Register &amp; Custody', $html);
        $this->assertStringContainsString('EFT Bank Transfers', $html);
        $this->assertStringContainsString('Petty Cash Custody Funds', $html);

        // Verify Collection sub-items
        $this->assertStringContainsString('Cashier POS Desk', $html);
        $this->assertStringContainsString('Payment Receipts (OR)', $html);
        $this->assertStringContainsString('Deposit Slip Batches', $html);
        $this->assertStringContainsString('Bank Deposits &amp; Clearing', $html);
        $this->assertStringContainsString('Payment Gateway Logs', $html);

        // Verify Budget sub-items
        $this->assertStringContainsString('Fiscal Year Planning', $html);
        $this->assertStringContainsString('Budget Allocation', $html);
        $this->assertStringContainsString('Departmental Budgets', $html);
        $this->assertStringContainsString('Variance Analysis', $html);
        $this->assertStringContainsString('Budget Reallocations', $html);

        // Verify GL sub-items
        $this->assertStringContainsString('Journal Entries', $html);
        $this->assertStringContainsString('Chart of Accounts (COA)', $html);
        $this->assertStringContainsString('Ledger Books', $html);
        $this->assertStringContainsString('Trial Balance', $html);
        $this->assertStringContainsString('Period-End Closing', $html);

        // Verify User & Security sub-items
        $this->assertStringContainsString('User Accounts', $html);
        $this->assertStringContainsString('Workstation Binding', $html);
        $this->assertStringContainsString('CAS Audit Trail', $html);

        // Verify caret rotation indicator and dynamic active module state
        $this->assertStringContainsString('rotate-180', $html);
        $this->assertStringContainsString("activeModule === 'ap'", $html);
        $this->assertStringContainsString("toggleModule('ap')", $html);
        $this->assertStringContainsString("toggleModule('ar')", $html);
    }

    public function test_submodule_nav_renders_empty_and_no_old_top_bar_exists(): void
    {
        $rendered = Blade::render("@include('partials.submodule-nav')");
        $this->assertEmpty(trim($rendered));
    }

    public function test_navigation_loading_modal_replaces_top_progress_bar(): void
    {
        $user = User::factory()->create([
            'role' => 'CFO',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/accounting/dashboard');

        $response->assertOk();
        // Top progress bar was replaced
        $response->assertDontSee('id="fms-top-progress"', false);
        $response->assertDontSee('id="fms-top-progress-bar"', false);

        // Navigation loading pop-up modal exists
        $response->assertSee('id="fms-navigation-loader"', false);
        $response->assertSee('id="fms-loader-backdrop"', false);
        $response->assertSee('id="fms-loader-card"', false);
        $response->assertSee('id="fms-loader-title"', false);
        $response->assertSee('id="fms-loader-desc"', false);
        $response->assertSee('fms-loader-indeterminate-bar', false);
    }
}
