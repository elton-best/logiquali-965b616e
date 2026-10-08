<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_indicator_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained('process_indicators')->cascadeOnDelete();
            
            // Période de mesure
            $table->date('measurement_date');
            $table->string('period', 50)->nullable()->comment('Ex: 2026-01, T1-2026, Semaine 4');
            
            // Valeur mesurée
            $table->decimal('value', 10, 2);
            $table->text('comment')->nullable();
            
            // Calcul automatique du statut
            $table->enum('status', ['ok', 'warning', 'critical'])->default('ok');
            
            // Qui a saisi
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Index pour recherches rapides
            $table->index(['indicator_id', 'measurement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_indicator_values');
    }
};
