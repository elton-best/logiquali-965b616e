<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_signature_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50); // 'audit_report', 'qhse_policy', etc.
            $table->unsignedBigInteger('document_id');
            $table->string('workflow_name', 100); // 'audit_report_iso', 'policy_validation'
            $table->integer('total_steps')->default(3); // Nombre total d'étapes
            $table->integer('current_step')->default(1); // Étape actuelle
            $table->enum('status', ['in_progress', 'completed', 'rejected', 'expired'])->default('in_progress');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at'); // +7 jours par étape
            $table->timestamps();

            // Index
            $table->index(['document_type', 'document_id']);
            $table->index('status');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
            
            // Contrainte unicité : 1 workflow actif par document
            $table->unique(['document_type', 'document_id', 'status'], 'unique_active_workflow');
            
            // Note: Validation current_step <= total_steps gérée au niveau applicatif
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signature_workflows');
    }
};
