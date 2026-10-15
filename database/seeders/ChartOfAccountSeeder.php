<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds minimal standard 5-class Chart of Accounts and active Fiscal Year.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Ensure current active Fiscal Year
            $currentYear = date('Y');
            FiscalYear::firstOrCreate(
                ['is_active' => true],
                [
                    'year_name'  => "{$currentYear}-" . ($currentYear + 1),
                    'start_date' => "{$currentYear}-01-01",
                    'end_date'   => "{$currentYear}-12-31",
                    'is_active'  => true,
                    'is_closed'  => false,
                ]
            );

            // 2. Delegate to minimal system accounts generator
            ChartOfAccount::ensureSystemAccounts();
        });
    }
}
