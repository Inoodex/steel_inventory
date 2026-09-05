<?php

namespace Database\Seeders;

use App\Models\BankDetail;
use Illuminate\Database\Seeder;

class BankDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            [
                'bank_name'       => 'Islami Bank Bangladesh PLC',
                'account_name'    => 'Demo Account',
                'account_number'  => '20501234567890',
                'branch'          => 'Head Branch, Dhaka',
                'routing_number'  => '125150824',
                'swift_code'      => 'IBBLBDDH024',
                'account_type'    => 'current',
                'opening_balance' => 2500000.00,
                'current_balance' => 2500000.00,
                'currency'        => 'BDT',
                'is_default'      => true,
                'is_active'       => true,
                'status'          => 'active',
                'notes'           => 'Principal operating bank account for steel lot procurement and wire transfers.',
            ],
        ];

        foreach ($banks as $b) {
            $bank = BankDetail::firstOrCreate(
                ['account_number' => $b['account_number']],
                $b
            );

            // Ensure Chart of Account is linked
            $bank->resolveChartOfAccount();
        }
    }
}
