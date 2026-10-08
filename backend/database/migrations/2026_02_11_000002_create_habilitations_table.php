<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('habilitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // conduite, manipulation, securite, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('certificate_number')->nullable();
            $table->date('issued_date');
            $table->date('expiry_date');
            $table->string('issuing_authority');
            $table->enum('status', ['active', 'expired', 'suspended', 'renewed'])->default('active');
            $table->string('certificate_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enterprise_id', 'site_id']);
            $table->index(['user_id', 'type']);
            $table->index(['expiry_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('habilitations');
    }
};