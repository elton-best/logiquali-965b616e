<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('operational_projects', 'release_notes')) {
                $table->text('release_notes')->nullable()->after('project_manager_id');
            }
            if (!Schema::hasColumn('operational_projects', 'release_evidences')) {
                $table->json('release_evidences')->nullable()->after('release_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operational_projects', function (Blueprint $table) {
            if (Schema::hasColumn('operational_projects', 'release_evidences')) {
                $table->dropColumn('release_evidences');
            }
            if (Schema::hasColumn('operational_projects', 'release_notes')) {
                $table->dropColumn('release_notes');
            }
        });
    }
};

