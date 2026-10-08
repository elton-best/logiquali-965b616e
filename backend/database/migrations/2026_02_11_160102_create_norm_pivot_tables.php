<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('norm_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['norm_id', 'module_id']);
        });

        Schema::create('norm_sub_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_module_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['norm_id', 'sub_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('norm_sub_module');
        Schema::dropIfExists('norm_module');
    }
};
