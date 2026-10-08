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
        Schema::create('process_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->string('version_number', 20)->comment('Ex: 1.0, 1.1, 2.0');
            $table->date('version_date');
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verifier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'verified', 'approved'])->default('draft');
            $table->text('changes_description')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_current')->default(false)->comment('Version actuelle');
            $table->timestamps();
            $table->softDeletes()->comment('Archivage versions anciennes');
            
            // Index pour retrouver version actuelle
            $table->index(['process_id', 'is_current']);
            $table->index(['process_id', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_versions');
    }
};
