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
        Schema::create('non_conformities', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('audit_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title')->nullable();
            $table->enum('type', ['normative', 'legal', 'regulatory', 'contractual', 'procedural']);
            $table->enum('severity', ['minor', 'major', 'critical']);
            $table->text('description');

            $table->foreignId('detected_by')->constrained('users')->nullOnDelete();
            $table->date('detected_at')->default(now());
            $table->text('root_cause_analysis')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'verified', 'closed'])->default('open');

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
        Schema::dropIfExists('non_conformities');
    }
};
