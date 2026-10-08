<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            // Workflow state
            $table->foreignId('workflow_state_id')->nullable()->after('status')->constrained('workflow_states')->nullOnDelete();
            
            // Types étendus
            $table->dropColumn('type');
        });
        
        Schema::table('audits', function (Blueprint $table) {
            $table->enum('type', [
                'internal', 'external', 'certification', 
                'system', 'process', 'product', 
                'supplier', 'thematic', 'surveillance'
            ])->default('internal')->after('site_id');
            
            // Checklist et constatations en JSON
            $table->json('checklist')->nullable()->after('scope'); // [{question, conformity, evidence, note}, ...]
            $table->json('findings')->nullable()->after('checklist'); // [{type: major|minor|observation, description, nc_id}, ...]
            
            // Objectifs et conclusions
            $table->text('objectives')->nullable()->after('scope');
            $table->text('conclusion')->nullable()->after('findings');
            $table->integer('conformity_rate')->nullable()->after('conclusion'); // Taux de conformité %
            $table->integer('duration')->nullable()->after('planned_date'); // Durée en heures
            
            // Documents
            $table->string('report_path')->nullable()->after('global_report_path');
            $table->json('attachments')->nullable()->after('report_path'); // Pièces jointes
            
            // Planification basée risques
            $table->text('risk_based_criteria')->nullable()->after('objectives');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['workflow_state_id']);
            $table->dropColumn([
                'workflow_state_id', 'checklist', 'findings',
                'objectives', 'conclusion', 'conformity_rate',
                'report_path', 'attachments', 'risk_based_criteria'
            ]);
            $table->dropColumn('type');
        });
        
        Schema::table('audits', function (Blueprint $table) {
            $table->enum('type', ['internal', 'external', 'certification'])->after('site_id');
        });
    }
};
