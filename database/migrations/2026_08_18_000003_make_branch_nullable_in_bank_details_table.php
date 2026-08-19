<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bank_details', 'branch')) {
            DB::statement("ALTER TABLE `bank_details` MODIFY `branch` VARCHAR(255) NULL");
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `bank_details` MODIFY `branch` VARCHAR(255) NOT NULL");
    }
};
