<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $columnsToDrop = [];

                foreach (['client_id', 'product_id', 'sale_type', 'project_id'] as $column) {
                    if (Schema::hasColumn('sales', $column)) {
                        $columnsToDrop[] = $column;
                    }
                }

                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'client_id')) {
                    $table->unsignedBigInteger('client_id')->nullable()->after('customer_id');
                }
                if (!Schema::hasColumn('sales', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->after('client_id');
                }
                if (!Schema::hasColumn('sales', 'sale_type')) {
                    $table->string('sale_type')->default('retail')->after('product_id');
                }
                if (!Schema::hasColumn('sales', 'project_id')) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('sale_type');
                }
            });
        }
    }
};
