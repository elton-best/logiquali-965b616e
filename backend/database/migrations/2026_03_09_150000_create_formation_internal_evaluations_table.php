<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_internal_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('formation_id');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 50)->default('combinaison');
            $table->json('scores')->nullable();
            $table->decimal('global_score', 5, 2)->nullable();
            $table->json('strengths')->nullable();
            $table->json('improvements')->nullable();
            $table->text('comment')->nullable();
            $table->date('evaluated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('formation_id');
            $table->foreign('formation_id')->references('id')->on('formations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_internal_evaluations');
    }
};
