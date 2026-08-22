<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('thickness')->nullable();
            $table->string('size')->nullable();
            $table->string('size_type')->default('inch');
            $table->decimal('unit_weight', 12, 3)->nullable();
            $table->decimal('total_weight', 12, 3)->nullable();
            $table->decimal('quantity', 12, 3)->default(1.000);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('sub_price', 15, 2)->default(0.00);
            $table->decimal('total_price', 15, 2);
            $table->decimal('payment', 15, 2)->default(0.00);
            $table->decimal('due', 15, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->unsignedBigInteger('bank_detail_id')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('status')->default('received');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('bank_detail_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('coils', function (Blueprint $table) {
            $table->id();
            $table->string('coil_number')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('thickness')->nullable();
            $table->string('width')->nullable();
            $table->string('length')->nullable();
            $table->decimal('gross_weight', 12, 3)->nullable();
            $table->decimal('tare_weight', 12, 3)->nullable();
            $table->decimal('net_weight', 12, 3);
            $table->decimal('remaining_weight', 12, 3);
            $table->integer('piece_count')->default(1);
            $table->decimal('rate_per_ton', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->enum('status', ['in_stock', 'reserved', 'processing', 'exhausted', 'scrapped'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('opening_stock', 12, 3)->default(0.000);
            $table->decimal('current_stock', 12, 3)->default(0.000);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('coils');
        Schema::dropIfExists('purchases');
    }
};