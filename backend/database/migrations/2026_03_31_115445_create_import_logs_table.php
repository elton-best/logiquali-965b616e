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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            
            // Relations (tenant scoping)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            
            // Fichier uploadé
            $table->string('file_name');
            $table->unsignedBigInteger('file_size'); // bytes
            
            // Compteurs
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('successful_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            
            // Statut du traitement
            $table->enum('status', ['pending', 'validating', 'processing', 'completed', 'failed'])
                ->default('pending');
            
            // Timestamps de traitement
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Rapport d'erreurs (si failed_rows > 0)
            $table->string('error_report_path')->nullable(); // storage/imports/errors/{id}.xlsx
            
            $table->timestamps();
            
            // Index pour performance
            $table->index(['user_id', 'status']);
            $table->index(['enterprise_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
