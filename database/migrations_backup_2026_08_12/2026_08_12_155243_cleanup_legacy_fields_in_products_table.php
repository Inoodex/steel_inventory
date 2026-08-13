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
        // 1. Drop foreign keys if they exist
        try {
            DB::statement("ALTER TABLE `products` DROP FOREIGN KEY `products_brand_id_foreign`");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE `products` DROP FOREIGN KEY `products_category_id_foreign`");
        } catch (\Exception $e) {}

        // 2. Make brand_id, category_id, model nullable with default NULL
        try {
            DB::statement("ALTER TABLE `products` MODIFY `brand_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE `products` MODIFY `category_id` BIGINT UNSIGNED NULL DEFAULT NULL");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE `products` MODIFY `model` VARCHAR(255) NULL DEFAULT NULL");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE `products` MODIFY `warranty` INT NULL DEFAULT 0");
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
