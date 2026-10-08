<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            // Organisateur interne (responsable formation) — toujours un collaborateur
            $table->foreignId('organizer_user_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            // Formateur interne — null si formateur externe (champ 'formateur' string reste pour externe)
            $table->foreignId('formateur_user_id')
                ->nullable()
                ->after('organizer_user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Lien processus pour agrégation dans la revue de processus
            $table->foreignId('process_id')
                ->nullable()
                ->after('formateur_user_id')
                ->constrained('processes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropForeign(['organizer_user_id']);
            $table->dropForeign(['formateur_user_id']);
            $table->dropForeign(['process_id']);
            $table->dropColumn(['organizer_user_id', 'formateur_user_id', 'process_id']);
        });
    }
};
