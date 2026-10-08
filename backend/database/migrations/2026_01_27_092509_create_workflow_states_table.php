<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_states', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // NonConformity, Audit, Action, etc.
            $table->string('code', 50); // draft, in_analysis, validated, closed
            $table->string('label'); // Brouillon, En analyse, Validé, Clôturé
            $table->string('color', 7)->nullable(); // Hex color
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_initial')->default(false); // État initial du workflow
            $table->boolean('is_final')->default(false); // État final du workflow
            $table->json('allowed_transitions')->nullable(); // Transitions autorisées vers autres états
            $table->json('required_fields')->nullable(); // Champs obligatoires pour cet état
            $table->json('permissions')->nullable(); // Permissions nécessaires
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['entity_type', 'code'], 'workflow_states_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_states');
    }
};
