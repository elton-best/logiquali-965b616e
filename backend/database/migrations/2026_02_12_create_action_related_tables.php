<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des participants aux actions
        Schema::create('action_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->nullable(); // contributor, validator, informed
            $table->timestamps();
            
            $table->unique(['action_id', 'user_id']);
            $table->index('action_id');
            $table->index('user_id');
        });

        // Table de suivi de progression
        Schema::create('action_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->date('update_date');
            $table->integer('progress_percentage')->nullable();
            $table->text('comments')->nullable();
            $table->text('difficulties')->nullable();
            $table->text('next_steps')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('action_id');
        });

        // Table des pièces jointes
        Schema::create('action_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->bigInteger('file_size')->nullable();
            $table->string('file_type', 100)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index('action_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_attachments');
        Schema::dropIfExists('action_progress_updates');
        Schema::dropIfExists('action_participants');
    }
};
