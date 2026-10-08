<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_risks_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            
            // Type
            $table->enum('type', ['risque', 'opportunite']);
            $table->string('code', 50)->comment('Ex: R-PROD-001, O-PROD-001');
            
            // Description
            $table->string('title');
            $table->text('description');
            $table->text('cause')->nullable()->comment('Cause racine');
            $table->text('consequence')->nullable()->comment('Conséquence potentielle');
            
            // Aspects QHSE
            $table->boolean('aspect_qualite')->default(false);
            $table->boolean('aspect_environnement')->default(false);
            $table->boolean('aspect_sante_securite')->default(false);
            
            // Évaluation (matrice probabilité x gravité)
            $table->integer('probabilite')->default(1)->comment('1-5');
            $table->integer('gravite')->default(1)->comment('1-5');
            $table->integer('criticite')->nullable()->comment('Calculé: probabilite * gravite');
            $table->enum('niveau', ['faible', 'moyen', 'eleve', 'critique'])->nullable();
            
            // Stratégie de traitement
            $table->enum('strategie', ['accepter', 'reduire', 'transferer', 'eviter', 'exploiter'])
                ->nullable();
            $table->text('actions_prevues')->nullable()->comment('Actions pour traiter le risque/opportunité');
            
            // Responsable du traitement
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_date')->nullable()->comment('Date cible de traitement');
            
            // Statut
            $table->enum('status', ['identifie', 'en_cours', 'traite', 'surveille', 'cloture'])
                ->default('identifie');
            
            // Évaluation résiduelle (après traitement)
            $table->integer('probabilite_residuelle')->nullable();
            $table->integer('gravite_residuelle')->nullable();
            $table->integer('criticite_residuelle')->nullable()->comment('Calculé: probabilite_residuelle * gravite_residuelle');
            
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_risks_opportunities');
    }
};
