<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Minimal Foundation Only (Master data, Chart of Accounts, Bank Accounts, System Users)
        $this->call([
            UserAndRoleSeeder::class,
            ChartOfAccountsSeeder::class,
            BankAccountSeeder::class,
            // To load mock demo encounters, invoices, and budgets, run manually:
            // php artisan db:seed --class=MalasakitGuaranteeLetterSeeder
            // php artisan db:seed --class=PublicHospitalFinancialDashboardSeeder
        ]);
    }
}

