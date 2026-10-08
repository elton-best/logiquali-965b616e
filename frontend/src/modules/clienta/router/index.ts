import type { RouteRecordRaw } from 'vue-router'

export const clientaRoutes: RouteRecordRaw[] = [
  {
    path: '/company',
    redirect: '/company/dashboard',
    meta: {
      requiresAuth: true,
      // requiredRole removed - using path check instead
    },
    children: [
      {
        path: 'subscription',
        name: 'company-subscription',
        component: () => import('@/modules/clienta/pages/subscription.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['subscriptions.read'],
        },
      },
      {
        path: 'dashboard',
        name: 'company-dashboard',
        component: () => import('@/modules/clienta/pages/dashboard.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'documents',
        name: 'company-documents',
        component: () => import('@/modules/clienta/pages/documents/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.documents.read'],
        },
      },
      {
        path: 'norm-library',
        name: 'company-norm-library',
        component: () => import('@/modules/clienta/pages/norm-library/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['norm_library.read'],
        },
      },
      {
        path: 'support.documents.create',
        name: 'company-document-create',
        component: () => import('@/modules/clienta/pages/documents/create.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.documents.create'],
        },
      },
      {
        path: 'documents/verification',
        name: 'company-document-verification',
        component: () => import('@/modules/clienta/pages/documents/verification.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['verify_documents'],
        },
      },
      {
        path: 'documents/approbation',
        name: 'company-document-approval',
        component: () => import('@/modules/clienta/pages/documents/approbation.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['approve_documents'],
        },
      },
      {
        path: 'documents/nomenclature',
        name: 'company-document-nomenclature',
        component: () => import('@/modules/clienta/pages/documents/nomenclature.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.documents.read'],
        },
      },
      {
        path: 'documents/:id',
        name: 'company-document-detail',
        component: () => import('@/modules/clienta/pages/documents/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.documents.read'],
        },
      },
      {
        path: 'nonconformities',
        name: 'company-nonconformities',
        component: () =>
          import('@/modules/clienta/pages/nonconformities/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
        },
      },
      {
        path: 'nonconformities/create',
        name: 'company-nonconformity-create',
        component: () =>
          import('@/modules/clienta/pages/nonconformities/create.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.create'],
        },
      },
      {
        path: 'nonconformities/:id',
        name: 'company-nonconformity-detail',
        component: () =>
          import('@/modules/clienta/pages/nonconformities/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
        },
      },
      {
        path: 'audits',
        name: 'company-audits',
        redirect: '/company/performance/audits',
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
        },
      },
      {
        path: 'evaluation.audits.create',
        name: 'company-audit-create',
        component: () => import('@/modules/clienta/pages/audits/create.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.create'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Audits internes', to: '/company/performance/audits' },
            { title: 'Nouveau plan d\'audit', disabled: true },
          ],
        },
      },
      {
        path: 'audits/:id',
        name: 'company-audit-detail',
        component: () => import('@/modules/clienta/pages/audits/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Audits internes', to: '/company/performance/audits' },
            { title: 'Détail audit', disabled: true },
          ],
        },
      },
      {
        path: 'management-reviews',
        name: 'company-management-reviews',
        component: () =>
          import('@/modules/clienta/pages/management-reviews/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Revue de direction', to: '/company/performance/revue-direction' },
            { title: 'Registre des revues', disabled: true },
          ],
        },
      },
      {
        path: 'management-reviews/:id',
        name: 'company-management-review-detail',
        component: () =>
          import('@/modules/clienta/pages/management-reviews/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Revue de direction', to: '/company/performance/revue-direction' },
            { title: 'Registre des revues', to: '/company/management-reviews' },
            { title: 'Détail revue', disabled: true },
          ],
        },
      },
      {
        path: 'actions',
        name: 'company-actions',
        component: () => import('@/modules/clienta/pages/actions/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
        },
      },
      {
        path: 'risks',
        name: 'company-risks',
        component: () => import('@/modules/clienta/pages/risks/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['planification.risques_opportunites.read'],
        },
      },
      {
        path: 'indicators',
        name: 'company-indicators',
        component: () => import('@/modules/clienta/pages/indicators/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
        },
      },
      {
        path: 'indicators/:id',
        name: 'company-indicator-detail',
        component: () => import('@/modules/clienta/pages/indicators/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
        },
      },
      {
        path: 'collaborators',
        name: 'company-collaborators',
        component: () =>
          import('@/modules/clienta/pages/collaborators/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.personnel.read', 'leadership.roles_responsabilites.personnel.read'],
        },
      },
      {
        path: 'users',
        name: 'company-users',
        component: () => import('@/modules/clienta/pages/users/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.personnel.read', 'users.read', 'leadership.roles_responsabilites.personnel.read'],
        },
      },
      {
        path: 'users/create',
        name: 'company-users-create',
        component: () => import('@/modules/clienta/pages/users/create.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.personnel.create', 'users.create'],
        },
      },
      {
        path: 'users/:id/permissions',
        name: 'company-user-permissions',
        component: () =>
          import('@/modules/clienta/pages/users/permissions.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.personnel.update', 'users.manage', 'users.update'],
        },
      },
      {
        path: 'users/:id',
        name: 'company-user-detail',
        component: () => import('@/modules/clienta/pages/users/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.personnel.read', 'users.read', 'leadership.roles_responsabilites.personnel.read'],
        },
      },
      {
        path: 'roles',
        name: 'company-roles',
        component: () => import('@/modules/clienta/pages/roles/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['roles.read'],
        },
      },
      {
        path: 'sites',
        name: 'company-sites',
        component: () => import('@/modules/clienta/pages/sites/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['sites.read'],
        },
      },
      {
        path: 'objectives',
        name: 'company-objectives',
        component: () => import('@/modules/clienta/pages/indicators/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['planification.objectifs.read', 'planification.objectifs.read'],
        },
      },
      {
        path: 'reclamations',
        name: 'company-reclamations',
        component: () =>
          import('@/modules/clienta/pages/reclamations/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['realisation.sorties_non_conformes.manage_complaints'],
        },
      },
      {
        path: 'settings',
        name: 'company-settings',
        component: () => import('@/modules/clienta/pages/settings/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['settings.read'],
        },
      },
      {
        path: 'security-audit-logs',
        name: 'company-security-audit-logs',
        component: () =>
          import('@/modules/clienta/pages/security/SecurityAuditLogs.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['security_audit.read'],
        },
      },
      {
        path: 'profile',
        name: 'company-profile',
        redirect: '/company/settings?tab=profil',
        meta: {
          requiresAuth: true,
          // Compat legacy: profil unifié dans Paramètres > Profil
        },
      },
      {
        path: 'notifications',
        name: 'company-notifications',
        component: () =>
          import('@/modules/clienta/pages/notifications/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'subscriptions',
        name: 'company-subscriptions',
        redirect: '/company/subscription',
        meta: {
          requiresAuth: true,
          // requiredRole removed - using path check instead
        },
      },
      {
        path: 'reports',
        name: 'company-reports',
        component: () => import('@/modules/clienta/pages/reports/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'reports/:id',
        name: 'company-report-detail',
        component: () => import('@/modules/clienta/pages/reports/[id].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },

      // ============================================
      // ISO 9001 Routes (Points 4-7)
      // ============================================

      // Point 4 - Contexte de l'organisme
      {
        path: 'contexte/profil',
        redirect: '/company/context/management-system',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'contexte/parties-interessees',
        redirect: '/company/context/stakeholders',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'contexte/domaine-application',
        redirect: '/company/context/application-scope',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'contexte/systeme-management',
        redirect: '/company/context/management-system',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'context/application-scope',
        name: 'company-iso-application-scope',
        component: () =>
          import('@/modules/clienta/pages/context/ApplicationScope.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            { title: 'Domaine d\'application', disabled: true },
          ],
        },
      },
      {
        path: 'context/application-scope/recap',
        name: 'company-iso-application-scope-recap',
        component: () =>
          import('@/modules/clienta/pages/context/ApplicationScopeRecap.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            {
              title: 'Domaine d\'application',
              to: '/company/context/application-scope',
            },
            { title: 'Récapitulatif', disabled: true },
          ],
        },
      },
      {
        path: 'context/swot-pestel',
        name: 'company-iso-swot-pestel',
        component: () =>
          import('@/modules/clienta/pages/context/SWOTPestel.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            { title: 'SWOT / PESTEL', disabled: true },
          ],
        },
      },
      {
        path: 'context/stakeholders',
        name: 'company-iso-stakeholders',
        component: () =>
          import('@/modules/clienta/pages/context/Stakeholders.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            { title: 'Parties intéressées', disabled: true },
          ],
        },
      },
      {
        path: 'context/management-system',
        name: 'company-iso-management-system',
        component: () =>
          import('@/modules/clienta/pages/context/ManagementSystem.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            { title: 'Système de management', disabled: true },
          ],
        },
      },
      {
        path: 'context/management-system/:id',
        name: 'company-iso-process-details',
        component: () =>
          import('@/modules/clienta/pages/context/ProcessDetails.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 4 - Contexte', disabled: true },
            {
              title: 'Système de management',
              to: '/company/context/management-system',
            },
            { title: 'Détails du processus', disabled: true },
          ],
        },
      },

      // Point 5 - Leadership (Sprint 2)
      {
        path: 'leadership/policy',
        name: 'company-leadership-policy',
        component: () =>
          import('@/modules/clienta/pages/leadership/Policy.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.politique.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Politique QHSE', disabled: true },
          ],
        },
      },
      //  Route DB-compatible pour Politique QHSE
      {
        path: 'leadership/politique',
        name: 'company-leadership-politique',
        component: () =>
          import('@/modules/clienta/pages/leadership/Policy.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.politique.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Politique QHSE', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/organization-chart',
        name: 'company-leadership-organization-chart',
        component: () =>
          import('@/modules/clienta/pages/leadership/OrgChart.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.organigramme.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Organigramme', disabled: true },
          ],
        },
      },
      //  Route DB-compatible pour Organigramme
      {
        path: 'leadership/organigramme',
        name: 'company-leadership-organigramme',
        component: () =>
          import('@/modules/clienta/pages/leadership/OrgChart.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.organigramme.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Organigramme', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/personnel',
        name: 'company-leadership-personnel',
        component: () =>
          import('@/modules/clienta/pages/leadership/PersonnelList.vue'),
        meta: {
          requiresAuth: true,
          permissions: [
            'leadership.roles_responsabilites.personnel.read',
            'leadership.roles_responsabilites.personnel.read',
          ],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Liste du personnel', disabled: true },
          ],
        },
      },
      //  Route DB-compatible pour Liste du personnel
      {
        path: 'leadership/liste_personnel',
        name: 'company-leadership-liste-personnel',
        component: () =>
          import('@/modules/clienta/pages/leadership/PersonnelList.vue'),
        meta: {
          requiresAuth: true,
          permissions: [
            'leadership.roles_responsabilites.personnel.read',
            'leadership.roles_responsabilites.personnel.read',
          ],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Liste du personnel', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/job-description',
        name: 'company-leadership-job-description',
        component: () =>
          import('@/modules/clienta/pages/leadership/JobDescription.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_poste.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de poste', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/job-descriptions',
        name: 'company-leadership-job-descriptions-legacy',
        component: () =>
          import('@/modules/clienta/pages/leadership/JobDescription.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_poste.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de poste', disabled: true },
          ],
        },
      },
      //  Route DB-compatible pour Fiche de poste
      {
        path: 'leadership/fiche_poste',
        name: 'company-leadership-fiche-poste',
        component: () =>
          import('@/modules/clienta/pages/leadership/JobDescription.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_poste.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de poste', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/roles',
        name: 'company-leadership-roles',
        component: () =>
          import('@/modules/clienta/pages/leadership/ResponsibilitySheet.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_responsabilite.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de responsabilité', disabled: true },
          ],
        },
      },
      {
        path: 'leadership/consultation',
        name: 'company-leadership-consultation',
        component: () =>
          import('@/modules/clienta/pages/leadership/ResponsibilitySheet.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_responsabilite.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Consultation et participation', disabled: true },
          ],
        },
      },
      //  Route DB-compatible pour Fiche de responsabilité
      {
        path: 'leadership/fiche_responsabilite',
        name: 'company-leadership-fiche-responsabilite',
        component: () =>
          import('@/modules/clienta/pages/leadership/ResponsibilitySheet.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_responsabilite.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de responsabilité', disabled: true },
          ],
        },
      },
      {
        path: 'responsibilities',
        name: 'company-responsibilities-legacy',
        component: () =>
          import('@/modules/clienta/pages/leadership/ResponsibilitySheet.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['leadership.roles_responsabilites.fiche_responsabilite.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 5 - Leadership', disabled: true },
            { title: 'Fiche de responsabilité', disabled: true },
          ],
        },
      },

      //  Routes à 3 niveaux DÉPLACÉES vers iso.ts pour meilleure priorité
      // Voir: frontend/src/router/modules/iso.ts

      // Point 6 - Planification (Sprint 3)
      {
        path: 'planification/risques',
        redirect: '/company/planning/risks-opportunities',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'planification/objectifs',
        redirect: '/company/planning/objectives',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'planification/plans-action',
        redirect: '/company/planning/action-plans',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'planning/aspects-environmentaux',
        redirect: '/company/planning/risks-opportunities',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'planning/compliance-obligations',
        redirect: '/company/operations/compliance-obligations',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'planning/environmental-review',
        redirect: '/company/planning/risks-opportunities',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'support/competencies',
        redirect: '/company/support/training',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'support/resources',
        redirect: '/company/support/equipment',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'support/documents',
        redirect: '/company/support/document-inventory',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'non-conformities',
        redirect: '/company/nonconformities',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/indicators',
        redirect: '/company/indicators',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/management-review',
        redirect: '/company/management-reviews',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/audit-interne',
        redirect: '/company/audits',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/internal-audit',
        redirect: '/company/audits',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/evaluations',
        name: 'company-iso-performance-evaluations',
        component: () =>
          import('@/modules/clienta/pages/performance/evaluations.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 9 - Évaluation', disabled: true },
            { title: 'Évaluations de performance', disabled: true },
          ],
        },
      },
      {
        path: 'performance/satisfaction',
        name: 'company-iso-performance-satisfaction',
        component: () =>
          import('@/modules/clienta/pages/performance/satisfaction.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 9 - Évaluation', disabled: true },
            { title: 'Satisfaction client', disabled: true },
          ],
        },
      },
      {
        path: 'performance/evaluations',
        redirect: '/company/performance/evaluations',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/criteria',
        name: 'company-performance-criteria',
        component: () =>
          import('@/modules/clienta/pages/performance/criteria.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Performance', to: '/company/performance' },
            { title: 'Critères d\'évaluation', disabled: true },
          ],
        },
      },
      {
        path: 'performance/evaluation-requests',
        name: 'company-evaluation-requests',
        component: () =>
          import('@/modules/clienta/pages/performance/evaluation-requests.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Performance', to: '/company/performance' },
            { title: 'Demandes d\'évaluation', disabled: true },
          ],
        },
      },
      {
        path: 'performance/satisfaction',
        redirect: '/company/performance/satisfaction',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/dashboard',
        redirect: '/company/performance',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/audits-internes',
        redirect: '/company/performance/audits',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance',
        name: 'company-performance-index',
        component: () => import('@/modules/clienta/pages/performance/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance',
        name: 'company-performance-surveillance',
        component: () => import('@/modules/clienta/pages/performance/surveillance/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/personnel',
        name: 'company-performance-surveillance-personnel',
        component: () => import('@/modules/clienta/pages/performance/surveillance/personnel.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/personnel/:id',
        name: 'company-performance-surveillance-personnel-detail',
        component: () => import('@/modules/clienta/pages/performance/surveillance/personnel-detail.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/client',
        name: 'company-performance-surveillance-client',
        component: () => import('@/modules/clienta/pages/performance/surveillance/client.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/client/:id',
        name: 'company-performance-surveillance-client-detail',
        component: () => import('@/modules/clienta/pages/performance/surveillance/client-detail.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/prestataires',
        name: 'company-performance-surveillance-prestataires',
        component: () => import('@/modules/clienta/pages/performance/surveillance/prestataires.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/prestataires/:id',
        name: 'company-performance-surveillance-prestataires-detail',
        component: () => import('@/modules/clienta/pages/performance/surveillance/prestataires-detail.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/performance-personnel',
        name: 'company-performance-surveillance-performance-personnel',
        component: () => import('@/modules/clienta/pages/performance/evaluations.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/performance-prestataires',
        name: 'company-performance-surveillance-performance-prestataires',
        component: () => import('@/modules/clienta/pages/performance/surveillance/performance-prestataires.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/procedure-evaluation',
        name: 'company-performance-surveillance-procedure-evaluation',
        component: () => import('@/modules/clienta/pages/performance/surveillance/procedure-evaluation.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/rapport',
        name: 'company-performance-surveillance-rapport',
        component: () => import('@/modules/clienta/pages/performance/surveillance/rapport.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/traceability',
        name: 'company-performance-surveillance-traceability',
        component: () => import('@/modules/clienta/pages/performance/surveillance/traceability.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },
      {
        path: 'performance/surveillance/process-review',
        name: 'company-performance-surveillance-process-review',
        component: () => import('@/modules/clienta/pages/performance/surveillance/process-review/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Évaluation des performances', disabled: true },
            { title: 'Revue Processus', disabled: true },
          ],
        },
      },
      {
        path: 'performance/surveillance/process-review/:processId',
        name: 'company-performance-surveillance-process-review-detail',
        component: () => import('@/modules/clienta/pages/performance/surveillance/process-review/[processId].vue'),
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Évaluation des performances', disabled: true },
            { title: 'Revue Processus', to: '/company/performance/surveillance/process-review' },
            { title: 'Détail', disabled: true },
          ],
        },
      },
      {
        path: 'performance/audits',
        name: 'company-performance-audits',
        component: () => import('@/modules/clienta/pages/performance/audits/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Audits internes', disabled: true },
          ],
        },
      },
      {
        path: 'performance/audits/planification',
        name: 'company-performance-audits-planification',
        component: () => import('@/modules/clienta/pages/performance/audits/planification.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Audits internes', to: '/company/performance/audits' },
            { title: 'Planification', disabled: true },
          ],
        },
      },
      {
        path: 'performance/audits/evaluation',
        name: 'company-performance-audits-evaluation',
        component: () => import('@/modules/clienta/pages/performance/audits/evaluation.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Audits internes', to: '/company/performance/audits' },
            { title: 'Evaluation des auditeurs', disabled: true },
          ],
        },
      },
      {
        path: 'performance/audits/programme',
        name: 'company-performance-audits-programme',
        component: () => import('@/modules/clienta/pages/performance/audits/programme.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Audits internes', to: '/company/performance/audits' },
            { title: 'Programme d\'audit', disabled: true },
          ],
        },
      },
      {
        path: 'performance/revue-direction',
        name: 'company-performance-revue-direction',
        component: () => import('@/modules/clienta/pages/performance/revue-direction/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Revue de direction', disabled: true },
          ],
        },
      },
      {
        path: 'performance/revue-processus',
        name: 'company-performance-revue-processus',
        redirect: '/company/performance/surveillance/process-review',
        meta: {
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Revue Processus', disabled: true },
          ],
        },
      },
      {
        path: 'performance/revue-direction/synthese',
        name: 'company-performance-revue-direction-synthese',
        component: () => import('@/modules/clienta/pages/performance/revue-direction/synthese.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Revue de direction', to: '/company/performance/revue-direction' },
            { title: 'Synthèse du SM', disabled: true },
          ],
        },
      },
      {
        path: 'performance/revue-direction/synthesis',
        redirect: '/company/performance/revue-direction/synthese',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'performance/revue-direction/rapports',
        name: 'company-performance-revue-direction-rapports',
        redirect: '/company/management-reviews',
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Revue de direction', to: '/company/performance/revue-direction' },
            { title: 'Rapports', disabled: true },
          ],
        },
      },
      {
        path: 'performance/revue-direction/invitations',
        name: 'company-performance-revue-direction-invitations',
        redirect: '/company/management-reviews',
        meta: {
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Evaluation des performances', disabled: true },
            { title: 'Revue de direction', to: '/company/performance/revue-direction' },
            { title: 'Invitations', disabled: true },
          ],
        },
      },
      {
        path: 'operations/procedures',
        redirect: '/company/processes',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'operations/operational-planning-control',
        name: 'company-iso-operational-planning-control',
        component: () =>
          import('@/modules/clienta/pages/operations/OperationalPlanningControl.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Planification et maîtrise opérationnelle',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/product-service-requirements',
        name: 'company-iso-product-service-requirements',
        component: () =>
          import('@/modules/clienta/pages/planning/ComplianceObligations.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Exigences relatives aux produits et services',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/design-development-products-services',
        name: 'company-iso-design-development-products-services',
        component: () =>
          import('@/modules/clienta/pages/operations/DesignDevelopmentProductsServices.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Conception et développement de produit et service',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/provider-management',
        name: 'company-iso-provider-management',
        component: () =>
          import('@/modules/clienta/pages/operations/ProviderManagement.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Gestion des prestataires', disabled: true },
          ],
        },
      },
      {
        path: 'operations/production-service-provision',
        name: 'company-iso-production-service-provision',
        component: () =>
          import('@/modules/clienta/pages/operations/ProductionServiceProvision.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Production et prestation de service', disabled: true },
          ],
        },
      },
      {
        path: 'operations/release-products-services',
        name: 'company-iso-release-products-services',
        component: () =>
          import('@/modules/clienta/pages/operations/ReleaseProductsServices.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Libération des produits et services', disabled: true },
          ],
        },
      },
      {
        path: 'operations/control-nonconforming-outputs',
        name: 'company-iso-control-nonconforming-outputs',
        component: () =>
          import('@/modules/clienta/pages/operations/ControlNonconformingOutputs.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Maîtrise des éléments de sortie non-conformes',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/emergency-preparedness',
        redirect: '/company/context/management-system',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'operations/energy-design',
        redirect: '/company/context/management-system',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'improvement/corrective-actions',
        redirect: '/company/actions',
        meta: {
          requiresAuth: true,
        },
      },
      {
        path: 'improvement/continuous',
        name: 'company-iso-improvement-continuous',
        component: () =>
          import('@/modules/clienta/pages/improvement/ContinuousImprovement.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 10 - Amélioration', disabled: true },
            { title: 'Amélioration continue', disabled: true },
          ],
        },
      },
      {
        path: 'planning/risks-opportunities',
        name: 'company-iso-risks-opportunities',
        component: () =>
          import('@/modules/clienta/pages/planning/RisksOpportunities.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            { title: 'Risques et opportunités', disabled: true },
          ],
        },
      },
      {
        path: 'planning/duerp',
        name: 'company-iso-duerp',
        component: () =>
          import('@/modules/clienta/pages/planning/RisksOpportunities.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            { title: 'DUERP', disabled: true },
          ],
        },
      },
      {
        path: 'planning/risks-opportunities/:id',
        name: 'company-iso-risk-opportunity-detail',
        component: () =>
          import('@/modules/clienta/pages/planning/[id].vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            {
              title: 'Risques et opportunités',
              to: '/company/planning/risks-opportunities',
            },
            { title: 'Détails', disabled: true },
          ],
        },
      },
      {
        path: 'planning/objectives',
        name: 'company-iso-objectives',
        component: () =>
          import('@/modules/clienta/pages/planning/Objectives.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            { title: 'Tableau de bord système', disabled: true },
          ],
        },
      },
      {
        path: 'planning/objectives/:id',
        name: 'company-iso-objective-detail',
        component: () =>
          import('@/modules/clienta/pages/planning/objectives/[id].vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            {
              title: 'Tableau de bord système',
              to: '/company/planning/objectives',
            },
            { title: 'Détails objectif', disabled: true },
          ],
        },
      },
      {
        path: 'planning/action-plans',
        name: 'company-iso-action-plans',
        component: () =>
          import('@/modules/clienta/pages/planning/ActionPlans.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 6 - Planification', disabled: true },
            { title: 'Plans du SM', disabled: true },
          ],
        },
      },

      {
        path: 'support/equipment',
        name: 'company-iso-equipment',
        component: () => import('@/pages/iso/support/Equipment.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 7 - Support', disabled: true },
            { title: 'Ressources', disabled: true },
          ],
        },
      },
      {
        path: 'competences',
        name: 'company-competences',
        component: () =>
          import('@/modules/clienta/pages/competences/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.competences.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Compétences & Formations', disabled: true },
          ],
        },
      },
      {
        path: 'support/training',
        name: 'company-iso-training',
        component: () =>
          import('@/modules/clienta/pages/competences/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.competences.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 7 - Support', disabled: true },
            { title: 'Compétences', disabled: true },
          ],
        },
      },
      {
        path: 'support/communication',
        name: 'company-iso-communication',
        component: () =>
          import('@/modules/clienta/pages/communications/index.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 7 - Support', disabled: true },
            { title: 'Communication', disabled: true },
          ],
        },
      },
      {
        path: 'support/awareness',
        name: 'company-iso-awareness',
        component: () =>
          import('@/modules/clienta/pages/communications/index.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 7 - Support', disabled: true },
            { title: 'Sensibilisation', disabled: true },
          ],
        },
      },
      {
        path: 'support/document-inventory',
        name: 'company-iso-document-inventory',
        component: () => import('@/modules/clienta/pages/documents/index.vue'),
        meta: {
          requiresAuth: true,
          permissions: ['support.documents.read'],
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Point 7 - Support', disabled: true },
            { title: 'Information documentée', disabled: true },
          ],
        },
      },
      {
        path: 'operations/operational-planning-control',
        name: 'company-iso-operational-planning-control',
        component: () =>
          import('@/modules/clienta/pages/operations/OperationalPlanningControl.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Planification et maîtrise opérationnelle',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/product-service-requirements',
        name: 'company-iso-product-service-requirements',
        component: () =>
          import('@/modules/clienta/pages/planning/ComplianceObligations.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Exigences relatives aux produits et services',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/compliance-obligations',
        name: 'company-iso-compliance-obligations',
        component: () =>
          import('@/modules/clienta/pages/planning/ComplianceObligations.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Exigences relatives aux produits et services',
              disabled: true,
            },
            { title: 'Obligations de conformité', disabled: true },
          ],
        },
      },
      {
        path: 'operations/design-development-products-services',
        name: 'company-iso-design-development-products-services',
        component: () =>
          import('@/modules/clienta/pages/operations/DesignDevelopmentProductsServices.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Conception et développement de produit et service',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'operations/provider-management',
        name: 'company-iso-provider-management',
        component: () =>
          import('@/modules/clienta/pages/operations/ProviderManagement.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Gestion des prestataires', disabled: true },
          ],
        },
      },
      {
        path: 'operations/production-service-provision',
        name: 'company-iso-production-service-provision',
        component: () =>
          import('@/modules/clienta/pages/operations/ProductionServiceProvision.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Production et prestation de service', disabled: true },
          ],
        },
      },
      {
        path: 'operations/release-products-services',
        name: 'company-iso-release-products-services',
        component: () =>
          import('@/modules/clienta/pages/operations/ReleaseProductsServices.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            { title: 'Libération des produits et services', disabled: true },
          ],
        },
      },
      {
        path: 'operations/control-nonconforming-outputs',
        name: 'company-iso-control-nonconforming-outputs',
        component: () =>
          import('@/modules/clienta/pages/operations/ControlNonconformingOutputs.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            {
              title: 'Point 8 - Réalisation des activités opérationnelles',
              disabled: true,
            },
            {
              title: 'Maîtrise des éléments de sortie non-conformes',
              disabled: true,
            },
          ],
        },
      },
      {
        path: 'my-tasks',
        name: 'company-my-tasks',
        component: () =>
          import('@/modules/clienta/pages/my-tasks/index.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Mes Tâches', disabled: true },
          ],
        },
      },
      {
        path: 'my-actions',
        name: 'company-my-actions',
        component: () =>
          import('@/modules/clienta/pages/actions/MyActions.vue'),
        meta: {
          requiresAuth: true,
          breadcrumbs: [
            { title: 'Accueil', to: '/company/dashboard' },
            { title: 'Mes Actions', disabled: true },
          ],
        },
      },
    ],
  },
]
