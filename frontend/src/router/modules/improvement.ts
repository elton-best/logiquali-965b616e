/**
 * Improvement Routes
 * Actions, Non-conformities, Audits, KPI, Objectives, Risks, Reclamations, Performance
 */

import type { RouteRecordRaw } from 'vue-router'

export const improvementRoutes: RouteRecordRaw[] = [
  {
    path: '/improvement',
    name: 'improvement',
    redirect: '/improvement/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'improvement-dashboard',
        component: () => import('@/pages/improvement/dashboard.vue'),
        meta: {
          title: 'Tableau de Bord Amélioration Continue',
          requiresAuth: true,
          permissions: ['dashboard.read'],
        },
      },

      // ========== ACTIONS ==========
      {
        path: 'actions',
        name: 'actions-list',
        component: () => import('@/pages/improvement/actions/index.vue'),
        meta: {
          title: 'Actions',
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
          breadcrumb: 'Actions',
        },
      },
      {
        path: 'actions/:id',
        name: 'actions-detail',
        component: () => import('@/pages/improvement/actions/[id].vue'),
        meta: {
          title: 'Détails Action',
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
          breadcrumb: 'Détails',
        },
      },

      // ========== NON-CONFORMITÉS ==========
      {
        path: 'non-conformities',
        name: 'non-conformities',
        component: () => import('@/pages/improvement/non-conformities/index.vue'),
        meta: {
          title: 'Non-Conformités',
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
          breadcrumb: 'Non-Conformités',
        },
      },
      {
        path: 'non-conformities/:id',
        name: 'non-conformities-detail',
        component: () => import('@/pages/improvement/non-conformities/[id].vue'),
        meta: {
          title: 'Détails NC',
          requiresAuth: true,
          permissions: ['amelioration.non_conformites.read'],
          breadcrumb: 'Détails',
        },
      },

      // ========== AUDITS ==========
      {
        path: 'audits',
        name: 'audits',
        component: () => import('@/pages/improvement/audits/index.vue'),
        meta: {
          title: 'Audits',
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumb: 'Audits',
        },
      },
      {
        path: 'audits/:id',
        name: 'audits-detail',
        component: () => import('@/pages/improvement/audits/[id].vue'),
        meta: {
          title: 'Détails Audit',
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumb: 'Détails',
        },
      },

      // ========== INDICATEURS (KPI) ==========
      {
        path: 'kpi',
        name: 'kpi',
        component: () => import('@/pages/improvement/kpi/index.vue'),
        meta: {
          title: 'Indicateurs',
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
          breadcrumb: 'Indicateurs',
        },
      },
      {
        path: 'kpi/dashboard',
        name: 'kpi-dashboard',
        component: () => import('@/pages/improvement/kpi/dashboard.vue'),
        meta: {
          title: 'Dashboard Indicateurs',
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
          breadcrumb: 'Dashboard',
        },
      },

      // ========== OBJECTIFS ==========
      {
        path: 'objectives',
        name: 'objectives',
        component: () => import('@/pages/improvement/objectives/index.vue'),
        meta: {
          title: 'Objectifs',
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
          breadcrumb: 'Objectifs',
        },
      },

      // ========== RISQUES ==========
      {
        path: 'risks',
        name: 'risks',
        component: () => import('@/pages/improvement/risks/index.vue'),
        meta: {
          title: 'Matrice des Risques',
          requiresAuth: true,
          permissions: ['planification.risques_opportunites.read'],
          breadcrumb: 'Risques',
        },
      },

      // ========== RÉCLAMATIONS ==========
      {
        path: 'reclamations',
        name: 'reclamations',
        component: () => import('@/pages/improvement/reclamations/index.vue'),
        meta: {
          title: 'Réclamations',
          requiresAuth: true,
          permissions: ['realisation.sorties_non_conformes.manage_complaints'],
          breadcrumb: 'Réclamations',
        },
      },

      // ========== ÉVALUATION DES PERFORMANCES ==========
      {
        path: 'performance/dashboard',
        name: 'performance-dashboard',
        redirect: '/company/performance',
        meta: {
          title: 'Évaluation des Performances',
          requiresAuth: true,
          permissions: ['dashboard.read'],
          breadcrumb: 'Performance',
        },
      },
      {
        path: 'performance/surveillance',
        name: 'performance-surveillance',
        redirect: '/company/performance/surveillance',
        meta: {
          title: 'Surveillance et Mesure',
          requiresAuth: true,
          permissions: ['planification.objectifs.read'],
          breadcrumb: 'Surveillance',
        },
      },
      {
        path: 'performance/audits-internes',
        name: 'performance-audits-internes',
        redirect: '/company/performance/audits',
        meta: {
          title: 'Audits Internes',
          requiresAuth: true,
          permissions: ['evaluation.audits.read'],
          breadcrumb: 'Audits Internes',
        },
      },
      {
        path: 'performance/revue-direction',
        name: 'performance-revue-direction',
        redirect: '/company/performance/revue-direction',
        meta: {
          title: 'Revue de Direction',
          requiresAuth: true,
          permissions: ['evaluation.revue_direction.read'],
          breadcrumb: 'Revue de Direction',
        },
      },
    ],
  },
]

export default improvementRoutes
