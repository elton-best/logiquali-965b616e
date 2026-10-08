<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reclamations', 'process_id')) {
            Schema::table('reclamations', function (Blueprint $table): void {
                $table->foreignId('process_id')->nullable()->after('site_id')->constrained('processes')->nullOnDelete();
                $table->index(['process_id', 'site_id'], 'reclamations_process_scope_idx');
            });
        }

        if (!Schema::hasTable('operational_project_process')) {
            Schema::create('operational_project_process', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('operational_project_id')->constrained('operational_projects')->cascadeOnDelete();
                $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['operational_project_id', 'process_id'], 'operational_project_process_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_project_process');
        if (Schema::hasColumn('reclamations', 'process_id')) {
            Schema::table('reclamations', function (Blueprint $table): void {
                $table->dropForeign(['process_id']);
                $table->dropIndex('reclamations_process_scope_idx');
                $table->dropColumn('process_id');
            });
        }
    }
};
