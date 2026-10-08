<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('action', [
                'submitted',
                'verified',
                'approved',
                'rejected',
                'rejection_confirmed',
                'rejection_cancelled',
                'delegated',
                'reminded',
                'generated',
                'regenerated',
                'downloaded',
                'previewed',
            ]);
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('delegated_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('action_at');
            $table->timestamps();

            $table->index(['document_id', 'action_at']);
            $table->index(['user_id', 'action']);
            $table->index('action_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_history');
    }
};
