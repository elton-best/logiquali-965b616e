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
        Schema::create('enterprise_sigle_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();

            $table->string('old_sigle', 10)->nullable();
            $table->string('new_sigle', 10);
            $table->string('reason', 255)->nullable();

            $table->string('recoding_mode', 20)->default('none'); // 'auto', 'manual', 'none'
            $table->integer('equipements_affected')->default(0);

            $table->boolean('is_reverted')->default(false);
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['enterprise_id', 'created_at']);
            $table->index(['enterprise_id', 'is_reverted']);
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprise_sigle_history');
    }
};
