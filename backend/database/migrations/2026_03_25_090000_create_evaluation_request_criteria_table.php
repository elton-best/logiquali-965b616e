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
        Schema::create('evaluation_request_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_criteria_id')->nullable()->constrained('evaluation_criteria')->nullOnDelete();

            // Snapshot des informations du critere au moment de la demande
            $table->string('criterion_name');
            $table->string('criterion_code')->nullable();
            $table->text('criterion_description')->nullable();
            $table->string('criterion_category')->nullable();

            // Snapshot de configuration de notation
            $table->enum('scale_type', ['numeric', 'stars', 'percentage', 'custom'])->default('numeric');
            $table->integer('scale_min')->default(0);
            $table->integer('scale_max')->default(4);
            $table->json('scale_labels')->nullable();

            // Snapshot de ponderation
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->boolean('is_mandatory')->default(true);
            $table->integer('display_order')->default(0);

            $table->timestamps();

            $table->unique(['evaluation_request_id', 'evaluation_criteria_id'], 'uniq_eval_request_criterion');
            $table->index(['evaluation_request_id', 'display_order'], 'idx_eval_request_display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_request_criteria');
    }
};
