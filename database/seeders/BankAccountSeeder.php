<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Database\Seeder;

final class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $glAccount = Account::where('code', '1020')->first();

        BankAccount::firstOrCreate(
            ['account_number' => 'MBTC-OPER-001020'],
            [
                'name'            => 'Metrobank Main Operating Account',
                'bank_name'       => 'Metropolitan Bank & Trust Co.',
                'account_number'  => 'MBTC-OPER-001020',
                'gl_code'         => '1020',
                'gl_account_id'   => $glAccount?->id,
                'purpose'         => 'General Operating & Disbursements',
                'currency'        => 'PHP',
                'opening_balance' => 5000000.0000,
                'balance'         => 5000000.0000,
                'minimum_balance' => 50000.0000,
                'status'          => 'Active',
                'is_active'       => true,
            ]
        );

        BankAccount::firstOrCreate(
            ['account_number' => 'LBP-TREAS-003410'],
            [
                'name'            => 'Landbank Hospital Treasury & MAIP Account',
                'bank_name'       => 'Land Bank of the Philippines',
                'account_number'  => 'LBP-TREAS-003410',
                'gl_code'         => '1020',
                'gl_account_id'   => $glAccount?->id,
                'purpose'         => 'Government Subsidies & Vendor Settlements',
                'currency'        => 'PHP',
                'opening_balance' => 8500000.0000,
                'balance'         => 8500000.0000,
                'minimum_balance' => 100000.0000,
                'status'          => 'Active',
                'is_active'       => true,
            ]
        );

        BankAccount::firstOrCreate(
            ['account_number' => 'BDO-EFT-009941'],
            [
                'name'            => 'BDO Supplier Electronic Payout Account',
                'bank_name'       => 'BDO Unibank, Inc.',
                'account_number'  => 'BDO-EFT-009941',
                'gl_code'         => '1020',
                'gl_account_id'   => $glAccount?->id,
                'purpose'         => 'PESONet Direct Vendor Disbursements',
                'currency'        => 'PHP',
                'opening_balance' => 3200000.0000,
                'balance'         => 3200000.0000,
                'minimum_balance' => 50000.0000,
                'status'          => 'Active',
                'is_active'       => true,
            ]
        );

        BankAccount::firstOrCreate(
            ['account_number' => 'BPI-OPER-005510'],
            [
                'name'            => 'BPI Hospital Treasury & Commercial Payout Account',
                'bank_name'       => 'Bank of the Philippine Islands (BPI)',
                'account_number'  => 'BPI-OPER-005510',
                'gl_code'         => '1020',
                'gl_account_id'   => $glAccount?->id,
                'purpose'         => 'Commercial Vendor & Diagnostic Settlements',
                'currency'        => 'PHP',
                'opening_balance' => 4500000.0000,
                'balance'         => 4500000.0000,
                'minimum_balance' => 50000.0000,
                'status'          => 'Active',
                'is_active'       => true,
            ]
        );
    }
}
