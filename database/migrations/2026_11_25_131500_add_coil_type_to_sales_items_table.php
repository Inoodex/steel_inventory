<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_items') && !Schema::hasColumn('sales_items', 'coil_type')) {
            Schema::table('sales_items', function (Blueprint $table) {
                $table->string('coil_type')->nullable()->default('split')->after('custom_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_items') && Schema::hasColumn('sales_items', 'coil_type')) {
            Schema::table('sales_items', function (Blueprint $table) {
                $table->dropColumn('coil_type');
            });
        }
    }
};
