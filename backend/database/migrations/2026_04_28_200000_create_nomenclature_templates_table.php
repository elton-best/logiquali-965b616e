<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomenclature_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_catalog_id')->nullable()->constrained('document_type_catalogs')->nullOnDelete();
            $table->foreignId('process_catalog_id')->nullable()->constrained('process_catalogs')->nullOnDelete();
            $table->string('name', 180);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft'); // draft|published|archived
            $table->json('format_structure');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['enterprise_id', 'site_id', 'status'], 'nomenclature_templates_scope_status_idx');
            $table->index(['enterprise_id', 'site_id', 'document_type_catalog_id', 'process_catalog_id'], 'nomenclature_templates_resolution_idx');
            $table->unique(['enterprise_id', 'site_id', 'document_type_catalog_id', 'process_catalog_id', 'version'], 'nomenclature_templates_scope_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomenclature_templates');
    }
};
