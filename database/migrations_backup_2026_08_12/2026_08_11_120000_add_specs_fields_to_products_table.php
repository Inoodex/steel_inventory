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
        Schema::table('products', function (Blueprint $table) {
            $table->string('thickness', 100)->nullable()->after('model');
            $table->string('size', 100)->nullable()->after('thickness');
            $table->string('size_type', 50)->nullable()->after('size');
            $table->decimal('weight', 10, 3)->nullable()->after('size_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['thickness', 'size', 'size_type', 'weight']);
        });
    }
};
