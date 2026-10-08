<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_catalog_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_catalog_id')
                ->constrained('document_type_catalogs')
                ->cascadeOnDelete();
            $table->foreignId('process_id')
                ->constrained('processes')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['document_type_catalog_id', 'process_id'], 'doc_type_catalog_process_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_catalog_process');
    }
};

