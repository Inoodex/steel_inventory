<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'hello@inoodex.com'],
            [
                'name' => 'Super Admin',
                'role' => 'Super Admin',
                'status' => '1',
                'password' => Hash::make('hello@inoodex.com'),
            ]
        );
    }
}
