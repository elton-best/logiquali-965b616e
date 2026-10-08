/**
 * Management System Routes
 * Stakeholders, Contexts (SWOT/PESTEL), Management Reviews
 */

import type { RouteRecordRaw } from 'vue-router'

export const managementRoutes: RouteRecordRaw[] = [
  // Stakeholders (Parties Intéressées)
  {
    path: '/stakeholders',
    name: 'stakeholders',
    component: () => import('@/pages/stakeholders/List.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.parties_interessees.read'],
      breadcrumb: 'Parties Intéressées',
    },
  },
  {
    path: '/stakeholders/create',
    name: 'contexte.parties_interessees.create',
    component: () => import('@/pages/stakeholders/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.parties_interessees.create'],
      breadcrumb: 'Nouvelle Partie',
    },
  },
  {
    path: '/stakeholders/:id',
    name: 'stakeholders-view',
    component: () => import('@/pages/stakeholders/View.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.parties_interessees.read'],
      breadcrumb: 'Détails',
    },
  },
  {
    path: '/stakeholders/:id/edit',
    name: 'stakeholders-edit',
    component: () => import('@/pages/stakeholders/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.parties_interessees.update'],
      breadcrumb: 'Édition',
    },
  },

  // Contexts (SWOT/PESTEL)
  {
    path: '/contexts',
    name: 'contexts',
    component: () => import('@/pages/contexts/List.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.comprehension_organisme.read'],
      breadcrumb: 'Contexte Organisationnel',
    },
  },
  {
    path: '/contexts/create',
    name: 'contexte.comprehension_organisme.update',
    component: () => import('@/pages/contexts/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.comprehension_organisme.update'],
      breadcrumb: 'Nouvelle Analyse',
    },
  },
  {
    path: '/contexts/:id',
    name: 'contexts-view',
    component: () => import('@/pages/contexts/View.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.comprehension_organisme.read'],
      breadcrumb: 'Détails',
    },
  },
  {
    path: '/contexts/:id/edit',
    name: 'contexts-edit',
    component: () => import('@/pages/contexts/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['contexte.comprehension_organisme.update'],
      breadcrumb: 'Édition',
    },
  },

  // Management Reviews (Revues Direction)
  {
    path: '/management-reviews',
    name: 'management-reviews',
    component: () => import('@/pages/management-reviews/List.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['evaluation.revue_direction.read'],
      breadcrumb: 'Revues de Direction',
    },
  },
  {
    path: '/management-reviews/create',
    name: 'management-reviews-create',
    component: () => import('@/pages/management-reviews/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['evaluation.revue_direction.create'],
      breadcrumb: 'Nouvelle Revue',
    },
  },
  {
    path: '/management-reviews/:id',
    name: 'management-reviews-view',
    component: () => import('@/pages/management-reviews/View.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['evaluation.revue_direction.read'],
      breadcrumb: 'Détails',
    },
  },
  {
    path: '/management-reviews/:id/edit',
    name: 'management-reviews-edit',
    component: () => import('@/pages/management-reviews/Form.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['evaluation.revue_direction.update'],
      breadcrumb: 'Édition',
    },
  },
]
