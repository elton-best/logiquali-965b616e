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
        Schema::create('organization_contexts', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            $table->integer('year');
            $table->text('internal_strengths')->nullable();
            $table->text('internal_weaknesses')->nullable();
            $table->text('external_opportunities')->nullable();
            $table->text('external_threats')->nullable();
            $table->text('political_factors')->nullable();
            $table->text('economic_factors')->nullable();
            $table->text('social_factors')->nullable();
            $table->text('technological_factors')->nullable();
            $table->text('environmental_factors')->nullable();
            $table->text('legal_factors')->nullable();

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
        Schema::dropIfExists('organization_contexts');
    }
};
