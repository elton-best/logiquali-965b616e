<?php

/**
 * Permission to Norm/Module Mappings
 * 
 * Defines which permissions belong to which norms and modules.
 * Used by PermissionNormMappingSeeder to populate the mapping table.
 * 
 * Format:
 * 'permission_name' => [
 *     'module_code' => 'module_code',
 *     'norm_codes' => ['ISO-9001', ...],  // null = all norms
 *     'is_shared' => true,  // true if shared across multiple norms
 * ]
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Strict mapping mode
    |--------------------------------------------------------------------------
    |
    | false (default): only explicit mappings defined below are seeded.
    | true: enable legacy fallback auto-mapping by prefix/heuristics.
    |
    */
    '__enable_auto_mapping' => false,

    /*
    |--------------------------------------------------------------------------
    | Explicit prefix mappings (strict, deterministic)
    |--------------------------------------------------------------------------
    |
    | Applied only to permissions that are still unmapped after explicit
    | permission-level entries above.
    |
    */
    '__prefix_mappings' => [
        ['prefix' => 'contexte', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'planification', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'support', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'leadership', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'evaluation', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'amelioration', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'realisation', 'module_code' => 'realisation', 'norm_codes' => ['ISO 9001:2015']],
        ['prefix' => 'processes', 'module_code' => 'realisation', 'norm_codes' => ['ISO 9001:2015']],
        ['prefix' => 'process', 'module_code' => 'realisation', 'norm_codes' => ['ISO 9001:2015']],
        ['prefix' => 'documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'document_inventories', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'create_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'edit_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'delete_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'view_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'export_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'import_documents', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'actions', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'activities', 'module_code' => 'realisation', 'norm_codes' => ['ISO 9001:2015']],
        ['prefix' => 'view_all_tasks', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'manage_public_holidays', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'verify_actions', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'configure_nomenclature', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'view_nomenclature', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'submit_for_verification', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'non_conformities', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'reclamations', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'process_reviews', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'audits', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'audit_programs', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'management_reviews', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'indicateurs', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'reports', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'risks', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'opportunities', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'objectives', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'plan_actions', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'strategic_axes', 'module_code' => 'planification', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'stakeholders', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'contexts', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'organization_contexts', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'application_scopes', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'qhse_policies', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'job_descriptions', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'responsibilities', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'team_members', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'employee_evaluations', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'client_satisfaction_forms', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'satisfaction_surveys', 'module_code' => 'evaluation', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'formations', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'communications', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'habilitations', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'equipements', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'maintenances', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'codification_elements', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'calibration_plans', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'training_plans', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'enterprises', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'sites', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'complaints', 'module_code' => 'amelioration', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'offers', 'module_code' => 'leadership', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'norm_library', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'competence_matrix', 'module_code' => 'support', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'norms', 'module_code' => 'contexte', 'norm_codes' => null, 'is_shared' => true],
        ['prefix' => 'duerps', 'module_code' => 'planification', 'norm_codes' => ['ISO 45001:2018']],
        ['prefix' => 'emergency_procedures', 'module_code' => 'realisation', 'norm_codes' => ['ISO 45001:2018']],
        ['prefix' => 'verification_reglementaires', 'module_code' => 'realisation', 'norm_codes' => ['ISO 45001:2018']],
        ['prefix' => 'aspect_environnementaux', 'module_code' => 'planification', 'norm_codes' => ['ISO 14001:2015']],
        ['prefix' => 'obligation_conformite_environnementales', 'module_code' => 'realisation', 'norm_codes' => ['ISO 14001:2015']],
        ['prefix' => 'operational_controls', 'module_code' => 'realisation', 'norm_codes' => ['ISO 14001:2015']],
        ['prefix' => 'consommation_energies', 'module_code' => 'planification', 'norm_codes' => ['ISO 50001:2018']],
        ['prefix' => 'ipes', 'module_code' => 'planification', 'norm_codes' => ['ISO 50001:2018']],
        ['prefix' => 'energie', 'module_code' => 'planification', 'norm_codes' => ['ISO 50001:2018']],
        ['prefix' => 'environnement', 'module_code' => 'planification', 'norm_codes' => ['ISO 14001:2015']],
        ['prefix' => 'securite', 'module_code' => 'realisation', 'norm_codes' => ['ISO 45001:2018']],
    ],

    // ========== SHARED MODULES (contexte, leadership, planification, support, evaluation, amelioration) ==========
    // These exist in ALL 6 norms: ISO-9001, ISO-14001, ISO-45001, ISO-27001, ISO-22000, ISO-50001
    
    'contexte.view' => [
        'module_code' => 'contexte',
        'norm_codes' => null,  // All norms
        'is_shared' => true,
    ],
    'contexte.edit' => [
        'module_code' => 'contexte',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'contexte.manage' => [
        'module_code' => 'contexte',
        'sub_module_code' => 'comprehension_organisme',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    'leadership.view' => [
        'module_code' => 'leadership',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'leadership.edit' => [
        'module_code' => 'leadership',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'leadership.manage' => [
        'module_code' => 'leadership',
        'sub_module_code' => 'roles_responsabilites',
        'section_code' => 'organigramme',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    'planification.view' => [
        'module_code' => 'planification',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'planification.edit' => [
        'module_code' => 'planification',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'planification.manage' => [
        'module_code' => 'planification',
        'sub_module_code' => 'objectifs_qualite',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    'support.view' => [
        'module_code' => 'support',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'support.edit' => [
        'module_code' => 'support',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'support.manage' => [
        'module_code' => 'support',
        'sub_module_code' => 'documents',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    'evaluation.view' => [
        'module_code' => 'evaluation',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'evaluation.edit' => [
        'module_code' => 'evaluation',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'evaluation.manage' => [
        'module_code' => 'evaluation',
        'sub_module_code' => 'audits_internes',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    'amelioration.view' => [
        'module_code' => 'amelioration',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'amelioration.edit' => [
        'module_code' => 'amelioration',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'amelioration.manage' => [
        'module_code' => 'amelioration',
        'sub_module_code' => 'non_conformites_actions',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    
    // ========== ISO 9001 SPECIFIC (realisation module) ==========
    'realisation.view' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    'realisation.edit' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    'realisation.manage' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    
    // ========== PROCESS PERMISSIONS (mapped to contexte + realisation) ==========
    'process.view' => [
        'module_code' => 'contexte',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'process.edit' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    'process.verify' => [
        'module_code' => 'evaluation',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'process.reject' => [
        'module_code' => 'leadership',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'processes.create' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    'processes.delete' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    'processes.manage' => [
        'module_code' => 'realisation',
        'norm_codes' => ['ISO 9001:2015'],
        'is_shared' => false,
    ],
    
    // ========== DOCUMENT PERMISSIONS (across all modules) ==========
    'verify_documents' => [
        'module_code' => 'leadership',
        'sub_module_code' => 'roles_responsabilites',
        'section_code' => 'liste_personnel',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'approve_documents' => [
        'module_code' => 'leadership',
        'sub_module_code' => 'roles_responsabilites',
        'section_code' => 'fiche_responsabilite',
        'norm_codes' => null,
        'is_shared' => true,
    ],
    'import_documents' => [
        'module_code' => 'support',
        'sub_module_code' => 'documents',
        'norm_codes' => null,
        'is_shared' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-mapping aliases (P0 closure)
    |--------------------------------------------------------------------------
    |
    | Used by PermissionNormMappingSeeder fallback to map permission prefixes
    | to a module code when an explicit permission mapping is not provided.
    |
    */
    '__module_aliases' => [
        'processes' => 'realisation',
        'process' => 'realisation',
        'documents' => 'support',
        'document' => 'support',
        'audit' => 'evaluation',
        'audits' => 'evaluation',
        'actions' => 'amelioration',
        'action' => 'amelioration',
        'risks' => 'planification',
        'risk' => 'planification',
        'objectives' => 'planification',
        'objective' => 'planification',
        'non_conformities' => 'amelioration',
        'non_conformity' => 'amelioration',
        'reclamations' => 'amelioration',
        'reclamation' => 'amelioration',
        'formations' => 'support',
        'formation' => 'support',
        'communications' => 'support',
        'communication' => 'support',
        'provider' => 'support',
        'providers' => 'support',
        'stakeholders' => 'contexte',
        'stakeholder' => 'contexte',
        'application_scope' => 'contexte',
        'context' => 'contexte',
        'view_all_tasks' => 'planification',
        'manage_public_holidays' => 'support',
        'verify_actions' => 'amelioration',
        'configure_nomenclature' => 'support',
        'view_nomenclature' => 'support',
        'submit_for_verification' => 'evaluation',
        'create_documents' => 'support',
        'edit_documents' => 'support',
        'delete_documents' => 'support',
        'view_documents' => 'support',
        'export_documents' => 'support',
        'import_documents' => 'support',
        'activities' => 'realisation',
        'opportunities' => 'planification',
        'application_scopes' => 'contexte',
        'qhse_policies' => 'leadership',
        'job_descriptions' => 'leadership',
        'responsibilities' => 'leadership',
        'team_members' => 'leadership',
        'strategic_axes' => 'planification',
        'reports' => 'evaluation',
        'habilitations' => 'support',
        'equipements' => 'support',
        'maintenances' => 'support',
        'codification_elements' => 'support',
        'calibration_plans' => 'support',
        'training_plans' => 'support',
        'duerps' => 'planification',
        'emergency_procedures' => 'realisation',
        'verification_reglementaires' => 'evaluation',
        'aspect_environnementaux' => 'planification',
        'obligation_conformite_environnementales' => 'planification',
        'operational_controls' => 'realisation',
        'consommation_energies' => 'evaluation',
        'ipes' => 'evaluation',
        'enterprises' => 'leadership',
        'sites' => 'leadership',
        'complaints' => 'amelioration',
        'offers' => 'support',
        'norm_library' => 'contexte',
        'competence_matrix' => 'support',
        'norms' => 'contexte',
        'securite' => 'support',
        'environnement' => 'planification',
        'energie' => 'evaluation',
    ],
];
