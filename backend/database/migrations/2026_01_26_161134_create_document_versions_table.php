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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('version_number'); // 1.0, 1.1, 2.0, etc.
            $table->string('file_path'); // Chemin fichier de cette version
            $table->bigInteger('file_size')->nullable(); // Taille en bytes
            $table->string('file_mime_type')->nullable();
            $table->string('file_original_name')->nullable();
            $table->text('change_summary')->nullable(); // Résumé des modifications
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_current')->default(false); // Version actuelle ?
            $table->timestamp('published_at')->nullable(); // Date diffusion
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['document_id', 'is_current']);
            $table->index('version_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
