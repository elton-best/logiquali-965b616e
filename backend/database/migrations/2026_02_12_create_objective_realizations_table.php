<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('objective_realizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained()->cascadeOnDelete();
            $table->date('period_date');
            $table->decimal('actual_value', 10, 2);
            $table->decimal('target_value', 10, 2)->nullable();
            $table->decimal('gap', 10, 2)->nullable();
            $table->decimal('gap_percentage', 5, 2)->nullable();
            $table->enum('trend', ['up', 'down', 'stable'])->nullable();
            $table->text('comments')->nullable();
            $table->text('corrective_actions')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['objective_id', 'period_date']);
            $table->index('objective_id');
            $table->index('period_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objective_realizations');
    }
};
