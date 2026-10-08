<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Champs de DocumentInventory absents de Document
            $table->string('processus', 100)->nullable()->after('process_id');
            $table->string('etat', 50)->nullable()->after('status'); // a_etablir, en_cours, termine
            $table->integer('periodicite_revision')->nullable()->after('review_due_date'); // en mois
            $table->date('date_revision')->nullable()->after('periodicite_revision');
            $table->date('prochaine_revision')->nullable()->after('date_revision');
            
            // Mapping des champs existants (commentaires pour documentation)
            // 'nom' → 'title' (déjà existe)
            // 'fichier' → 'file_path' (déjà existe)
            // 'created_by' → 'author_id' (déjà existe)
            // 'validated_by' → 'approver_id' (déjà existe)
            // 'validated_at' → 'approved_at' (déjà existe)
            // Ancienne nomenclature legacy supprimée: utiliser nomenclature_template_id.
            
            // Index pour performance
            $table->index('processus');
            $table->index('etat');
            $table->index(['site_id', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['processus']);
            $table->dropIndex(['etat']);
            $table->dropIndex(['site_id', 'etat']);
            
            $table->dropColumn([
                'processus',
                'etat',
                'periodicite_revision',
                'date_revision',
                'prochaine_revision',
            ]);
        });
    }
};
