<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('coils') && !Schema::hasColumn('coils', 'piece_count')) {
            Schema::table('coils', function (Blueprint $table) {
                $table->decimal('piece_count', 12, 2)->default(1.00)->after('length');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('coils') && Schema::hasColumn('coils', 'piece_count')) {
            Schema::table('coils', function (Blueprint $table) {
                $table->dropColumn('piece_count');
            });
        }
    }
};
