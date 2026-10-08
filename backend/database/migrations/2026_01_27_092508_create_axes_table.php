<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // Q, HS, E
            $table->string('name'); // Qualité, Santé-Sécurité, Environnement
            $table->string('description')->nullable();
            $table->string('color', 7)->nullable(); // Hex color for UI
            $table->string('icon')->nullable(); // Icon name for UI
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Pivot polymorphique pour lier axes aux différentes entités
        Schema::create('axeables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('axe_id')->constrained()->cascadeOnDelete();
            $table->morphs('axeable'); // axeable_type, axeable_id
            $table->timestamps();

            $table->unique(['axe_id', 'axeable_id', 'axeable_type'], 'axeables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axeables');
        Schema::dropIfExists('axes');
    }
};
