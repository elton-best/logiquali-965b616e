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
        Schema::create('process_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('target_value', 10, 2)->nullable();
            $table->date('target_date')->nullable();
            $table->foreignId('indicator_id')->unique()->constrained('process_indicators')->cascadeOnDelete()->comment('1 objectif = 1 indicateur');
            $table->enum('status', ['not_started', 'in_progress', 'achieved', 'failed'])->default('not_started');
            $table->integer('achievement_percentage')->default(0)->comment('0-100');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->index('process_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_objectives');
    }
};
