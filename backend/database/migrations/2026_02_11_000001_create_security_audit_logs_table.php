<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('enterprise_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_type'); // auth, permission, data_access, file_upload, etc.
            $table->string('resource_type')->nullable(); // Model class name
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('action'); // login, logout, create, update, delete, access_denied, etc.
            $table->ipAddress('ip_address');
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', ['logged', 'reviewed', 'escalated'])->default('logged');
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['enterprise_id', 'created_at']);
            $table->index(['risk_level', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_audit_logs');
    }
};