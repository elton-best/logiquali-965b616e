<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            // Catégorie processus (4 types ISO)
            $table->enum('category', ['pilotage', 'support', 'operationnel', 'mesure_amelioration'])
                ->default('operationnel')->after('type');
            
            // Code processus unique
            $table->string('code', 50)->unique()->after('ref');
            
            // Champs Turtle Diagram
            $table->text('finalite')->nullable()->after('purpose');
            $table->json('acteurs')->nullable()->comment('Liste des rôles/responsables');
            $table->json('ressources')->nullable()->comment('Ressources matérielles, humaines, financières');
            $table->json('methodes')->nullable()->comment('Méthodes, procédures, instructions');
            $table->json('interfaces')->nullable()->comment('Clients/fournisseurs internes/externes');
            
            // Aspects QHSE (flexibilité SMQ/SMI)
            $table->boolean('aspect_qualite')->default(true);
            $table->boolean('aspect_environnement')->default(false);
            $table->boolean('aspect_sante_securite')->default(false);
            
            // Normes ISO applicables
            $table->json('normes_iso')->nullable()->comment('9001, 14001, 45001');
            
            // Statut et workflow
            $table->enum('status', ['draft', 'in_review', 'validated', 'active', 'obsolete'])
                ->default('draft')->after('is_validated');
            
            // Dates importantes
            $table->timestamp('last_review_at')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->integer('review_frequency_months')->default(12)->comment('Fréquence de revue en mois');
            
            // Version
            $table->string('version', 20)->default('1.0');
            
            // Niveau hiérarchique (pour cartographie)
            $table->foreignId('parent_process_id')->nullable()->constrained('processes')->nullOnDelete();
            $table->integer('level')->default(1)->comment('Niveau dans la hiérarchie: 1=macro, 2=méso, 3=micro');
            $table->integer('order')->default(0)->comment('Ordre d\'affichage dans la cartographie');
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table) {
            $table->dropColumn([
                'category', 'code', 'finalite', 'acteurs', 'ressources', 
                'methodes', 'interfaces', 'aspect_qualite', 'aspect_environnement',
                'aspect_sante_securite', 'normes_iso', 'status', 'last_review_at',
                'next_review_at', 'review_frequency_months', 'version',
                'parent_process_id', 'level', 'order'
            ]);
        });
    }
};
