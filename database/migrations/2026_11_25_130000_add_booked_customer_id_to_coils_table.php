<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coils', function (Blueprint $table) {
            if (!Schema::hasColumn('coils', 'booked_customer_id')) {
                $table->unsignedBigInteger('booked_customer_id')->nullable()->after('status');
                $table->foreign('booked_customer_id')
                    ->references('id')
                    ->on('customers')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('coils', function (Blueprint $table) {
            if (Schema::hasColumn('coils', 'booked_customer_id')) {
                $table->dropForeign(['booked_customer_id']);
                $table->dropColumn('booked_customer_id');
            }
        });
    }
};
