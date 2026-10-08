<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('abbreviation', 20);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['enterprise_id', 'site_id', 'abbreviation'], 'doc_type_catalog_unique_abbr');
            $table->index(['enterprise_id', 'site_id', 'is_active'], 'doc_type_catalog_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_catalogs');
    }
};

