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
        Schema::create('document_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50); // 'policy', 'procedure', 'job_description', etc.
            $table->unsignedBigInteger('document_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('signature_text'); // "Lu et approuvé par Jean Dupont"
            $table->timestamp('signed_at');
            $table->string('ip_address', 45)->nullable(); // IPv4 ou IPv6
            $table->text('user_agent')->nullable();
            $table->string('document_hash', 64)->nullable(); // SHA-256 du document
            $table->boolean('password_verified')->default(true);
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index('user_id');
            $table->index('signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_signatures');
    }
};
