<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete()
                ->comment('NULL = global, sinon spécifique au site');
            
            $table->string('key')->comment('Ex: smq_type, iso_9001_enabled');
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['boolean', 'string', 'integer', 'json'])->default('string');
            
            $table->timestamps();
            
            // Contrainte d'unicité
            $table->unique(['site_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
