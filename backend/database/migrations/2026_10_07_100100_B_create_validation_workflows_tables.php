<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows_validation', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->nullOnDelete();
            $table->string('objet_type', 120);
            $table->unsignedBigInteger('objet_id');
            $table->string('status', 40)->default('brouillon');
            $table->text('commentaire')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('refused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->timestamps();

            $table->index(['enterprise_id', 'objet_type', 'objet_id'], 'validation_object_scope_idx');
            $table->index(['enterprise_id', 'status'], 'validation_status_scope_idx');
        });

        Schema::create('validation_workflow_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('validation_workflow_id')->constrained('workflows_validation')->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['validation_workflow_id', 'created_at'], 'validation_history_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_workflow_histories');
        Schema::dropIfExists('workflows_validation');
    }
};
