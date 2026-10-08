<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('version', 50)->default('1.0');
            $table->boolean('is_current')->default(true);
            $table->text('objective');
            $table->text('scope');
            $table->jsonb('included_processes')->nullable();
            $table->jsonb('products_services')->nullable();
            $table->jsonb('organizational_units')->nullable();
            $table->jsonb('locations')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('exclusions_justification')->nullable();
            $table->jsonb('applicable_norms')->nullable();
            $table->boolean('document_generated')->default(false);
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
        Schema::dropIfExists('application_scopes');
    }
};
