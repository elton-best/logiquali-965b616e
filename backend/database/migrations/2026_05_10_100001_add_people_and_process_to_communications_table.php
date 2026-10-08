<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            // Organisateur interne (responsable de l'organisation) — toujours un collaborateur
            $table->foreignId('organizer_user_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            // Chargé de com/sensibilisation interne — null si externe (champ 'responsable' string reste pour externe)
            $table->foreignId('responsible_user_id')
                ->nullable()
                ->after('organizer_user_id')
                ->constrained('users')
                ->nullOnDelete();

            // Participants internes (collaborateurs) — array d'IDs
            $table->json('participant_user_ids')
                ->nullable()
                ->after('responsible_user_id');

            // Lien processus pour agrégation dans la revue de processus
            $table->foreignId('process_id')
                ->nullable()
                ->after('participant_user_ids')
                ->constrained('processes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropForeign(['organizer_user_id']);
            $table->dropForeign(['responsible_user_id']);
            $table->dropForeign(['process_id']);
            $table->dropColumn(['organizer_user_id', 'responsible_user_id', 'participant_user_ids', 'process_id']);
        });
    }
};
