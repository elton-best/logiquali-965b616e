<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('equipment_code', 100)->unique();
            $table->string('name', 255);
            $table->string('category', 100)->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->string('location', 255)->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operational_status', 50)->default('operational');
            $table->string('criticality_level', 50)->nullable();
            $table->jsonb('process_ids')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('equipment_code');
            $table->index('operational_status');
        });

        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('maintenance_type', 50);
            $table->string('frequency', 50)->nullable();
            $table->integer('frequency_value')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date');
            $table->integer('alert_days_before')->default(7);
            $table->boolean('alert_sent')->default(false);
            $table->timestamp('alert_sent_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->jsonb('checklist')->nullable();
            $table->string('status', 50)->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('equipment_id');
            $table->index('next_maintenance_date');
        });

        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_plan_id')->constrained('maintenance_plans')->cascadeOnDelete();
            $table->date('performed_date');
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('work_description')->nullable();
            $table->jsonb('parts_replaced')->nullable();
            $table->text('observations')->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->decimal('labor_cost', 10, 2)->nullable();
            $table->decimal('parts_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->timestamps();

            $table->index('maintenance_plan_id');
        });

        Schema::create('calibration_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('frequency', 50);
            $table->date('last_calibration_date')->nullable();
            $table->date('next_calibration_date');
            $table->string('calibration_certificate_path', 500)->nullable();
            $table->integer('alert_days_before')->default(30);
            $table->boolean('alert_sent')->default(false);
            $table->string('service_provider', 255)->nullable();
            $table->string('service_provider_accreditation', 255)->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('valid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('equipment_id');
            $table->index('next_calibration_date');
        });

        Schema::create('calibration_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calibration_plan_id')->constrained('calibration_plans')->cascadeOnDelete();
            $table->date('performed_date');
            $table->string('certificate_number', 255)->nullable();
            $table->string('certificate_path', 500);
            $table->jsonb('results')->nullable();
            $table->string('conformity_status', 50)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->date('next_calibration_date')->nullable();
            $table->timestamps();

            $table->index('calibration_plan_id');
        });

        Schema::create('training_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->integer('year');
            $table->string('status', 50)->default('draft');
            $table->decimal('total_budget', 12, 2)->nullable();
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('year');
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_plan_id')->constrained('training_plans')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('training_type', 100)->nullable();
            $table->jsonb('target_user_ids')->nullable();
            $table->jsonb('target_job_roles')->nullable();
            $table->date('planned_date')->nullable();
            $table->string('frequency', 50)->nullable();
            $table->date('actual_date')->nullable();
            $table->string('trainer', 255)->nullable();
            $table->string('location', 255)->nullable();
            $table->decimal('duration_hours', 5, 2)->nullable();
            $table->string('status', 50)->default('planned');
            $table->text('cancellation_reason')->nullable();
            $table->integer('alert_before_months')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('training_plan_id');
            $table->index('status');
        });

        Schema::create('training_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('attended')->default(false);
            $table->integer('satisfaction_score')->nullable();
            $table->integer('knowledge_acquired_score')->nullable();
            $table->text('comments')->nullable();
            $table->string('certificate_path', 500)->nullable();
            $table->boolean('evaluation_locked')->default(false);
            $table->timestamps();

            $table->index('training_session_id');
        });

        Schema::create('communication_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('communication_type', 50);
            $table->string('target_audience', 255);
            $table->string('channel', 100);
            $table->string('frequency', 50)->nullable();
            $table->string('subject', 255);
            $table->text('content')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->jsonb('proof_paths')->nullable();
            $table->string('status', 50)->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('communication_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_actions');
        Schema::dropIfExists('training_evaluations');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_plans');
        Schema::dropIfExists('calibration_records');
        Schema::dropIfExists('calibration_plans');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('maintenance_plans');
        Schema::dropIfExists('equipment');
    }
};
