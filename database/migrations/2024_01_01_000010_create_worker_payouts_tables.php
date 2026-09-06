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
        Schema::create('worker_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_no', 50)->unique();
            $table->date('payout_date');
            $table->string('charge_type', 50)->default('mixed'); // labour, delivery, weight_scale, other, mixed
            $table->string('recipient_name', 191);
            $table->string('recipient_phone', 50)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('payment_method', 50)->default('cash'); // cash, bank, cheque, mobile_banking
            $table->unsignedBigInteger('bank_detail_id')->nullable();
            $table->unsignedBigInteger('payment_account_id')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('bank_detail_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('payment_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('worker_payout_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_payout_id');
            $table->unsignedBigInteger('sale_id');
            $table->string('charge_type', 50); // labour, delivery, weight_scale, other
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('worker_payout_id')->references('id')->on('worker_payouts')->onDelete('cascade');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->index(['sale_id', 'charge_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worker_payout_items');
        Schema::dropIfExists('worker_payouts');
    }
};
