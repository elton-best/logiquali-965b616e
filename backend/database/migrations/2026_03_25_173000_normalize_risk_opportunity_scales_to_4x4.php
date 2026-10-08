<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('process_risks_opportunities')) {
            DB::statement("
                UPDATE process_risks_opportunities
                SET
                    probabilite = LEAST(COALESCE(probabilite, 1), 4),
                    gravite = LEAST(COALESCE(gravite, 1), 4),
                    probabilite_residuelle = CASE
                        WHEN probabilite_residuelle IS NULL THEN NULL
                        ELSE LEAST(probabilite_residuelle, 4)
                    END,
                    gravite_residuelle = CASE
                        WHEN gravite_residuelle IS NULL THEN NULL
                        ELSE LEAST(gravite_residuelle, 4)
                    END
            ");

            DB::statement("
                UPDATE process_risks_opportunities
                SET
                    criticite = COALESCE(probabilite, 1) * COALESCE(gravite, 1),
                    niveau = CASE
                        WHEN (COALESCE(probabilite, 1) * COALESCE(gravite, 1)) >= 12 THEN 'critique'
                        WHEN (COALESCE(probabilite, 1) * COALESCE(gravite, 1)) >= 8 THEN 'eleve'
                        WHEN (COALESCE(probabilite, 1) * COALESCE(gravite, 1)) >= 4 THEN 'moyen'
                        ELSE 'faible'
                    END,
                    criticite_residuelle = CASE
                        WHEN probabilite_residuelle IS NOT NULL AND gravite_residuelle IS NOT NULL
                            THEN probabilite_residuelle * gravite_residuelle
                        ELSE NULL
                    END
            ");
        }

        if (Schema::hasTable('risks')) {
            DB::statement("
                UPDATE risks
                SET
                    probability = LEAST(COALESCE(probability, 1), 4),
                    gravity = LEAST(COALESCE(gravity, 1), 4),
                    impact = LEAST(COALESCE(impact, 1), 4),
                    probability_score = LEAST(COALESCE(probability_score, probability, 1), 4),
                    severity_score = LEAST(COALESCE(severity_score, gravity, impact, 1), 4),
                    residual_probability = CASE
                        WHEN residual_probability IS NULL THEN NULL
                        ELSE LEAST(residual_probability, 4)
                    END,
                    residual_gravity = CASE
                        WHEN residual_gravity IS NULL THEN NULL
                        ELSE LEAST(residual_gravity, 4)
                    END
            ");

            DB::statement("
                UPDATE risks
                SET
                    criticality = CASE
                        WHEN type = 'opportunity'
                            THEN LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(impact, gravity, 1), 4)
                        ELSE LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(gravity, impact, 1), 4)
                    END,
                    criticality_score = LEAST(COALESCE(probability_score, probability, 1), 4)
                        * LEAST(COALESCE(severity_score, gravity, impact, 1), 4),
                    criticality_level = CASE
                        WHEN (
                            CASE
                                WHEN type = 'opportunity'
                                    THEN LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(impact, gravity, 1), 4)
                                ELSE LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(gravity, impact, 1), 4)
                            END
                        ) >= 12 THEN 'critical'
                        WHEN (
                            CASE
                                WHEN type = 'opportunity'
                                    THEN LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(impact, gravity, 1), 4)
                                ELSE LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(gravity, impact, 1), 4)
                            END
                        ) >= 8 THEN 'high'
                        WHEN (
                            CASE
                                WHEN type = 'opportunity'
                                    THEN LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(impact, gravity, 1), 4)
                                ELSE LEAST(COALESCE(probability, 1), 4) * LEAST(COALESCE(gravity, impact, 1), 4)
                            END
                        ) >= 4 THEN 'medium'
                        ELSE 'low'
                    END
            ");
        }
    }

    public function down(): void
    {
        // Impossible de restaurer automatiquement les anciennes valeurs > 4.
    }
};

