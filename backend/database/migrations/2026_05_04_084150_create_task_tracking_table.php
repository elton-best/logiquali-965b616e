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
        Schema::create('task_tracking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('trackable_type');
            $table->unsignedBigInteger('trackable_id');
            $table->enum('status', ['non_demarre', 'en_cours', 'termine'])->default('non_demarre');
            $table->tinyInteger('progress_rate')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('tracked_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['user_id', 'trackable_type', 'trackable_id']);
            $table->index(['user_id', 'status']);
            $table->index(['trackable_type', 'trackable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_tracking');
    }
};
