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
        Schema::create('enterprises', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('logo_path')->nullable();
            $table->string('registration_number')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->timestamp('trial_ends_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->string('organigram_path')->nullable();
            $table->string('field')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprises');
    }
};
