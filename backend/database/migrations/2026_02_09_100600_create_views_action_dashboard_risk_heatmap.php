<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW v_action_dashboard AS
        SELECT 
            a.site_id,
            COUNT(*) AS total_actions,
            COUNT(*) FILTER (WHERE a.status = 'completed') AS completed_actions,
            COUNT(*) FILTER (WHERE a.status = 'in_progress') AS in_progress_actions,
            COUNT(*) FILTER (WHERE a.status = 'pending') AS pending_actions,
            COUNT(*) FILTER (WHERE a.deadline < CURRENT_DATE AND a.status <> 'completed') AS overdue_actions,
            AVG(a.progress_rate) AS avg_progress_rate,
            AVG(
                CASE 
                    WHEN a.deadline < CURRENT_DATE 
                    THEN CURRENT_DATE - a.deadline 
                    ELSE 0 
                END
            ) AS avg_delay_days
        FROM actions a
        WHERE a.deleted_at IS NULL
        GROUP BY a.site_id;
        SQL);

        DB::unprepared(<<<SQL
        CREATE OR REPLACE VIEW v_risk_heatmap AS
        SELECT 
            r.site_id,
            r.probability_score,
            r.severity_score,
            r.criticality_level,
            COUNT(*) AS risk_count
        FROM risks r
        WHERE r.deleted_at IS NULL
        GROUP BY 
            r.site_id,
            r.probability_score,
            r.severity_score,
            r.criticality_level;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS v_action_dashboard');
        DB::unprepared('DROP VIEW IF EXISTS v_risk_heatmap');
    }
};
