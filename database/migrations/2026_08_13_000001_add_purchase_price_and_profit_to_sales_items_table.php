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
        Schema::table('sales_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_items', 'purchase_price')) {
                $table->decimal('purchase_price', 15, 2)->default(0.00)->after('total_price');
            }
            if (!Schema::hasColumn('sales_items', 'profit')) {
                $table->decimal('profit', 15, 2)->default(0.00)->after('purchase_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_items', 'profit')) {
                $table->dropColumn('profit');
            }
            if (Schema::hasColumn('sales_items', 'purchase_price')) {
                $table->dropColumn('purchase_price');
            }
        });
    }
};
