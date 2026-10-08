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
            $table->foreignId('norm_id')->constrained('norms')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['norm_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('norm_module');
    }
};
