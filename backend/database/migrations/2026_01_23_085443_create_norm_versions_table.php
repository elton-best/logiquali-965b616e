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
        Schema::create('norm_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_id')->constrained()->onDelete('cascade');
            $table->string('version_code'); // 2015, 2018, 2024
            $table->string('full_code'); // ISO 9001:2015
            $table->date('published_at')->nullable();
            $table->date('archived_at')->nullable();
            $table->string('excel_file_path')->nullable(); // Fichier Excel original
            $table->boolean('is_current')->default(false); // Version actuelle
            $table->json('metadata')->nullable(); // Infos supplémentaires
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Une seule version current par norme
            $table->unique(['norm_id', 'is_current'], 'unique_current_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('norm_versions');
    }
};
