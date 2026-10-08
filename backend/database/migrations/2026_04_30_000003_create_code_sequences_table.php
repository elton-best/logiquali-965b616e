<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained('enterprises')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('document_type_configuration_id')
                ->constrained('document_type_configurations')
                ->onDelete('cascade');
            $table->foreignId('process_id')->nullable()->constrained('processes')->onDelete('cascade');
            $table->year('year')->nullable(); // Pour scope incluant année
            $table->tinyInteger('month')->unsigned()->nullable(); // Pour scope incluant mois (1-12)
            $table->integer('last_sequence_number')->unsigned()->default(0);
            $table->json('available_numbers')->nullable(); // Pool de numéros recyclés [3, 7, 12]
            $table->timestamps();

            // Index composé pour recherche rapide selon scope
            $table->index([
                'enterprise_id',
                'site_id',
                'document_type_configuration_id',
                'process_id',
                'year',
                'month'
            ], 'idx_sequence_scope');

            // Contrainte unique pour éviter doublons de séquence
            $table->unique([
                'enterprise_id',
                'site_id',
                'document_type_configuration_id',
                'process_id',
                'year',
                'month'
            ], 'unique_sequence_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_sequences');
    }
};
