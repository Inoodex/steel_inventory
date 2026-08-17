<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'product_id')) {
                    $table->dropColumn('product_id');
                }
            });
        }

        if (Schema::hasTable('coils')) {
            Schema::table('coils', function (Blueprint $table) {
                $dropCols = [];
                if (Schema::hasColumn('coils', 'product_id')) {
                    $dropCols[] = 'product_id';
                }
                if (Schema::hasColumn('coils', 'steel_type')) {
                    $dropCols[] = 'steel_type';
                }
                if (!empty($dropCols)) {
                    $table->dropColumn($dropCols);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->after('id');
                }
            });
        }

        if (Schema::hasTable('coils')) {
            Schema::table('coils', function (Blueprint $table) {
                if (!Schema::hasColumn('coils', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->after('warehouse_id');
                }
                if (!Schema::hasColumn('coils', 'steel_type')) {
                    $table->string('steel_type')->nullable()->after('product_id');
                }
            });
        }
    }
};
