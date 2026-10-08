<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qhse_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('version', 50)->default('1.0');
            $table->boolean('is_current')->default(true);
            $table->date('effective_date')->nullable();
            $table->text('mission');
            $table->text('vision')->nullable();
            $table->jsonb('values')->nullable();
            $table->jsonb('commitments')->nullable();
            $table->text('quality_policy')->nullable();
            $table->text('environmental_policy')->nullable();
            $table->text('health_safety_policy')->nullable();
            $table->string('status', 50)->default('draft');
            $table->timestamp('validated_by_direction_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_path', 500)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('enterprise_id');
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qhse_policies');
    }
};
