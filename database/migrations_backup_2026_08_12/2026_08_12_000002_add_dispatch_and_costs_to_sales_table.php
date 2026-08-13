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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('customer_id');
            }
            if (!Schema::hasColumn('sales', 'delivery_status')) {
                $table->string('delivery_status')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('sales', 'labour_cost')) {
                $table->decimal('labour_cost', 12, 2)->default(0.00)->after('delivery_charge');
            }
            if (!Schema::hasColumn('sales', 'weight_scale_cost')) {
                $table->decimal('weight_scale_cost', 12, 2)->default(0.00)->after('labour_cost');
            }
            if (!Schema::hasColumn('sales', 'other_charges')) {
                $table->decimal('other_charges', 12, 2)->default(0.00)->after('weight_scale_cost');
            }
            if (!Schema::hasColumn('sales', 'note')) {
                $table->text('note')->nullable()->after('other_charges');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'warehouse_id',
                'delivery_status',
                'labour_cost',
                'weight_scale_cost',
                'other_charges',
                'note',
            ]);
        });
    }
};
