<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (!Schema::hasColumn('process_objectives', 'applicable_norms')) {
                $table->json('applicable_norms')->nullable()->after('strategic_axes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_objectives', function (Blueprint $table) {
            if (Schema::hasColumn('process_objectives', 'applicable_norms')) {
                $table->dropColumn('applicable_norms');
            }
        });
    }
};

