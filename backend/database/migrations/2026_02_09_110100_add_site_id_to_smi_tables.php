<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // application_scopes
        if (!Schema::hasColumn('application_scopes', 'site_id')) {
            Schema::table('application_scopes', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE application_scopes a
                SET site_id = s.id
                FROM sites s
                WHERE a.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = a.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // qhse_policies
        if (!Schema::hasColumn('qhse_policies', 'site_id')) {
            Schema::table('qhse_policies', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE qhse_policies q
                SET site_id = s.id
                FROM sites s
                WHERE q.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = q.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // participation_records
        if (!Schema::hasColumn('participation_records', 'site_id')) {
            Schema::table('participation_records', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE participation_records p
                SET site_id = s.id
                FROM sites s
                WHERE p.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = p.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // compliance_requirements
        if (!Schema::hasColumn('compliance_requirements', 'site_id')) {
            Schema::table('compliance_requirements', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE compliance_requirements c
                SET site_id = s.id
                FROM sites s
                WHERE c.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = c.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // training_plans
        if (!Schema::hasColumn('training_plans', 'site_id')) {
            Schema::table('training_plans', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE training_plans t
                SET site_id = s.id
                FROM sites s
                WHERE t.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = t.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // communication_actions
        if (!Schema::hasColumn('communication_actions', 'site_id')) {
            Schema::table('communication_actions', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE communication_actions c
                SET site_id = s.id
                FROM sites s
                WHERE c.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = c.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // operational_controls (site from process)
        if (!Schema::hasColumn('operational_controls', 'site_id')) {
            Schema::table('operational_controls', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE operational_controls o
                SET site_id = p.site_id
                FROM processes p
                WHERE o.process_id = p.id
            SQL);
        }

        // ai_suggestions
        if (!Schema::hasColumn('ai_suggestions', 'site_id')) {
            Schema::table('ai_suggestions', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('enterprise_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE ai_suggestions a
                SET site_id = s.id
                FROM sites s
                WHERE a.enterprise_id = s.enterprise_id
                AND (s.is_headquarter = true OR s.id = (
                    SELECT id FROM sites s2 WHERE s2.enterprise_id = a.enterprise_id ORDER BY s2.id LIMIT 1
                ))
            SQL);
        }

        // collaborator_action_confirmations (site from action)
        if (!Schema::hasColumn('collaborator_action_confirmations', 'site_id')) {
            Schema::table('collaborator_action_confirmations', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('action_id')->constrained('sites')->nullOnDelete();
            });
            DB::statement(<<<SQL
                UPDATE collaborator_action_confirmations c
                SET site_id = a.site_id
                FROM actions a
                WHERE c.action_id = a.id
            SQL);
        }

        // equipment already has site_id, environmental_aspects has site_id, duerp has site_id
    }

    public function down(): void
    {
        $tables = [
            'application_scopes',
            'qhse_policies',
            'participation_records',
            'compliance_requirements',
            'training_plans',
            'communication_actions',
            'operational_controls',
            'ai_suggestions',
            'collaborator_action_confirmations',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'site_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign(['site_id']);
                    $table->dropColumn('site_id');
                });
            }
        }
    }
};
