<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_iso_coverage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            
            // Norme ISO
            $table->enum('norme', ['9001', '14001', '45001', '27001', '22000'])->comment('Norme ISO');
            $table->string('version', 10)->default('2015')->comment('Version de la norme');
            
            // Clause ISO
            $table->string('clause_number', 20)->comment('Ex: 4.4, 6.1, 8.2.1');
            $table->string('clause_title')->comment('Titre de la clause');
            $table->text('clause_description')->nullable();
            
            // Couverture
            $table->enum('coverage_level', ['none', 'partial', 'full'])
                ->default('partial')
                ->comment('Niveau de couverture');
            $table->integer('coverage_percentage')->nullable()->comment('% de couverture (0-100)');
            $table->text('coverage_comment')->nullable()->comment('Détails sur la couverture');
            
            // Preuves
            $table->json('evidence')->nullable()->comment('Références vers documents, procédures, enregistrements');
            
            // Évaluation
            $table->enum('conformity_status', ['conforme', 'non_conforme', 'amelioration', 'non_applicable'])
                ->default('conforme');
            $table->date('last_audit_date')->nullable();
            $table->date('next_audit_date')->nullable();
            
            $table->timestamps();
            
            // Contrainte d'unicité
            $table->unique(['process_id', 'norme', 'clause_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_iso_coverage');
    }
};
