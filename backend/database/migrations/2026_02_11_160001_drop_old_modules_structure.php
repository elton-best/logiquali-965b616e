<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les permissions liées aux modules
        DB::table('permissions')->where('name', 'like', 'modules.%')->delete();
        
        // Supprimer les tables
        Schema::dropIfExists('norm_module');
        Schema::dropIfExists('modules');
    }

    public function down(): void
    {
        // Recréer la structure basique (sans données)
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('category')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('norm_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_id')->constrained()->onDelete('cascade');
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }
};
