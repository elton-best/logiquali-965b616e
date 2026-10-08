<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plaintes', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('workflow_state_id')->nullable()->constrained('workflow_states')->nullOnDelete();
            
            // Informations plaignant (partie intéressée)
            $table->string('plaignant_name');
            $table->string('plaignant_email')->nullable();
            $table->string('plaignant_phone')->nullable();
            $table->string('plaignant_company')->nullable();
            $table->enum('stakeholder_type', ['employee', 'client', 'supplier', 'contractor', 'neighbor', 'authority', 'other'])->default('employee');
            
            // Détails de la plainte
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['discrimination', 'harassment', 'safety', 'ethics', 'environment', 'working_conditions', 'management', 'other'])->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Dates
            $table->date('received_date');
            $table->date('due_date')->nullable();
            $table->date('closed_date')->nullable();
            
            // Traitement
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('analysis')->nullable();
            $table->text('immediate_response')->nullable();
            $table->date('response_date')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Suivi
            $table->text('corrective_actions')->nullable();
            $table->text('preventive_actions')->nullable();
            $table->boolean('confidential')->default(false);
            $table->boolean('anonymous')->default(false);
            
            // Résultat
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_comment')->nullable();
            $table->date('satisfaction_date')->nullable();
            
            // Impact
            $table->decimal('cost_impact', 10, 2)->nullable();
            
            // Statut (legacy - workflow_state_id recommandé)
            $table->string('status')->nullable();
            
            // Audit fields
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Indexes
            $table->index('stakeholder_type');
            $table->index('category');
            $table->index('severity');
            $table->index('status');
            $table->index('received_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plaintes');
    }
};
