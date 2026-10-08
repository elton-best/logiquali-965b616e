<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Axe 2 du Sprint 1 — Refactoring documentaire.
 * Ajoute les colonnes source_module, source_submodule, source_section aux documents
 * et peuple les données existantes depuis source_type via la map de correspondance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'source_module')) {
                $table->string('source_module', 150)->nullable()->after('source_type');
            }
            if (!Schema::hasColumn('documents', 'source_submodule')) {
                $table->string('source_submodule', 150)->nullable()->after('source_module');
            }
            if (!Schema::hasColumn('documents', 'source_section')) {
                $table->string('source_section', 150)->nullable()->after('source_submodule');
            }
        });

        // Peupler les colonnes pour les documents existants depuis source_type
        $map = [
            'application_scope'     => ['context',     'application_scope',   ''],
            'context_swot'          => ['context',     'swot_pestel',         ''],
            'stakeholder_register'  => ['context',     'stakeholders',        ''],
            'qhse_policy'           => ['support',     'qhse_policy',         ''],
            'procedure'             => ['support',     'procedures',          ''],
            'procedure_annex'       => ['support',     'procedures',          'annexes'],
            'inventory'             => ['support',     'general',             ''],
            'management_review'     => ['management',  'management_review',   ''],
            'job_description'       => ['management',  'job_descriptions',    ''],
            'responsibility_sheet'  => ['management',  'responsibilities',    ''],
            'org_chart'             => ['management',  'org_chart',           ''],
            'process_sheet'         => ['processes',   'process_detail',      ''],
            'process_review'        => ['processes',   'process_review',      ''],
            'audit_report'          => ['improvement', 'audits',              'reports'],
            'non_conformity_report' => ['improvement', 'non_conformities',    'reports'],
            'risk_plan'             => ['improvement', 'risks',               'plans'],
        ];

        foreach ($map as $sourceType => [$module, $submodule, $section]) {
            DB::table('documents')
                ->where('source_type', $sourceType)
                ->whereNull('source_module')
                ->update([
                    'source_module'    => $module,
                    'source_submodule' => $submodule,
                    'source_section'   => $section ?: null,
                    'updated_at'       => now(),
                ]);
        }

        // Peupler aussi depuis metadata.source_context pour les docs sans source_type mappé
        // mais qui ont déjà un source_context dans metadata (syntaxe PostgreSQL jsonb)
        DB::statement("
            UPDATE documents
            SET
                source_module    = COALESCE(NULLIF(source_module, ''),    metadata->'source_context'->>'module'),
                source_submodule = COALESCE(NULLIF(source_submodule, ''), metadata->'source_context'->>'submodule'),
                source_section   = COALESCE(NULLIF(source_section, ''),   metadata->'source_context'->>'section')
            WHERE metadata IS NOT NULL
              AND metadata->'source_context' IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['source_module', 'source_submodule', 'source_section']);
        });
    }
};
