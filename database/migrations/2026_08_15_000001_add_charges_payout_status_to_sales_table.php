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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'charges_payout_status')) {
                $table->string('charges_payout_status', 20)->default('unpaid')->after('other_charges');
            }
            if (!Schema::hasColumn('sales', 'charges_payout_at')) {
                $table->timestamp('charges_payout_at')->nullable()->after('charges_payout_status');
            }
            if (!Schema::hasColumn('sales', 'charges_payout_by')) {
                $table->unsignedBigInteger('charges_payout_by')->nullable()->after('charges_payout_at');
                $table->foreign('charges_payout_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('sales', 'charges_payout_note')) {
                $table->text('charges_payout_note')->nullable()->after('charges_payout_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'charges_payout_by')) {
                $table->dropForeign(['charges_payout_by']);
                $table->dropColumn('charges_payout_by');
            }
            if (Schema::hasColumn('sales', 'charges_payout_note')) {
                $table->dropColumn('charges_payout_note');
            }
            if (Schema::hasColumn('sales', 'charges_payout_at')) {
                $table->dropColumn('charges_payout_at');
            }
            if (Schema::hasColumn('sales', 'charges_payout_status')) {
                $table->dropColumn('charges_payout_status');
            }
        });
    }
};
