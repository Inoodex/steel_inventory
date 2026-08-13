<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Tables to safely drop
        $tablesToDrop = [
            'project_costs',
            'project_items',
            'cost_categories',
            'projects',
            'bill_items',
            'bills',
            'challan_items',
            'challans',
            'quotation_items',
            'quotations',
            'clients',
            'brands',
            'categories',
        ];

        foreach ($tablesToDrop as $table) {
            Schema::dropIfExists($table);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
