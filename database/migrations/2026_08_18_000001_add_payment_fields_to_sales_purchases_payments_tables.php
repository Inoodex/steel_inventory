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
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('due_payment');
            }
            if (!Schema::hasColumn('sales', 'bank_detail_id')) {
                $table->foreignId('bank_detail_id')->nullable()->after('payment_method')->constrained('bank_details')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'transaction_ref')) {
                $table->string('transaction_ref')->nullable()->after('bank_detail_id');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('due');
            }
            if (!Schema::hasColumn('purchases', 'bank_detail_id')) {
                $table->foreignId('bank_detail_id')->nullable()->after('payment_method')->constrained('bank_details')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchases', 'transaction_ref')) {
                $table->string('transaction_ref')->nullable()->after('bank_detail_id');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'bank_detail_id')) {
                $table->foreignId('bank_detail_id')->nullable()->after('payment_method')->constrained('bank_details')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'transaction_ref')) {
                $table->string('transaction_ref')->nullable()->after('bank_detail_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'bank_detail_id')) {
                $table->dropForeign(['bank_detail_id']);
                $table->dropColumn('bank_detail_id');
            }
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('sales', 'transaction_ref')) {
                $table->dropColumn('transaction_ref');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'bank_detail_id')) {
                $table->dropForeign(['bank_detail_id']);
                $table->dropColumn('bank_detail_id');
            }
            if (Schema::hasColumn('purchases', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('purchases', 'transaction_ref')) {
                $table->dropColumn('transaction_ref');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bank_detail_id')) {
                $table->dropForeign(['bank_detail_id']);
                $table->dropColumn('bank_detail_id');
            }
            if (Schema::hasColumn('payments', 'transaction_ref')) {
                $table->dropColumn('transaction_ref');
            }
        });
    }
};
