<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->decimal('planned_budget', 12, 2)->nullable();
            $table->integer('planned_actions')->nullable();
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enterprise_id', 'site_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_plans');
    }
};
