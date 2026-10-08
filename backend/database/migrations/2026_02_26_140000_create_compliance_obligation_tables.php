<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_obligation_aspects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'name']);
        });

        Schema::create('compliance_obligation_aspect_norm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspect_id')->constrained('compliance_obligation_aspects')->cascadeOnDelete();
            $table->foreignId('norm_id')->constrained('norms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aspect_id', 'norm_id']);
        });

        Schema::create('compliance_obligation_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aspect_id')->constrained('compliance_obligation_aspects')->cascadeOnDelete();
            $table->string('ref')->nullable()->unique();
            $table->text('regulatory_reference');
            $table->text('description')->nullable();
            $table->text('applicable_requirement')->nullable();
            $table->string('watch_source')->nullable();
            $table->date('entry_into_force_date')->nullable();
            $table->enum('regulatory_change_status', ['existing', 'new', 'modified'])->nullable();
            $table->enum('validity_status', ['in_force', 'obsolete'])->nullable();
            $table->enum('compliance_status', ['compliant', 'partial', 'non_compliant', 'not_applicable'])->default('not_applicable');
            $table->text('actions_corrective_preventive')->nullable();
            $table->date('deadline')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('evaluation_frequency', ['monthly', 'quarterly', 'semiannual', 'annual', 'on_demand'])->nullable();
            $table->text('comments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['aspect_id', 'compliance_status']);
        });

        Schema::create('compliance_obligation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('text_id')->constrained('compliance_obligation_texts')->cascadeOnDelete();
            $table->text('title');
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])->default('pending');
            $table->text('comments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_obligation_actions');
        Schema::dropIfExists('compliance_obligation_texts');
        Schema::dropIfExists('compliance_obligation_aspect_norm');
        Schema::dropIfExists('compliance_obligation_aspects');
    }
};

