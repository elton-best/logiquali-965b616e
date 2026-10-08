<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competences_acquises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('competence_requise_id')->constrained('competences_requises')->onDelete('cascade');
            $table->enum('level_acquired', ['base', 'intermediaire', 'avance', 'expert']);
            $table->date('acquired_date');
            $table->date('expiry_date')->nullable();
            $table->enum('acquisition_method', ['formation', 'experience', 'certification', 'evaluation']);
            $table->uuid('formation_id')->nullable();
            $table->unsignedBigInteger('habilitation_id')->nullable();
            $table->string('proof_document')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'expired', 'pending_renewal'])->default('active');
            $table->timestamps();

            $table->index(['user_id', 'competence_requise_id']);
            $table->index(['enterprise_id', 'site_id']);
            $table->index(['expiry_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competences_acquises');
    }
};