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
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('order_no')->unique();
            $table->date('order_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('tax', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('payble', 15, 2)->default(0.00);
            $table->decimal('advanced_payment', 15, 2)->default(0.00);
            $table->decimal('due_payment', 15, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->string('sale_type')->default('retail');
            $table->enum('status', ['paid', 'partial', 'credit', 'cancelled'])->default('credit');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('sales_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('coil_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('lot_id')->nullable();
            $table->string('thickness')->nullable();
            $table->string('size')->nullable();
            $table->string('size_type')->default('ft');
            $table->decimal('qty', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->decimal('profit', 15, 2)->default(0.00);
            $table->integer('warranty')->default(0);
            $table->decimal('returned_qty', 12, 3)->default(0.000);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('coil_id')->references('id')->on('coils')->onDelete('set null');
            $table->foreign('lot_id')->references('id')->on('lots')->onDelete('set null');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('payment_for')->default(2); // 2: Sales
            $table->string('payment_method')->default('cash');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
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
            $table->unsignedBigInteger('product_id')->nullable();
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

        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('sales_revenue', 15, 2)->default(0.00);
            $table->decimal('other_revenue', 15, 2)->default(0.00);
            $table->decimal('total_revenue', 15, 2)->default(0.00);
            $table->decimal('total_expense', 15, 2)->default(0.00);
            $table->decimal('net_profit', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenues');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sales_items');
        Schema::dropIfExists('sales');
    }
};