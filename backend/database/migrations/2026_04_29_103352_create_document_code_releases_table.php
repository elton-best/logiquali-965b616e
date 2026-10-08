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
        Schema::create('document_code_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('enterprises')->onDelete('cascade');
            $table->string('code')->index();
            $table->foreignId('original_document_id')->nullable()->constrained('documents')->onDelete('set null');
            $table->enum('release_reason', ['rejected', 'deleted', 'manual'])->default('rejected');
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('released_at');
            $table->foreignId('reused_by_document_id')->nullable()->constrained('documents')->onDelete('set null');
            $table->timestamp('reused_at')->nullable();
            $table->timestamps();
            
            $table->index(['entreprise_id', 'code', 'reused_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_code_releases');
    }
};
