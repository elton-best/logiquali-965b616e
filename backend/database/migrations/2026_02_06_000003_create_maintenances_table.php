<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->enum('type', ['preventive', 'corrective', 'etalonnage'])->default('preventive');
            $table->date('date_prevue');
            $table->date('date_realisee')->nullable();
            $table->enum('statut', ['planifie', 'en_cours', 'realise', 'reporte', 'annule'])->default('planifie');
            $table->text('description')->nullable();
            $table->string('responsable')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            
            $table->index(['date_prevue', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
