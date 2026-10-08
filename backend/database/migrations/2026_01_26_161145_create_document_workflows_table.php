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
        Schema::create('document_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // "Workflow Politique QHSE"
            $table->json('steps'); // [{order: 1, role: 'reviewer', name: 'Relecteur', required: true}, ...]
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Workflow par défaut pour cette catégorie
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('document_category_id');
            $table->index(['document_category_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_workflows');
    }
};
