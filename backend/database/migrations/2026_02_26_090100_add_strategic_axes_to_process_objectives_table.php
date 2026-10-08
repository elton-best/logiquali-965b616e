<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('process_objectives', 'strategic_axes')) {
                $table->json('strategic_axes')->nullable()->after('strategic_axis');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (Schema::hasColumn('process_objectives', 'strategic_axes')) {
                $table->dropColumn('strategic_axes');
            }
        });
    }
};
