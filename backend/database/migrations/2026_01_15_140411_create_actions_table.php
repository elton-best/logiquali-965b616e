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
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['corrective', 'preventive', 'improvement', 'immediate']);
            $table->string('title');
            $table->text('description');
            $table->enum('source', ['audit', 'complaint', 'non_conformity', 'suggestion', 'management_review'])->nullable();
            $table->foreignId('initiator_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->text('root_cause')->nullable();
            $table->text('immediate_action')->nullable();
            $table->date('immediate_action_date')->nullable();
            $table->foreignId('responsible_id')->constrained('users')->cascadeOnDelete()->nullable();
            $table->date('deadline')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'verified', 'cancelled'])->default('planned');
            $table->text('effectiveness_verified')->nullable();
            $table->date('verification_date')->nullable();



            // $table->enum('origin', ['audit', 'nc', 'risk', 'opportunity', 'complaint', 'other']);
            // $table->foreignId('origin_id')->nullable();
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
        Schema::dropIfExists('actions');
    }
};
