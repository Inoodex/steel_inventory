<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name'            => 'Sharif Khan',
                'company'         => 'ABC Properties',
                'phone'           => '+880 1900-000000',
                'email'           => 'abc@properties.com',
                'address'         => 'Board Bazar, Gazipur, Dhaka',
                'bin_number'      => '003344556-0303',
                'tin_number'      => '456789012345',
                'opening_balance' => 0.00,
                'status'          => '1',
            ],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['phone' => $c['phone']],
                $c
            );
        }
    }
}
