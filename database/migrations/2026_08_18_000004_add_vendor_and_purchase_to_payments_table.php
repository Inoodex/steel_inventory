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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('customer_id');
                $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'purchase_id')) {
                $table->unsignedBigInteger('purchase_id')->nullable()->after('sale_id');
                $table->foreign('purchase_id')->references('id')->on('purchases')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'purchase_id')) {
                $table->dropForeign(['purchase_id']);
                $table->dropColumn('purchase_id');
            }
            if (Schema::hasColumn('payments', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });
    }
};
