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
        Schema::create('certifications_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // 'ISO', 'Label', 'Regional', 'Custom'
            $table->string('code', 50)->unique(); // '9001', '14001', 'BREEAM', etc.
            $table->string('name'); // 'ISO 9001:2015 - Système Management Qualité'
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable(); // URL logo officiel
            $table->string('issuing_body', 100)->nullable(); // 'ISO', 'AFNOR', etc.
            $table->integer('validity_years')->nullable(); // 3 pour ISO
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Données additionnelles
            $table->timestamps();

            // Index
            $table->index('type');
            $table->index('is_active');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certifications_catalog');
    }
};
