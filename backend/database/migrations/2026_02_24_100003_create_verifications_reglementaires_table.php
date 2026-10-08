<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications_reglementaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->enum('type_verification', [
                'vgp', 
                'vgp_approfondie', 
                'epreuve_statique', 
                'epreuve_dynamique',
                'verification_mise_service',
                'verification_remise_service'
            ]);
            $table->string('organisme_agree');
            $table->string('numero_rapport')->unique()->nullable();
            $table->date('date_verification');
            $table->date('date_prochaine_verification');
            $table->enum('resultat', ['conforme', 'non_conforme', 'reserve'])->default('conforme');
            $table->text('observations')->nullable();
            $table->text('reserves')->nullable();
            $table->string('document_path')->nullable();
            $table->decimal('cout', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('equipement_id');
            $table->index('date_prochaine_verification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications_reglementaires');
    }
};
