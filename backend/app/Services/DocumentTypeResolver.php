<?php

namespace App\Services;

/**
 * Résolveur centralisé du type documentaire par contexte de module.
 *
 * Mappe chaque contexte de module à son type documentaire par défaut.
 * Utilisé par tous les contrôleurs d'export pour éviter le codage en dur
 * du type documentaire dans chaque contrôleur.
 *
 * Types disponibles :
 * - POL : Politique (politique QHSE, politique qualité…)
 * - PRC : Procédure (procédures opérationnelles)
 * - PRD : Instruction (fiches processus, instructions de travail)
 * - FOR : Formulaire (formulaires, modèles)
 * - ENR : Enregistrement (registres, rapports, preuves — type par défaut)
 */
class DocumentTypeResolver
{
    /**
     * Mapping contexte → type documentaire.
     *
     * @var array<string, string>
     */
    private const TYPE_MAP = [
        // Point 5 — Leadership
        'qhse_policy'            => 'POL',
        'politique_qhse'         => 'POL',

        // Point 4 — Contexte (fiches processus = instruction de travail)
        'process_sheet'          => 'PRD',
        'fiche_processus'        => 'PRD',

        // Procédures
        'procedure'              => 'PRC',
        'procedure_doc'          => 'PRC',

        // Formulaires
        'form'                   => 'FOR',
        'formulaire'             => 'FOR',

        // Tout le reste = Enregistrement (type par défaut)
        'stakeholder_register'   => 'ENR',
        'registre_parties'       => 'ENR',
        'context_swot'           => 'ENR',
        'swot_pestel'            => 'ENR',
        'application_scope'      => 'ENR',
        'domaine_application'    => 'ENR',
        'risk_opportunity'       => 'ENR',
        'risques_opportunites'   => 'ENR',
        'objective'              => 'ENR',
        'objectif'               => 'ENR',
        'job_description'        => 'ENR',
        'fiche_poste'            => 'ENR',
        'responsibility_sheet'   => 'ENR',
        'fiche_responsabilite'   => 'ENR',
        'compliance_obligation'  => 'ENR',
        'obligations_conformite' => 'ENR',
        'formation'              => 'ENR',
        'communication'          => 'ENR',
        'audit'                  => 'ENR',
        'non_conformity'         => 'ENR',
        'non_conformite'         => 'ENR',
        'action_plan'            => 'ENR',
        'plan_action'            => 'ENR',
        'plan_sm'                => 'ENR',
        'provider_document'      => 'ENR',
        'prestataire'            => 'ENR',
        'design_development'     => 'PRC',
        'conception_developpement' => 'PRC',
    ];

    /**
     * Résout le type documentaire pour un contexte de module donné.
     *
     * @param string $moduleContext Le contexte du module (ex: 'qhse_policy', 'process_sheet'…)
     * @return string L'abréviation du type documentaire (ex: 'POL', 'ENR'…)
     */
    public static function resolveType(string $moduleContext): string
    {
        $normalized = mb_strtolower(trim($moduleContext));

        return self::TYPE_MAP[$normalized] ?? 'ENR';
    }

    /**
     * Retourne tous les types disponibles avec leur description.
     *
     * @return array<string, string>
     */
    public static function availableTypes(): array
    {
        return [
            'POL' => 'Politique',
            'PRC' => 'Procédure',
            'PRD' => 'Instruction',
            'FOR' => 'Formulaire',
            'ENR' => 'Enregistrement',
        ];
    }
}
