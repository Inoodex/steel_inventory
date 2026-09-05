<?php

namespace Database\Seeders;

use App\Models\CompanyDetail;
use Illuminate\Database\Seeder;

class CompanyDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyDetail::updateOrCreate(
            ['company_name' => ' M/S SA Enterprise'],
            [
                'tagline'              => 'Test',
                'email'                => 'saenterprise8939@gmail.com',
                'phone'                => '+880 1800-000000',
                'alternate_phone'      => '+880 1700-000000',
                'address'              => 'Postogola, Dhaka',
                'city'                 => 'Dhaka',
                'state'                => 'Dhaka Division',
                'postal_code'          => '1204',
                'country'              => 'Bangladesh',
                'tax_number'           => 'TAX-DHK-2026-00',
                'bin_number'           => '0123456789-0101',
                'tin_number'           => '3456789012',
                'trade_license'        => 'TRAD/SITA/0001/2026',
                'website'              => '',
                'currency_symbol'      => '৳',
                'currency_code'        => 'BDT',
                'terms_and_conditions' => "1. Goods once sold and lifted from the yard cannot be taken back.\n2. Certified weighbridge gross and tare slips issued at the yard weighbridge shall be final.\n3. All payments must be cleared before dispatch or loading of vehicles.\n4. Demurrage and yard detention charges apply after 7 days of invoice.",
                'invoice_notes'        => 'Thank you for your business. Quality verified ship-breaking steel plates and coils.',
                'is_default'           => true,
            ]
        );
    }
}
