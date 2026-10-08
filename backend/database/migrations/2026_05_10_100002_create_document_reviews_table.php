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
        // Create document_reviews table for DocumentInventoryReview model
        // This table tracks scheduled reviews and audit history for documents
        Schema::create('document_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->dateTime('scheduled_date')->nullable();
            $table->dateTime('completed_date')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->json('findings')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index('document_id');
            $table->index('scheduled_date');
            $table->index('status');
            $table->index('reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_reviews');
    }
};
