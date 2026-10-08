<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicateurs', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->unique(); // Code court unique
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('formula')->nullable(); // Formule de calcul
            $table->string('unit', 50)->nullable(); // %, nombre, €, etc.
            $table->enum('type', ['kpi', 'metric', 'indicator'])->default('kpi');
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            
            // Seuils et alertes
            $table->decimal('target_value', 10, 2)->nullable(); // Valeur cible
            $table->decimal('min_threshold', 10, 2)->nullable(); // Seuil minimum
            $table->decimal('max_threshold', 10, 2)->nullable(); // Seuil maximum
            $table->decimal('alert_threshold', 10, 2)->nullable(); // Seuil d'alerte
            $table->decimal('current_value', 10, 2)->nullable(); // Valeur actuelle
            
            // Données historiques en JSON
            $table->json('historical_data')->nullable(); // [{date, value, note}, ...]
            
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'paused', 'archived'])->default('active');
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicateurs');
    }
};
