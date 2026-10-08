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
        Schema::create('task_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_tracking_id');
            $table->string('status_old')->nullable();
            $table->string('status_new');
            $table->tinyInteger('progress_rate_old')->nullable();
            $table->tinyInteger('progress_rate_new')->nullable();
            $table->unsignedBigInteger('changed_by');
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();

            $table->foreign('task_tracking_id')->references('id')->on('task_tracking')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['task_tracking_id', 'changed_at']);
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_tracking_history');
    }
};
