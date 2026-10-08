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
        Schema::create('equipement_transfer_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();

            // Previous location state
            $table->foreignId('previous_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('previous_localisation_id')->nullable()->constrained('codification_elements')->nullOnDelete();

            // New location state
            $table->foreignId('new_site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('new_localisation_id')->nullable()->constrained('codification_elements')->nullOnDelete();

            // Transfer metadata
            $table->string('transfer_reason_code', 20)->nullable();
            $table->text('transfer_notes')->nullable();

            // Verification workflow (optional)
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();

            // Audit trail
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index(['enterprise_id', 'equipement_id', 'transferred_at']);
            $table->index(['equipement_id', 'transferred_at']);
            $table->index('transferred_by');
            $table->index('transfer_reason_code');
            $table->index(['enterprise_id', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipement_transfer_history');
    }
};
