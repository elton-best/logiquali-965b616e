<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('improvement_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->foreignId('proposer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('impact', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'adopted', 'rejected'])->default('pending');
            $table->text('follow_up')->nullable();
            $table->date('proposed_at')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();

            $table->index(['site_id', 'status']);
            $table->index(['process_id']);
            $table->index(['proposer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('improvement_suggestions');
    }
};
