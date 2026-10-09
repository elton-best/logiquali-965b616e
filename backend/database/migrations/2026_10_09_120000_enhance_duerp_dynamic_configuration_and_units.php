<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Table des Unités de Travail (Work Units) personnalisables ou liées à des processus/sites
        if (!Schema::hasTable('duerp_work_units')) {
            Schema::create('duerp_work_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enterprise_id')->constrained('enterprises')->cascadeOnDelete();
                $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
                $table->foreignId('duerp_id')->nullable()->constrained('duerp')->cascadeOnDelete();
                $table->foreignId('process_id')->nullable()->constrained('processes')->nullOnDelete();
                $table->string('name', 255);
                $table->string('code', 50)->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('headcount')->nullable()->default(1);
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Table des familles / types de risques DUERP configurables par entreprise
        if (!Schema::hasTable('duerp_risk_families')) {
            Schema::create('duerp_risk_families', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
                $table->string('code', 50)->nullable();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order_num')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });

            // Insérer les familles de risques INRS de référence par défaut (globales: enterprise_id = null)
            $defaultFamilies = [
                ['name' => 'Risques de trébuchement, heurt ou autre chute de plain-pied', 'description' => 'Sols glissants, encombrements, inégalités de surface.'],
                ['name' => 'Risques de chute de hauteur', 'description' => 'Escabeaux, échafaudages, toitures, fosses, passerelles.'],
                ['name' => 'Risques liés aux circulations internes de véhicules', 'description' => 'Chariots élévateurs, transpalettes, voies piétons/engins.'],
                ['name' => 'Risques routiers en mission', 'description' => 'Déplacements professionnels, véhicules de service, fatigue au volant.'],
                ['name' => 'Risques liés à la charge physique (manutention, postures, TMS)', 'description' => 'Manutention manuelle de charges, gestes répétitifs, postures inconfortables.'],
                ['name' => 'Risques liés à la manutention mécanique', 'description' => 'Ponts roulants, palans, apparaux de levage, chutes de charges.'],
                ['name' => 'Risques liés aux équipements de travail (machines, outils, écrans)', 'description' => 'Organes en mouvement, pièces en rotation, coupures, projections.'],
                ['name' => 'Risques liés aux produits, émissions et déchets (chimiques/biologiques)', 'description' => 'Solvants, acides, poussières CMR, réactions exothermiques.'],
                ['name' => 'Risques liés aux agents biologiques', 'description' => 'Bactéries, virus, moisissures, eaux usées.'],
                ['name' => 'Risques liés aux effondrements et aux chutes d\'objets', 'description' => 'Racks de stockage instables, empilements, matériaux en dévers.'],
                ['name' => 'Risques liés au bruit et ambiances sonores', 'description' => 'Machines bruyantes, outils percutants, dépassement du seuil de 80 dB(A).'],
                ['name' => 'Risques liés aux ambiances thermiques', 'description' => 'Travail au froid, canicule, chambres froides, fours, courants d’air.'],
                ['name' => 'Risques d\'incendie et d\'explosion (ATEX)', 'description' => 'Stockages inflammables, étincelles, poussières explosives.'],
                ['name' => 'Risques d\'origine électrique', 'description' => 'Armoires sous tension, câbles détériorés, contacts directs/indirects.'],
                ['name' => 'Risques liés aux ambiances lumineuses', 'description' => 'Éclairage inadapté, éblouissements, travail sur écran prolongé.'],
                ['name' => 'Risques liés aux rayonnements (ionisants et non ionisants)', 'description' => 'Laser, UV, soudure à l’arc, micro-ondes, sources radioactives.'],
                ['name' => 'Risques psychosociaux (stress, agressions, charge mentale)', 'description' => 'Stress, violences internes/externes, harcèlement, charge émotionnelle.'],
            ];

            foreach ($defaultFamilies as $idx => $fam) {
                DB::table('duerp_risk_families')->insert([
                    'enterprise_id' => null,
                    'code' => 'INRS_' . str_pad((string) ($idx + 1), 2, '0', STR_PAD_LEFT),
                    'name' => $fam['name'],
                    'description' => $fam['description'],
                    'is_active' => true,
                    'order_num' => $idx + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Table des échelles de cotation dynamiques (Gravité & Fréquence) par entreprise
        if (!Schema::hasTable('duerp_scoring_scales')) {
            Schema::create('duerp_scoring_scales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
                $table->string('scale_type', 50); // 'gravity', 'frequency', 'maitrise'
                $table->unsignedInteger('level')->default(1);
                $table->string('label', 100);
                $table->decimal('score_value', 8, 2)->default(1.0);
                $table->string('cadence', 255)->nullable();
                $table->string('consequences', 255)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insérer les échelles par défaut globales
            // Gravité (1 à 4)
            $gravities = [
                ['level' => 1, 'label' => 'Faible', 'score_value' => 1.0, 'consequences' => 'Lésions sans arrêt de travail', 'description' => 'Inconfort, accident ou maladie sans arrêt de travail, soins bénins dispensés sur place.'],
                ['level' => 2, 'label' => 'Moyenne', 'score_value' => 2.0, 'consequences' => 'Lésions avec arrêt réversible', 'description' => 'Accident ou maladie avec arrêt de travail temporaire, sans séquelles permanentes.'],
                ['level' => 3, 'label' => 'Grave', 'score_value' => 3.0, 'consequences' => 'Lésions avec arrêt et séquelles', 'description' => 'Accident ou maladie avec incapacité permanente partielle (IPP), réversible.'],
                ['level' => 4, 'label' => 'Très grave', 'score_value' => 4.0, 'consequences' => 'Incapacité permanente totale / Décès', 'description' => 'Accident mortel ou invalidité permanente lourde.'],
            ];
            foreach ($gravities as $g) {
                DB::table('duerp_scoring_scales')->insert(array_merge($g, [
                    'scale_type' => 'gravity',
                    'enterprise_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Fréquence (1 à 4)
            $frequencies = [
                ['level' => 1, 'label' => 'Faible / Occasionnelle', 'score_value' => 1.0, 'cadence' => 'Au moins 1 fois / an', 'description' => 'Exposition très ponctuelle ou exceptionnelle.'],
                ['level' => 2, 'label' => 'Moyenne / Intermittente', 'score_value' => 2.0, 'cadence' => 'Au moins 1 fois / mois', 'description' => 'Exposition périodique liée à certaines phases opératoires.'],
                ['level' => 3, 'label' => 'Grande / Fréquente', 'score_value' => 3.0, 'cadence' => 'Au moins 1 fois / semaine', 'description' => 'Exposition habituelle dans la routine hebdomadaire.'],
                ['level' => 4, 'label' => 'Très grande / Permanente', 'score_value' => 4.0, 'cadence' => 'Au moins 1 fois / jour', 'description' => 'Exposition continue tout au long de la journée de travail.'],
            ];
            foreach ($frequencies as $f) {
                DB::table('duerp_scoring_scales')->insert(array_merge($f, [
                    'scale_type' => 'frequency',
                    'enterprise_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Maîtrise (1 à 3)
            $maitrises = [
                ['level' => 1, 'label' => 'Bonne ou forte', 'score_value' => 1.0, 'description' => 'Satisfaisant à poursuivre. Mesures de prévention éprouvées.'],
                ['level' => 2, 'label' => 'Moyenne', 'score_value' => 2.0, 'description' => 'À améliorer dans le cadre du plan d’actions.'],
                ['level' => 3, 'label' => 'Faible ou absente', 'score_value' => 3.0, 'description' => 'À mettre en place ou améliorer urgemment.'],
            ];
            foreach ($maitrises as $m) {
                DB::table('duerp_scoring_scales')->insert(array_merge($m, [
                    'scale_type' => 'maitrise',
                    'enterprise_id' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 4. Enrichir la table duerp avec le processus gestionnaire et le workflow de soumission
        Schema::table('duerp', function (Blueprint $table) {
            if (!Schema::hasColumn('duerp', 'managing_process_id')) {
                $table->foreignId('managing_process_id')->nullable()->constrained('processes')->nullOnDelete()->after('work_unit_definition');
            }
            if (!Schema::hasColumn('duerp', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()->after('next_evaluation_date');
            }
            if (!Schema::hasColumn('duerp', 'submitted_at')) {
                $table->dateTime('submitted_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('duerp', 'submission_notes')) {
                $table->text('submission_notes')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('duerp', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('duerp', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_notes');
            }
            if (!Schema::hasColumn('duerp', 'rejected_at')) {
                $table->dateTime('rejected_at')->nullable()->after('rejected_by');
            }
            if (!Schema::hasColumn('duerp', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
        });

        // 5. Enrichir duerp_dangers avec les relations vers l'unité de travail, la famille de risque et les critères Excel
        Schema::table('duerp_dangers', function (Blueprint $table) {
            if (!Schema::hasColumn('duerp_dangers', 'work_unit_id')) {
                $table->foreignId('work_unit_id')->nullable()->constrained('duerp_work_units')->nullOnDelete()->after('process_id');
            }
            if (!Schema::hasColumn('duerp_dangers', 'risk_family_id')) {
                $table->foreignId('risk_family_id')->nullable()->constrained('duerp_risk_families')->nullOnDelete()->after('inrs_family');
            }
            if (!Schema::hasColumn('duerp_dangers', 'mitigation_level')) {
                $table->string('mitigation_level', 50)->nullable()->after('existing_preventions'); // Bonne/forte, Moyenne, Faible/absente
            }
            if (!Schema::hasColumn('duerp_dangers', 'mitigation_coef')) {
                $table->decimal('mitigation_coef', 4, 2)->nullable()->default(1.0)->after('mitigation_level');
            }
            if (!Schema::hasColumn('duerp_dangers', 'action_evaluation_date')) {
                $table->date('action_evaluation_date')->nullable()->after('deadline');
            }
            if (!Schema::hasColumn('duerp_dangers', 'action_effectivity_criteria')) {
                $table->text('action_effectivity_criteria')->nullable()->after('action_evaluation_date');
            }
            if (!Schema::hasColumn('duerp_dangers', 'action_efficacy_criteria')) {
                $table->text('action_efficacy_criteria')->nullable()->after('action_effectivity_criteria');
            }
            if (!Schema::hasColumn('duerp_dangers', 'observations')) {
                $table->text('observations')->nullable()->after('action_efficacy_criteria');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duerp_dangers', function (Blueprint $table) {
            $cols = ['observations', 'action_efficacy_criteria', 'action_effectivity_criteria', 'action_evaluation_date', 'mitigation_coef', 'mitigation_level', 'risk_family_id', 'work_unit_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('duerp_dangers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('duerp', function (Blueprint $table) {
            $cols = ['rejection_reason', 'rejected_at', 'rejected_by', 'approval_notes', 'submission_notes', 'submitted_at', 'submitted_by', 'managing_process_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('duerp', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('duerp_scoring_scales');
        Schema::dropIfExists('duerp_risk_families');
        Schema::dropIfExists('duerp_work_units');
    }
};

