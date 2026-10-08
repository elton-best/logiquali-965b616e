<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duerp_work_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->foreignId('management_process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->string('code', 80)->nullable();
            $table->string('name');
            $table->string('unit_type', 40)->default('custom');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['enterprise_id', 'is_active'], 'duerp_units_scope_idx');
        });

        Schema::create('duerp_risk_families', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_unit_id')->constrained('duerp_work_units')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['work_unit_id', 'name'], 'duerp_family_unit_name_unique');
        });

        Schema::create('duerp_risk_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('label');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['enterprise_id', 'code'], 'duerp_risk_type_scope_code_unique');
        });

        Schema::create('duerp_scales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 40); // severity | exposure
            $table->string('label');
            $table->integer('value');
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['enterprise_id', 'kind', 'value'], 'duerp_scale_scope_kind_value_unique');
        });

        Schema::create('duerp_prevention_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('duerp_danger_id')->constrained('duerp_dangers')->cascadeOnDelete();
            $table->string('category', 80)->nullable();
            $table->text('action');
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->string('status', 40)->default('a_faire');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['duerp_danger_id', 'status'], 'duerp_prevention_status_idx');
        });

        Schema::table('duerp_dangers', function (Blueprint $table): void {
            $table->foreignId('work_unit_id')->nullable()->after('duerp_id')->constrained('duerp_work_units')->nullOnDelete();
            $table->foreignId('risk_family_id')->nullable()->after('work_unit_id')->constrained('duerp_risk_families')->nullOnDelete();
            $table->foreignId('risk_type_id')->nullable()->after('risk_family_id')->constrained('duerp_risk_types')->nullOnDelete();
            $table->index(['work_unit_id', 'risk_family_id'], 'duerp_danger_hierarchy_idx');
        });
    }

    public function down(): void
    {
        Schema::table('duerp_dangers', function (Blueprint $table): void {
            $table->dropForeign(['work_unit_id']);
            $table->dropForeign(['risk_family_id']);
            $table->dropForeign(['risk_type_id']);
            $table->dropIndex('duerp_danger_hierarchy_idx');
            $table->dropColumn(['work_unit_id', 'risk_family_id', 'risk_type_id']);
        });
        Schema::dropIfExists('duerp_prevention_actions');
        Schema::dropIfExists('duerp_scales');
        Schema::dropIfExists('duerp_risk_types');
        Schema::dropIfExists('duerp_risk_families');
        Schema::dropIfExists('duerp_work_units');
    }
};
