/**
 * ISO Modules Dynamic Routes
 * Route générique pour tous les sous-modules ISO (incluant Leadership)
 */

import type { RouteRecordRaw } from 'vue-router'

export const isoModuleRoutes: RouteRecordRaw[] = [
  // ✅ Routes spécifiques à 3 niveaux AVANT la route générique
  // Leadership > Rôles et Responsabilités > Sections
  {
    path: '/company/leadership/organization-chart',
    name: 'leadership-roles-organigramme',
    component: () => import('@/modules/clienta/pages/leadership/OrgChart.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['leadership.roles_responsabilites.organigramme.read'],
      breadcrumb: 'Organigramme',
    },
  },
  {
    path: '/company/leadership/personnel',
    name: 'leadership-roles-personnel',
    component: () => import('@/modules/clienta/pages/leadership/PersonnelList.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['leadership.roles_responsabilites.personnel.read'],
      breadcrumb: 'Liste du personnel',
    },
  },
  {
    path: '/company/leadership/fiche_poste',
    name: 'leadership-roles-fiche-poste',
    component: () => import('@/modules/clienta/pages/leadership/JobDescription.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['leadership.roles_responsabilites.fiche_poste.read'],
      breadcrumb: 'Fiche de poste',
    },
  },
  {
    path: '/company/leadership/fiche_responsabilite',
    name: 'leadership-roles-fiches',
    component: () => import('@/modules/clienta/pages/leadership/ResponsibilitySheet.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['leadership.roles_responsabilites.fiche_responsabilite.read'],
      breadcrumb: 'Fiche de responsabilité',
    },
  },

  // Route générique dynamique pour TOUS les sous-modules ISO (incluant Leadership)
  // ⚠️ DOIT être EN DERNIER pour ne pas intercepter les routes spécifiques ci-dessus
  {
    path: '/company/:module/:submodule',
    name: 'dynamic-submodule',
    component: () => import('@/modules/clienta/pages/iso/DynamicSubModule.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'Module ISO',
    },
  },
]
