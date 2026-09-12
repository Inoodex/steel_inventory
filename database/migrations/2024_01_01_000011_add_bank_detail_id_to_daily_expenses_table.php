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
        Schema::table('daily_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_expenses', 'bank_detail_id')) {
                $table->foreignId('bank_detail_id')->nullable()->after('spend_method')->constrained('bank_details')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('daily_expenses', 'bank_detail_id')) {
                $table->dropForeign(['bank_detail_id']);
                $table->dropColumn('bank_detail_id');
            }
        });
    }
};
