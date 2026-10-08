<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->comment('Ex: IND-PROD-001');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Type et catégorie
            $table->enum('type', ['efficacite', 'efficience', 'conformite', 'performance'])
                ->default('efficacite');
            $table->enum('category', ['qualite', 'environnement', 'sante_securite', 'global'])
                ->default('qualite');
            
            // Mesure
            $table->string('unit', 50)->comment('%, nb, €, kg, h, etc.');
            $table->enum('measurement_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])
                ->default('monthly');
            
            // Seuils et objectifs
            $table->decimal('target_value', 10, 2)->nullable()->comment('Valeur cible');
            $table->decimal('min_threshold', 10, 2)->nullable()->comment('Seuil minimum acceptable');
            $table->decimal('max_threshold', 10, 2)->nullable()->comment('Seuil maximum acceptable');
            $table->decimal('alert_threshold', 10, 2)->nullable()->comment('Seuil d\'alerte');
            
            // Formule de calcul (optionnelle)
            $table->text('formula')->nullable()->comment('Formule de calcul si applicable');
            
            // Responsable du suivi
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Statut
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['ok', 'warning', 'critical', 'unknown'])->default('unknown');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_indicators');
    }
};
