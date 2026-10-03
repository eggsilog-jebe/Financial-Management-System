<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ResetTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fms:fresh-start {--force : Bypass confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all operational, billing, budget, and cashier transaction data for a fresh start while preserving users, chart of accounts, and workstation device bindings.';

    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  HOSPITAL FMS — FRESH TRANSACTION RESET            ');
        $this->info('====================================================');

        if (! $this->option('force') && ! $this->confirm('This will wipe all bills, payments, cashier shifts, budgets, and claims while keeping all users intact. Proceed?', true)) {
            $this->warn('Operation cancelled.');
            return self::SUCCESS;
        }

        $tables = [
            'payments',
            'bank_deposits',
            'cashier_shifts',
            'philhealth_claims',
            'guarantee_letters',
            'invoice_items',
            'credit_notes',
            'hmo_claims',
            'invoices',
            'patient_accounts',
            'budget_encumbrances',
            'budget_reallocations',
            'budget_allocations',
            'journal_entry_lines',
            'journal_entries',
            'disbursement_vouchers',
            'petty_cash_vouchers',
            'receiving_report_items',
            'receiving_reports',
            'purchase_order_items',
            'purchase_orders',
            'purchase_bills',
            'cas_audit_trails',
        ];

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        }

        $clearedCount = 0;
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->line("  ✓ Cleared <fg=cyan>{$table}</> ({$count} rows)");
                $clearedCount++;
            }
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // Clear application and view caches
        Cache::flush();
        $this->callSilent('view:clear');

        $this->newLine();
        $this->info('====================================================');
        $this->info('  ✓ SUCCESS: Operational database reset to 0!      ');
        $this->info('  ✓ Users, Workstations & Chart of Accounts intact. ');
        $this->info('====================================================');

        return self::SUCCESS;
    }
}
