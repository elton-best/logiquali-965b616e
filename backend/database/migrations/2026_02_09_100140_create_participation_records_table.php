<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('record_type', 100);
            $table->string('subject', 255);
            $table->text('description')->nullable();
            $table->date('date');
            $table->jsonb('participants')->nullable();
            $table->string('attendance_list_path', 500)->nullable();
            $table->string('attendance_proof_path', 500)->nullable();
            $table->text('decisions')->nullable();
            $table->jsonb('action_items')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participation_records');
    }
};
