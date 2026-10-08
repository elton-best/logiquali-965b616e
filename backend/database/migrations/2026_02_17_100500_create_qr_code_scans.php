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
        Schema::create('qr_code_scans', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50); // Type document polymorphique
            $table->unsignedBigInteger('document_id');
            $table->string('hash', 64)->index(); // Hash QR code
            $table->string('ip_address', 45); // IPv4 ou IPv6
            $table->text('user_agent')->nullable();
            $table->string('country', 2)->nullable(); // Code pays ISO
            $table->string('city', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Si authentifié
            $table->timestamp('scanned_at');
            $table->timestamps();

            // Index pour performance et analytics
            $table->index(['document_type', 'document_id']);
            $table->index('scanned_at');
            $table->index('country');
            $table->index(['document_type', 'document_id', 'scanned_at']);
            $table->index(['ip_address', 'scanned_at']); // Détection abus
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_code_scans');
    }
};
