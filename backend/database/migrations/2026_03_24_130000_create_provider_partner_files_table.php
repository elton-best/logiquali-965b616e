<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_partner_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_partner_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('category', 80)->default('piece');
            $table->text('note')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_mime', 128)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();

            $table->index(['enterprise_id', 'provider_partner_id'], 'provider_partner_files_enterprise_provider_index');
            $table->index(['provider_partner_id', 'category'], 'provider_partner_files_provider_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_partner_files');
    }
};

