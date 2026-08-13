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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('thickness')->nullable()->after('product_id');
            $table->string('size')->nullable()->after('thickness');
            $table->string('size_type')->nullable()->after('size');
            $table->decimal('unit_weight', 10, 3)->nullable()->after('size_type');
            $table->decimal('total_weight', 10, 3)->nullable()->after('unit_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['thickness', 'size', 'size_type', 'unit_weight', 'total_weight']);
        });
    }
};
