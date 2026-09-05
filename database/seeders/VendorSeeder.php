<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            [
                'name'            => 'Abul Khair Steel Mills (AKS Heavy Scrap)',
                'company'         => 'Abul Khair Group',
                'phone'           => '+880 1841-567890',
                'email'           => 'steel@abulkhairgroup.com',
                'address'         => 'Sitakunda, Chattogram',
                'bin_number'      => '004567890-0404',
                'tin_number'      => '781245670123',
                'opening_balance' => 0.00,
                'status'          => '1',
            ],
        ];

        foreach ($vendors as $v) {
            Vendor::firstOrCreate(
                ['phone' => $v['phone']],
                $v
            );
        }
    }
}
