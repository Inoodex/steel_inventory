<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->date('return_date');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('coil_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->decimal('return_weight', 12, 3)->default(0.000);
            $table->decimal('return_qty', 12, 3)->default(1.000);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('total_refund_amount', 15, 2)->default(0.00);
            $table->string('refund_type', 30)->default('reduce_due'); // reduce_due, advance_credit, cash_refund, bank_refund
            $table->string('payment_method', 30)->nullable(); // cash, bank, mobile_banking
            $table->unsignedBigInteger('bank_detail_id')->nullable();
            $table->string('transaction_ref', 255)->nullable();
            $table->string('reason', 100)->nullable(); // defective, spec_mismatch, damaged, excess_intake, other
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('completed'); // completed, pending, cancelled
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('coil_id')->references('id')->on('coils')->onDelete('set null');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
            $table->foreign('bank_detail_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['vendor_id', 'return_date']);
            $table->index(['purchase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};

