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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['internal', 'external', 'certification']);
            $table->string('title');
            $table->date('planned_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->foreignId('lead_auditor_id')->constrained('users')->nullOnDelete()->constrained('users');
            $table->json('team_members')->nullable();
            $table->text('scope')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->string('global_report_path')->nullable();
            // $table->json('auditors')->nullable();
            // $table->json('auditees')->nullable();
            // $table->text('objectives')->nullable();
            // $table->text('findings')->nullable();
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
        Schema::dropIfExists('audits');
    }
};
