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
        Schema::create('management_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->foreignId('chairman_id')->constrained('users')->nullOnDelete();
            $table->json('participants')->nullable();
            $table->text('previous_actions_status')->nullable();
            $table->text('context_changes')->nullable();
            $table->text('performance_indicators')->nullable();
            $table->text('customer_satisfaction')->nullable();
            $table->text('audit_results')->nullable();
            $table->text('nc_complaints_status')->nullable();
            $table->text('resources_adequacy')->nullable();
            $table->text('improvement_opportunities')->nullable();
            $table->json('decisions')->nullable();
            $table->string('report_path')->nullable();
            $table->enum('status', ['planned', 'completed'])->default('planned');

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
        Schema::dropIfExists('management_reviews');
    }
};
