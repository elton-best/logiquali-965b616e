<?php

namespace App\Support;

/**
 * Table de correspondance source_type → module/submodule/section.
 * Source unique de vérité pour classifier les documents dans l'inventaire.
 */
class DocumentSourceTypeMap
{
    /**
     * @var array<string, array{module: string, submodule: string, section: string}>
     */
    private const MAP = [
        // Contexte
        'application_scope'     => ['module' => 'context', 'submodule' => 'application_scope',    'section' => ''],
        'context_swot'          => ['module' => 'context', 'submodule' => 'swot_pestel',           'section' => ''],
        'stakeholder_register'  => ['module' => 'context', 'submodule' => 'stakeholders',          'section' => ''],

        // Support / Politique
        'qhse_policy'           => ['module' => 'support', 'submodule' => 'qhse_policy',           'section' => ''],
        'procedure'             => ['module' => 'support', 'submodule' => 'procedures',             'section' => ''],
        'procedure_annex'       => ['module' => 'support', 'submodule' => 'procedures',             'section' => 'annexes'],
        'inventory'             => ['module' => 'support', 'submodule' => 'general',               'section' => ''],

        // Management / Pilotage
        'management_review'     => ['module' => 'management', 'submodule' => 'management_review',  'section' => ''],
        'job_description'       => ['module' => 'management', 'submodule' => 'job_descriptions',   'section' => ''],
        'responsibility_sheet'  => ['module' => 'management', 'submodule' => 'responsibilities',   'section' => ''],
        'org_chart'             => ['module' => 'management', 'submodule' => 'org_chart',          'section' => ''],

        // Processus
        'process_sheet'         => ['module' => 'processes', 'submodule' => 'process_detail',      'section' => ''],
        'process_review'        => ['module' => 'processes', 'submodule' => 'process_review',      'section' => ''],
        'process_cartography'   => ['module' => 'processes', 'submodule' => 'cartography',         'section' => ''],

        // Amélioration
        'audit_report'          => ['module' => 'improvement', 'submodule' => 'audits',            'section' => 'reports'],
        'non_conformity_report' => ['module' => 'improvement', 'submodule' => 'non_conformities',  'section' => 'reports'],
        'risk_plan'             => ['module' => 'improvement', 'submodule' => 'risks',             'section' => 'plans'],
    ];

    /**
     * Résout le module/submodule/section depuis un source_type.
     * Retourne des valeurs vides si le type n'est pas mappé.
     *
     * @return array{module: string, submodule: string, section: string}
     */
    public static function resolve(string $sourceType): array
    {
        return self::MAP[$sourceType] ?? ['module' => '', 'submodule' => '', 'section' => ''];
    }

    /**
     * Retourne tous les source_types connus.
     *
     * @return string[]
     */
    public static function knownTypes(): array
    {
        return array_keys(self::MAP);
    }
}
