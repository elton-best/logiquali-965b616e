<?php

/*
|--------------------------------------------------------------------------
| Navigation Sidebar LOGIQUALI (sections réelles du projet)
|--------------------------------------------------------------------------
| IMPORTANT : les routes React de l'énoncé (factures, devis, proformas,
| bons-commande, etc.) sont fictives et ne sont PAS utilisées ici.
| Contrainte respectée : on reprend les sections réelles du code LOGIQUALI :
|  - UserController::getSidebar() (backend, clés/labels FR)
|  - ClientALayout.vue primaryMenuItems + secondaryMenuItems (frontend)
|  - Modules normatifs dynamiques (Contexte, Leadership, ...)
| Labels français d'origine conservés à l'identique.
|
| Chaque item : route (nom Laravel), label, icon (nom Reicon kebab),
| badge (optionnel), highlight (optionnel), active (patterns routeIs).
| L'état actif strict pour le tableau de bord utilise routeIs('dashboard').
| Sinon égalité OU préfixe via 'dashboard.documents.*' (équiv. isActive React).
|
| Icônes : 100% Reicon (https://github.com/dqev/reicon, MIT).
| Clone persistant local : /home/beg2/reicon. Mapping MDI/Lucide -> Reicon :
|  mdi-view-dashboard-outline / LayoutDashboard -> widget
|  Plus -> add-circle | Mes Tâches (mdi-checkbox-marked) -> check-list
|  Documents (mdi-file-document) -> file-text
|  Vérification (mdi-check-circle) -> check-circle
|  Approbation (mdi-check-decagram) -> verify
|  Nomenclature (tag) -> tag2 | Normes (book) -> book-open
|  Processus -> diagram-tree | Risques -> alert-triangle
|  Objectifs -> target | Plans SM -> calendar-check
|  Audits -> clipboard-check | Revue direction -> presentation
|  Indicateurs -> chart-line | Non-conformités -> alert-circle
|  Sites (mdi-office-building) -> buildings | Collaborateurs -> users
|  Parties intéressées -> user-heart | Communications -> bullhorn
|  Formations -> graduation-cap | Paramètres (Settings) -> settings
|  Journal sécurité -> shield | Abonnement (Crown) -> crown
|  Profil Entreprise (User/Building2) -> building
|  ChevronDown/Left/Right -> chevron-down/left/right | LogOut -> logout
*/

return [
    [
        'id' => 'general',
        'label' => 'Général',
        'icon' => 'home',
        'collapsible' => false,
        'items' => [
            [
                'route' => 'dashboard',
                'label' => "Vue d'ensemble",
                'icon' => 'widget',
                'active' => ['dashboard'],
            ],
            [
                'route' => 'dashboard.documents.create',
                'label' => 'Nouveau document',
                'icon' => 'add-circle',
                'highlight' => true,
                'active' => ['dashboard.documents.create'],
            ],
            [
                'route' => 'dashboard.tasks.index',
                'label' => 'Mes Tâches',
                'icon' => 'check-list',
                'active' => ['dashboard.tasks.*'],
            ],
        ],
    ],
    [
        'id' => 'information-documentee',
        'label' => 'Information documentée',
        'icon' => 'file-text',
        'collapsible' => true,
        'items' => [
            [
                'route' => 'dashboard.documents.index',
                'label' => 'Documents',
                'icon' => 'file-text',
                'active' => ['dashboard.documents.index', 'dashboard.documents.show'],
            ],
            [
                'route' => 'dashboard.documents.verification',
                'label' => 'Vérification',
                'icon' => 'check-circle',
                'active' => ['dashboard.documents.verification'],
            ],
            [
                'route' => 'dashboard.documents.approbation',
                'label' => 'Approbation',
                'icon' => 'verify',
                'active' => ['dashboard.documents.approbation'],
            ],
            [
                'route' => 'dashboard.documents.nomenclature',
                'label' => 'Nomenclature',
                'icon' => 'tag2',
                'active' => ['dashboard.documents.nomenclature'],
            ],
            [
                'route' => 'dashboard.norms.index',
                'label' => 'Bibliothèque des normes',
                'icon' => 'book-open',
                'badge' => 'ISO',
                'active' => ['dashboard.norms.*'],
            ],
        ],
    ],
    [
        'id' => 'pilotage',
        'label' => 'Pilotage & Planification',
        'icon' => 'diagram-tree',
        'collapsible' => true,
        'items' => [
            [
                'route' => 'dashboard.processes.index',
                'label' => 'Processus',
                'icon' => 'diagram-tree',
                'active' => ['dashboard.processes.*'],
            ],
            [
                'route' => 'dashboard.risks.index',
                'label' => 'Risques & Opportunités',
                'icon' => 'alert-triangle',
                'active' => ['dashboard.risks.*'],
            ],
            [
                'route' => 'dashboard.objectives.index',
                'label' => 'Objectifs',
                'icon' => 'target',
                'active' => ['dashboard.objectives.*'],
            ],
            [
                'route' => 'dashboard.action-plans.index',
                'label' => 'Plans du SM',
                'icon' => 'calendar-check',
                'active' => ['dashboard.action-plans.*'],
            ],
        ],
    ],
    [
        'id' => 'evaluation',
        'label' => 'Évaluation & Amélioration',
        'icon' => 'clipboard-check',
        'collapsible' => true,
        'items' => [
            [
                'route' => 'dashboard.audits.index',
                'label' => 'Audits internes',
                'icon' => 'clipboard-check',
                'active' => ['dashboard.audits.*'],
            ],
            [
                'route' => 'dashboard.reviews.index',
                'label' => 'Revue de direction',
                'icon' => 'presentation',
                'active' => ['dashboard.reviews.*'],
            ],
            [
                'route' => 'dashboard.indicators.index',
                'label' => 'Indicateurs',
                'icon' => 'chart-line',
                'active' => ['dashboard.indicators.*'],
            ],
            [
                'route' => 'dashboard.nonconformities.index',
                'label' => 'Non-conformités',
                'icon' => 'alert-circle',
                'active' => ['dashboard.nonconformities.*'],
            ],
        ],
    ],
    [
        'id' => 'organisation',
        'label' => 'Sites & Équipes',
        'icon' => 'buildings',
        'collapsible' => true,
        'items' => [
            [
                'route' => 'dashboard.sites.index',
                'label' => 'Sites',
                'icon' => 'buildings',
                'active' => ['dashboard.sites.*'],
            ],
            [
                'route' => 'dashboard.collaborators.index',
                'label' => 'Collaborateurs',
                'icon' => 'users',
                'active' => ['dashboard.collaborators.*', 'dashboard.users.*'],
            ],
            [
                'route' => 'dashboard.stakeholders.index',
                'label' => 'Parties intéressées',
                'icon' => 'user-heart',
                'active' => ['dashboard.stakeholders.*'],
            ],
            [
                'route' => 'dashboard.communications.index',
                'label' => 'Communications',
                'icon' => 'bullhorn',
                'active' => ['dashboard.communications.*'],
            ],
            [
                'route' => 'dashboard.trainings.index',
                'label' => 'Formations',
                'icon' => 'graduation-cap',
                'active' => ['dashboard.trainings.*'],
            ],
        ],
    ],
    [
        'id' => 'config',
        'label' => 'Paramètres & Compte',
        'icon' => 'settings',
        'collapsible' => true,
        'items' => [
            [
                'route' => 'dashboard.settings',
                'label' => 'Paramètres',
                'icon' => 'settings',
                'active' => ['dashboard.settings'],
            ],
            [
                'route' => 'dashboard.security.index',
                'label' => 'Journal sécurité',
                'icon' => 'shield',
                'active' => ['dashboard.security.*'],
            ],
            [
                'route' => 'dashboard.subscription',
                'label' => 'Abonnement',
                'icon' => 'crown',
                'badge' => 'Pro',
                'active' => ['dashboard.subscription'],
            ],
            [
                'route' => 'dashboard.profile',
                'label' => 'Profil Entreprise',
                'icon' => 'building',
                'active' => ['dashboard.profile'],
            ],
        ],
    ],
];
