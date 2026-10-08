<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_process_sequence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_sequence_id')->constrained('process_sequences')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->boolean('is_mandatory')->default(false)->comment('Document obligatoire pour cette activité');
            $table->timestamps();
            
            // Éviter doublons
            $table->unique(['process_sequence_id', 'document_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_process_sequence');
    }
};
