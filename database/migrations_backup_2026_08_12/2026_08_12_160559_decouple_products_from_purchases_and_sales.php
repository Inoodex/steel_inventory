<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Purchases Table Decoupling
        try {
            DB::statement("ALTER TABLE `purchases` DROP FOREIGN KEY `purchases_product_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `purchases` MODIFY `product_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `purchases` MODIFY `quantity` DECIMAL(12,3) NULL DEFAULT 0.000");
        } catch (\Throwable $e) {}

        // 2. Sales Table Decoupling
        try {
            DB::statement("ALTER TABLE `sales` DROP FOREIGN KEY `sales_product_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` DROP FOREIGN KEY `sales_client_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` DROP FOREIGN KEY `sales_project_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` MODIFY `product_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` MODIFY `client_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` MODIFY `project_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales` MODIFY `qty` DECIMAL(12,3) NULL DEFAULT 0.000");
        } catch (\Throwable $e) {}

        // 3. Sales Items Decoupling & Coil Support
        try {
            DB::statement("ALTER TABLE `sales_items` MODIFY `product_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `sales_items` MODIFY `qty` DECIMAL(12,3) NULL DEFAULT 1.000");
        } catch (\Throwable $e) {}

        Schema::table('sales_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_items', 'coil_id')) {
                $table->unsignedBigInteger('coil_id')->nullable()->after('order_id');
            }
            if (!Schema::hasColumn('sales_items', 'thickness')) {
                $table->string('thickness')->nullable()->after('coil_id');
            }
            if (!Schema::hasColumn('sales_items', 'size')) {
                $table->string('size')->nullable()->after('thickness');
            }
            if (!Schema::hasColumn('sales_items', 'size_type')) {
                $table->string('size_type', 50)->nullable()->after('size');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
