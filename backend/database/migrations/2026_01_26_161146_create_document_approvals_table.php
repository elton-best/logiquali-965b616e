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
        Schema::create('document_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Qui approuve
            $table->integer('step_order'); // Étape dans le workflow (1, 2, 3...)
            $table->string('role'); // Role de l'approbateur (reviewer, validator, approver)
            $table->enum('status', [
                'pending',           // En attente
                'approved',          // Approuvé
                'rejected',          // Rejeté
                'changes_requested'  // Modifications demandées
            ])->default('pending');
            $table->text('comment')->nullable(); // Commentaire approbateur
            $table->timestamp('actioned_at')->nullable(); // Date action
            $table->timestamps();
            
            $table->index(['document_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('step_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approvals');
    }
};
