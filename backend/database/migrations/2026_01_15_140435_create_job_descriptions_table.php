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
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title');
            $table->text('mission');
            $table->text('activities');
            $table->text('functional_relations')->nullable();
            $table->string('hierarchical_superior')->nullable();
            $table->text('work_conditions')->nullable();
            $table->text('work_environment')->nullable();
            $table->string('required_level')->nullable();
            $table->string('current_level')->nullable();
            $table->text('required_skills')->nullable();
            $table->text('professional_qualities')->nullable();
            $table->date('last_updated')->nullable();

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
        Schema::dropIfExists('job_descriptions');
    }
};
