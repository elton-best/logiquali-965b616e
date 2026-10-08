<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competences_acquises', function (Blueprint $table) {
            $table->foreign('formation_id')->references('id')->on('formations')->onDelete('set null');
            $table->foreign('habilitation_id')->references('id')->on('habilitations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('competences_acquises', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropForeign(['habilitation_id']);
        });
    }
};