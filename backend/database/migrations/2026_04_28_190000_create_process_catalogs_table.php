<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('abbreviation', 30);
            $table->string('internal_code', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique(['enterprise_id', 'site_id', 'abbreviation'], 'process_catalog_unique_abbr');
            $table->index(['enterprise_id', 'site_id', 'is_active'], 'process_catalog_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_catalogs');
    }
};
