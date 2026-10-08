<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->string('consent_type'); // 'cookies', 'data_processing', 'marketing', 'analytics'
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_date');
            $table->timestamp('expiry_date')->nullable();
            $table->text('consent_text')->nullable(); // Version du texte affiché
            $table->string('consent_version', 20)->default('1.0'); // Version politique
            $table->json('metadata')->nullable(); // Extra data (browser, device, etc.)
            $table->timestamps();
            
            $table->index(['user_id', 'consent_type']);
            $table->index('consent_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
