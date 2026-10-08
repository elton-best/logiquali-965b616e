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
        Schema::create('audit_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('process_id')->constrained('processes')->cascadeOnDelete();
            $table->text('requirement');
            $table->text('question');
            $table->text('evidence')->nullable();
            $table->enum('conformity_status', ['conformity', 'minor_nc', 'major_nc', 'observation'])->default('not_applicable');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('audit_checklist_items');
    }
};
