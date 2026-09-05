<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name'           => 'Main Yard 01 (Dhaka)',
                'code'           => 'YARD-BHT-01',
                'location'       => 'Postogola, Dhaka',
                'contact_person' => 'Md. Rafiqul Islam (Yard In-charge)',
                'contact_phone'  => '+880 1800-112233',
                'capacity_ton'   => 15000.000,
                'status'         => 'active',
                'notes'          => 'Primary heavy plate and whole ship-breaking scrap yard with 50-ton gantry crane.',
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(
                ['code' => $wh['code']],
                $wh
            );
        }
    }
}
