<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_code_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nomenclature_template_id')->nullable()->constrained()->nullOnDelete();
            
            // Type de document (PRC, FOR, POL, etc.)
            $table->string('document_type', 50);
            
            // Code complet généré (ex: PRC-01-042)
            $table->string('code', 100)->unique();
            
            // Statut du code
            $table->enum('status', ['available', 'reserved', 'used'])->default('available');
            
            // Document qui a réservé/utilisé ce code
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            
            // Métadonnées de réservation
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('released_at')->nullable();
            
            // Raison de la libération (rejet, suppression, etc.)
            $table->string('release_reason', 100)->nullable();
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['site_id', 'document_type', 'status'], 'code_pool_lookup_idx');
            $table->index(['site_id', 'status', 'created_at'], 'code_pool_available_idx');
            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_code_pool');
    }
};
