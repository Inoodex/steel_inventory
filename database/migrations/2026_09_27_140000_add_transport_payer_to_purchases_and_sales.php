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
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'transport_payer')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('transport_payer', 20)->default('me')->after('delivery_charge');
            });
        }

        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'transport_payer')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('transport_payer', 20)->default('vendor')->after('delivery_charge');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'transport_payer')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('transport_payer');
            });
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'transport_payer')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('transport_payer');
            });
        }
    }
};
