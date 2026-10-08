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
        Schema::create('norms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ISO 9001, ISO 14001
            $table->string('name'); // Système de management de la qualité
            $table->text('description')->nullable();
            $table->enum('domain', ['quality', 'environment', 'security', 'food_safety', 'integrated', 'other'])->default('quality');
            $table->unsignedBigInteger('current_version_id')->nullable(); // Will be FK after norm_versions created
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('norms');
    }
};
