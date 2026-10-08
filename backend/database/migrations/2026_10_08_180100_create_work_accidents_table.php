<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * REQ-6.1-D08 : Traitement des accidents SST.
     * REQ-6.1-D09 : Reco expert ISO 45001 - Accidents, incidents, presqu'accidents, analyse de causes, TF/TG.
     */
    public function up(): void
    {
        if (!Schema::hasTable('work_accidents')) {
            Schema::create('work_accidents', function (Blueprint $table) {
                $table->id();
                $table->string('ref')->unique();
                $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
                $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
                $table->foreignId('duerp_danger_id')->nullable()->constrained('duerp_dangers')->nullOnDelete();
                
                $table->string('type', 50)->default('accident_avec_arret'); // accident_avec_arret, accident_sans_arret, accident_trajet, presqu_accident, incident_materiel
                $table->dateTime('accident_date');
                $table->string('location')->nullable(); // Unité de travail / lieu exact
                $table->string('victim_name')->nullable();
                $table->string('victim_job_title')->nullable();
                $table->integer('victim_seniority_months')->nullable();
                
                $table->text('circumstances'); // Circonstances détaillées de l'accident
                $table->string('nature_of_injury')->nullable(); // Brûlure, coupure, fracture, etc.
                $table->string('location_of_injury')->nullable(); // Main, tête, dos, etc.
                $table->string('material_agent')->nullable(); // Machine, outil, sol glissant
                
                $table->integer('lost_days_count')->default(0); // Nombre de jours d'arrêt
                $table->string('severity_level', 20)->default('moyen'); // benin, moyen, grave, mortel
                
                $table->json('root_cause_analysis')->nullable(); // Arbre des causes / 5 Pourquoi (arbre structuré)
                $table->text('preventive_recommendations')->nullable();
                
                $table->foreignId('corrective_action_id')->nullable()->constrained('actions')->nullOnDelete();
                $table->foreignId('investigator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('investigation_date')->nullable();
                
                $table->string('status', 30)->default('declare'); // declare, en_enquete, actions_en_cours, resolu, cloture
                $table->text('closure_notes')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_accidents');
    }
};
