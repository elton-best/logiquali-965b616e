<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            // Drop generated column first (PostgreSQL constraint)
            $table->dropColumn('criticality');
        });

        Schema::table('risks', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Type : risque ou opportunité
            $table->enum('type', ['risk', 'opportunity'])->default('risk')->after('ref');
            
            // Cotation étendue (matrice 5x5)
            $table->integer('probability')->default(1)->change(); // 1-5
            $table->integer('gravity')->default(1)->after('probability'); // 1-5 (remplace severity)
            $table->integer('impact')->default(1)->after('gravity'); // 1-5 pour opportunités
            $table->integer('criticality')->nullable()->after('impact'); // Auto-calculé : prob * gravity
            
            // Matrice et cotation résiduelle
            $table->json('initial_assessment')->nullable()->after('root_cause'); // {probability, gravity, criticality}
            $table->json('residual_assessment')->nullable()->after('initial_assessment'); // Après actions
            
            // Traitement du risque
            $table->enum('treatment', ['avoid', 'reduce', 'transfer', 'accept', 'exploit'])->nullable()->after('control_action');
            $table->text('treatment_plan')->nullable()->after('treatment');
            
            // Spécificités QHSE
            $table->enum('category', ['strategic', 'operational', 'financial', 'compliance', 'hs', 'environmental'])->nullable()->after('type');
            $table->json('affected_stakeholders')->nullable()->after('category'); // Parties intéressées
            
            // DUERP (Document Unique HS)
            $table->string('duerp_reference')->nullable()->after('ref');
            $table->text('prevention_measures')->nullable()->after('control_action');
            
            // Revue et validation
            $table->date('last_review_date')->nullable()->after('deadline');
            $table->date('next_review_date')->nullable()->after('last_review_date');
            $table->foreignId('validated_by')->nullable()->after('responsible_id')->constrained('users')->nullOnDelete();
            $table->date('validation_date')->nullable()->after('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn([
                'workflow_state_id', 'type', 'gravity', 'impact', 'initial_assessment',
                'residual_assessment', 'treatment', 'treatment_plan', 'category',
                'affected_stakeholders', 'duerp_reference', 'prevention_measures',
                'last_review_date', 'next_review_date', 'validated_by', 'validation_date'
            ]);
        });
    }
};
