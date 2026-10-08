<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->jsonb('control_points')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->text('operating_instructions')->nullable();
            $table->jsonb('related_document_ids')->nullable();
            $table->string('status', 50)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('process_id');
        });

        Schema::create('emergency_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('emergency_type', 100);
            $table->string('title', 255);
            $table->jsonb('procedure_steps')->nullable();
            $table->jsonb('emergency_contacts')->nullable();
            $table->jsonb('required_equipment')->nullable();
            $table->boolean('training_required')->default(false);
            $table->jsonb('trained_users')->nullable();
            $table->date('last_drill_date')->nullable();
            $table->date('next_drill_date')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('emergency_type');
        });

        Schema::create('collaborator_action_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained('actions')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->text('confirmation_notes')->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->timestamp('proof_uploaded_at')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();

            $table->index('action_id');
            $table->index('user_id');
        });

        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('suggestion_context', 100);
            $table->string('related_entity_type', 100)->nullable();
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->text('user_prompt');
            $table->text('ai_response');
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('modified_by_user')->default(false);
            $table->string('api_model', 100)->nullable();
            $table->integer('api_tokens_used')->nullable();
            $table->decimal('api_cost', 8, 4)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('enterprise_id');
            $table->index('suggestion_context');
        });

        Schema::create('onboarding_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('password_changed')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->boolean('signature_uploaded')->default(false);
            $table->text('signature_data')->nullable();
            $table->timestamp('signature_uploaded_at')->nullable();
            $table->boolean('activity_domain_selected')->default(false);
            $table->string('activity_domain', 255)->nullable();
            $table->timestamp('activity_domain_selected_at')->nullable();
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('onboarding_completed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_steps');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('collaborator_action_confirmations');
        Schema::dropIfExists('emergency_procedures');
        Schema::dropIfExists('operational_controls');
    }
};
