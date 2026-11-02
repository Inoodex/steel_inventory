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
        if (Schema::hasTable('purchases') && !Schema::hasColumn('purchases', 'product_name')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->string('product_name')->nullable()->after('warehouse_id');
            });
        }

        if (Schema::hasTable('coils') && !Schema::hasColumn('coils', 'product_name')) {
            Schema::table('coils', function (Blueprint $table) {
                $table->string('product_name')->nullable()->after('coil_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases') && Schema::hasColumn('purchases', 'product_name')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropColumn('product_name');
            });
        }

        if (Schema::hasTable('coils') && Schema::hasColumn('coils', 'product_name')) {
            Schema::table('coils', function (Blueprint $table) {
                $table->dropColumn('product_name');
            });
        }
    }
};
