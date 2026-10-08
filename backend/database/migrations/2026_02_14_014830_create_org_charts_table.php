<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_current')->default(true);
            $table->foreignId('uploaded_by')->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('enterprise_id');
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_charts');
    }
};
