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
        Schema::create('coils', function (Blueprint $table) {
            $table->id();
            $table->string('coil_number')->unique();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('steel_type')->default('Ship Steel Coil'); // e.g. Ship Steel Coil, Marine MS Plate, Re-rollable Scrap, Profile
            $table->string('thickness')->nullable(); // e.g. 0.22mm, 12mm, 16mm
            $table->string('width')->nullable(); // e.g. 914mm, 5ft
            $table->string('length')->nullable(); // e.g. 20ft, Coil, 12m
            $table->decimal('gross_weight', 12, 3)->default(0.000); // Scale Gross Weight (Tons / KG)
            $table->decimal('tare_weight', 12, 3)->default(0.000); // Scale Tare / Packing Weight
            $table->decimal('net_weight', 12, 3)->default(0.000); // Net Billable Weight (Gross - Tare)
            $table->decimal('remaining_weight', 12, 3)->default(0.000); // Current Available Weight in Yard
            $table->decimal('rate_per_ton', 12, 2)->default(0.00); // Rate per Ton or Rate per KG
            $table->decimal('total_price', 12, 2)->default(0.00); // Total Price = Net Weight * Rate
            $table->enum('status', ['in_stock', 'in_processing', 'exhausted'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (!Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('vendor_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coils');
        if (Schema::hasColumn('purchases', 'warehouse_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
