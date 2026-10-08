<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('process_risks_opportunities', 'normes_iso')) {
                $table->json('normes_iso')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('process_risks_opportunities', function (Blueprint $table) {
            if (Schema::hasColumn('process_risks_opportunities', 'normes_iso')) {
                $table->dropColumn('normes_iso');
            }
        });
    }
};

