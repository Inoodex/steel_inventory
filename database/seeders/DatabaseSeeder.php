<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermissionSeeder::class,
            // CompanyDetailSeeder::class,
            // WarehouseSeeder::class,
            // VendorSeeder::class,
            // CustomerSeeder::class,
            // ChartOfAccountSeeder::class,
            // BankDetailSeeder::class,
        ]);
    }
}
