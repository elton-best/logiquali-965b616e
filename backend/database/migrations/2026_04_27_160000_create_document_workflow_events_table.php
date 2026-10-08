<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('chain_index');
            $table->string('event_type', 120);
            $table->string('from_status', 80)->nullable();
            $table->string('to_status', 80);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->string('previous_event_hash', 64)->nullable();
            $table->string('event_hash', 64);
            $table->string('event_signature', 64);
            $table->timestamp('occurred_at');
            $table->timestamp('sealed_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chain_index']);
            $table->unique('event_hash');
            $table->index(['document_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_workflow_events');
    }
};

