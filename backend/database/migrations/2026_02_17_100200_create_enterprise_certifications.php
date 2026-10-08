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
        Schema::create('enterprise_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('certification_id')->constrained('certifications_catalog')->onDelete('cascade');
            $table->string('certificate_number', 100)->nullable();
            $table->string('certifying_body', 100)->nullable(); // Organisme certificateur
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('logo_path')->nullable(); // Logo personnalisé si différent
            $table->enum('status', ['active', 'pending_renewal', 'expired'])->default('active');
            $table->text('scope')->nullable(); // Périmètre certification
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('enterprise_id');
            $table->index('status');
            $table->index('expiry_date');
            $table->index(['enterprise_id', 'status']);
            
            // Contrainte unicité
            $table->unique(['enterprise_id', 'certification_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_certifications');
    }
};
