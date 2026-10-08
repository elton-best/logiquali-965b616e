<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_histories')) {
            return;
        }

        Schema::create('communication_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('communication_id');
            $table->enum('action', ['created', 'updated', 'completed', 'rescheduled', 'cancelled']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('user_name');
            $table->text('comment')->nullable();
            $table->date('previous_date')->nullable();
            $table->date('new_date')->nullable();
            $table->timestamps();

            $table->foreign('communication_id')
                ->references('id')
                ->on('communications')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_histories');
    }
};
