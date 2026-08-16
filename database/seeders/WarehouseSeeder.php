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
                'name'           => 'Main Steel Yard (Central)',
                'code'           => 'WH-MAIN',
                'location'       => 'Plot 12, Industrial Area, Sector 7',
                'contact_person' => 'Yard In-Charge',
                'phone'          => '01700000001',
                'status'         => 'active',
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(['code' => $wh['code']], $wh);
        }
    }
}
