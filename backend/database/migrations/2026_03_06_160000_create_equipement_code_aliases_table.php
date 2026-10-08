<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipement_code_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipement_id')->constrained('equipements')->onDelete('cascade');
            $table->foreignId('enterprise_id')->constrained()->onDelete('cascade');
            $table->string('code_alias');
            $table->string('change_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['enterprise_id', 'code_alias'], 'equip_code_aliases_ent_code_unique');
            $table->index(['equipement_id', 'created_at'], 'equip_code_aliases_equipement_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipement_code_aliases');
    }
};

