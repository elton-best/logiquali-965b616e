<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('numero');
            $table->enum('type', ['communication', 'sensibilisation']);
            $table->string('designation');
            $table->json('cibles');
            $table->json('moyens');
            $table->json('chronogramme');
            $table->string('responsable');
            $table->decimal('cout', 10, 2)->nullable();
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('status', ['planifiee', 'en_attente', 'realisee', 'replanifiee', 'annulee'])->default('planifiee');
            $table->enum('frequency', ['ponctuelle', 'annuelle', 'semestrielle', 'trimestrielle', 'mensuelle', 'biennale', 'sur_demande']);
            $table->text('observations')->nullable();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('communication_proofs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('communication_id');
            $table->string('filename');
            $table->string('url');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->foreign('communication_id')->references('id')->on('communications')->onDelete('cascade');
        });

        Schema::create('communication_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('communication_id');
            $table->enum('action', ['created', 'updated', 'completed', 'rescheduled', 'cancelled']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->text('comment')->nullable();
            $table->date('previous_date')->nullable();
            $table->date('new_date')->nullable();
            $table->timestamps();

            $table->foreign('communication_id')->references('id')->on('communications')->onDelete('cascade');
        });

        Schema::create('communication_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('communication_id');
            $table->enum('type', ['J-30', 'J-15', 'J-7', 'J-3', 'J-1']);
            $table->date('date');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('communication_id')->references('id')->on('communications')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_alerts');
        Schema::dropIfExists('communication_history');
        Schema::dropIfExists('communication_proofs');
        Schema::dropIfExists('communications');
    }
};
