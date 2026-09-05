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
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'delivery_charge')) {
                $table->decimal('delivery_charge', 15, 2)->default(0.00)->after('due');
            }
            if (!Schema::hasColumn('purchases', 'labour_cost')) {
                $table->decimal('labour_cost', 15, 2)->default(0.00)->after('delivery_charge');
            }
            if (!Schema::hasColumn('purchases', 'weight_scale_cost')) {
                $table->decimal('weight_scale_cost', 15, 2)->default(0.00)->after('labour_cost');
            }
            if (!Schema::hasColumn('purchases', 'other_charges')) {
                $table->decimal('other_charges', 15, 2)->default(0.00)->after('weight_scale_cost');
            }
            if (!Schema::hasColumn('purchases', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0.00)->after('other_charges');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $cols = ['delivery_charge', 'labour_cost', 'weight_scale_cost', 'other_charges', 'discount'];
            $existing = array_filter($cols, fn($c) => Schema::hasColumn('purchases', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
