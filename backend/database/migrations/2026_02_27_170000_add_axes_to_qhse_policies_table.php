<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qhse_policies', function (Blueprint $table) {
            if (!Schema::hasColumn('qhse_policies', 'axes')) {
                $table->jsonb('axes')->nullable()->after('values');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qhse_policies', function (Blueprint $table) {
            if (Schema::hasColumn('qhse_policies', 'axes')) {
                $table->dropColumn('axes');
            }
        });
    }
};
