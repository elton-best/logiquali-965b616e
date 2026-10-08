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
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['client', 'employee']);
            $table->integer('year');
            $table->string('period')->nullable();
            $table->string('respondent_name')->nullable();
            $table->string('respondent_email')->nullable();
            $table->json('responses');
            $table->decimal('total_score', 5, 2)->nullable();
            $table->enum('satisfaction_level', ['very_satisfied', 'satisfied', 'dissatisfied', 'very_dissatisfied'])->nullable();
            $table->text('recommendations')->nullable();

            $table->timestamps();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satisfaction_surveys');
    }
};
