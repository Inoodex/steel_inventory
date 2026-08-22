<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('sales_by')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('order_no')->unique();
            $table->date('order_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('vat', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('payble', 15, 2)->default(0.00);
            $table->decimal('bill', 15, 2)->default(0.00);
            $table->decimal('qty', 12, 3)->default(0.000);
            $table->decimal('advanced_payment', 15, 2)->default(0.00);
            $table->decimal('due_payment', 15, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->unsignedBigInteger('bank_detail_id')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('delivery_status')->default('pending');
            $table->decimal('delivery_charge', 15, 2)->default(0.00);
            $table->decimal('labour_cost', 15, 2)->default(0.00);
            $table->decimal('weight_scale_cost', 15, 2)->default(0.00);
            $table->decimal('other_charges', 15, 2)->default(0.00);
            $table->string('charges_payout_status', 20)->default('unpaid');
            $table->timestamp('charges_payout_at')->nullable();
            $table->unsignedBigInteger('charges_payout_by')->nullable();
            $table->text('charges_payout_note')->nullable();
            $table->enum('status', ['paid', 'partial', 'credit', 'cancelled'])->default('credit');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('sales_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('bank_detail_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('charges_payout_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('coil_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('thickness')->nullable();
            $table->string('size')->nullable();
            $table->string('size_type')->default('ft');
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->decimal('profit', 15, 2)->default(0.00);
            $table->decimal('returned_qty', 12, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('coil_id')->references('id')->on('coils')->onDelete('set null');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('payment_for')->default(2); // 2: Sales, 3: Purchases
            $table->string('payment_method')->default('cash');
            $table->unsignedBigInteger('bank_detail_id')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('remarks')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('1');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('bank_detail_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('return_date');
            $table->decimal('total_refund_amount', 15, 2)->default(0.00);
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('sales_item_id')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->string('return_reason')->nullable();
            $table->string('condition')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('return_id')->references('id')->on('returns')->onDelete('cascade');
            $table->foreign('sales_item_id')->references('id')->on('sales_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales_items');
        Schema::dropIfExists('sales');
    }
};