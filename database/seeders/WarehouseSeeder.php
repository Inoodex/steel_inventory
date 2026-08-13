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
            [
                'name'           => 'Chittagong Mill Depot',
                'code'           => 'WH-CTG',
                'location'       => 'Port Connecting Road, Chittagong',
                'contact_person' => 'Depot Manager',
                'phone'          => '01700000002',
                'status'         => 'active',
            ],
            [
                'name'           => 'Dhaka Central Warehouse',
                'code'           => 'WH-DHK',
                'location'       => 'Tejgaon Industrial Area, Dhaka',
                'contact_person' => 'Disposal Officer',
                'phone'          => '01700000003',
                'status'         => 'active',
            ],
            [
                'name'           => 'Factory Rolling Mill Yard',
                'code'           => 'WH-MILL',
                'location'       => 'Rolling Mill Complex, Yard 3',
                'contact_person' => 'Mill Dispatch Supervisor',
                'phone'          => '01700000004',
                'status'         => 'active',
            ],
        ];

        foreach ($warehouses as $wh) {
            Warehouse::firstOrCreate(['code' => $wh['code']], $wh);
        }
    }
}
