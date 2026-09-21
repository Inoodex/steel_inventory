<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_items') && !Schema::hasColumn('sales_items', 'custom_size')) {
            Schema::table('sales_items', function (Blueprint $table) {
                $table->string('custom_size')->nullable()->after('size_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_items') && Schema::hasColumn('sales_items', 'custom_size')) {
            Schema::table('sales_items', function (Blueprint $table) {
                $table->dropColumn('custom_size');
            });
        }
    }
};
