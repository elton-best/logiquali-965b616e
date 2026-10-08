<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renommer la table
        Schema::rename('complaints', 'reclamations');
        
        Schema::table('reclamations', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Informations client/partie intéressée étendue
            $table->string('client_name')->nullable()->after('user_id');
            $table->string('client_email')->nullable()->after('client_name');
            $table->string('client_phone')->nullable()->after('client_email');
            $table->string('client_company')->nullable()->after('client_phone');
            $table->enum('stakeholder_type', ['client', 'supplier', 'employee', 'authority', 'other'])->default('client')->after('client_company');
            
            // Catégorie et gravité
            $table->enum('category', ['product', 'service', 'delivery', 'quality', 'safety', 'environment', 'other'])->nullable()->after('stakeholder_type');
            $table->enum('severity', ['minor', 'major', 'critical'])->default('minor')->after('category');
            
            // Traitement de la réclamation
            $table->text('analysis')->nullable()->after('description');
            $table->text('immediate_response')->nullable()->after('analysis');
            $table->date('response_date')->nullable()->after('immediate_response');
            $table->foreignId('responded_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            
            // Satisfaction post-traitement
            $table->integer('satisfaction_rating')->nullable()->after('response_date'); // 1-5
            $table->text('satisfaction_comment')->nullable()->after('satisfaction_rating');
            $table->date('satisfaction_date')->nullable()->after('satisfaction_comment');
            
            // Délais
            $table->date('received_date')->nullable()->after('ref');
            $table->date('due_date')->nullable()->after('received_date');
            $table->date('closed_date')->nullable()->after('due_date');
            
            // Coût et impact
            $table->decimal('cost_impact', 10, 2)->nullable()->after('satisfaction_comment');
            $table->boolean('warranty_claim')->default(false)->after('cost_impact');
        });
    }

    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['responded_by']);
            $table->dropColumn([
                'workflow_state_id', 'client_name', 'client_email', 'client_phone',
                'client_company', 'stakeholder_type', 'category', 'severity',
                'analysis', 'immediate_response', 'response_date', 'responded_by',
                'satisfaction_rating', 'satisfaction_comment', 'satisfaction_date',
                'received_date', 'due_date', 'closed_date', 'cost_impact', 'warranty_claim'
            ]);
        });
        
        Schema::rename('reclamations', 'complaints');
    }
};
