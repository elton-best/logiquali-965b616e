<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['INTERNE', 'EXTERNE']);
            $table->string('code', 10)->unique();
            $table->string('label', 100);
            $table->timestamps();
        });

        Schema::create('context_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('context_id')->constrained('organization_contexts')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('analysis_categories')->cascadeOnDelete();
            $table->text('description');
            $table->enum('sentiment', ['FAVORABLE', 'DEFAVORABLE']);
            $table->boolean('is_major')->default(false);
            $table->integer('impact_score')->default(1);
            $table->text('action_plan_summary')->nullable();
            $table->timestamps();

            $table->index('is_major');
            $table->index('context_id');
        });

        // Extend organization_contexts to align with updated schema
        if (!Schema::hasColumn('organization_contexts', 'enterprise_id')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->foreignId('enterprise_id')->nullable()->after('ref')->constrained('enterprises')->nullOnDelete();
            });
        }
        if (!Schema::hasColumn('organization_contexts', 'title')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->string('title')->nullable()->after('enterprise_id');
            });
        }
        if (!Schema::hasColumn('organization_contexts', 'is_active')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('title');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('context_issues');
        Schema::dropIfExists('analysis_categories');

        if (Schema::hasColumn('organization_contexts', 'enterprise_id')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->dropForeign(['enterprise_id']);
                $table->dropColumn('enterprise_id');
            });
        }
        if (Schema::hasColumn('organization_contexts', 'title')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
        if (Schema::hasColumn('organization_contexts', 'is_active')) {
            Schema::table('organization_contexts', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
