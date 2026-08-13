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
        // 1. Disable FK checks for safe cleanup
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. Drop FK constraints pointing to products or product_serials
        $fkDrops = [
            ['product_serials', 'product_serials_product_id_foreign'],
            ['services', 'services_product_id_foreign'],
            ['warranty_claims', 'warranty_claims_product_id_foreign'],
            ['return_items', 'return_items_product_id_foreign'],
            ['project_items', 'project_items_product_id_foreign'],
            ['quotation_items', 'quotation_items_product_id_foreign'],
            ['inventories', 'inventories_product_id_foreign'],
        ];

        foreach ($fkDrops as [$table, $fk]) {
            try {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
                }
            } catch (\Throwable $e) {}
        }

        // 3. Make product_id nullable in leftover tables
        $tablesWithProductId = ['services', 'warranty_claims', 'return_items', 'inventories'];
        foreach ($tablesWithProductId as $tbl) {
            try {
                if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'product_id')) {
                    DB::statement("ALTER TABLE `{$tbl}` MODIFY `product_id` BIGINT UNSIGNED NULL DEFAULT NULL");
                }
            } catch (\Throwable $e) {}
        }

        // 4. Drop tables
        Schema::dropIfExists('product_serials');
        Schema::dropIfExists('products');

        // 5. Re-enable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
