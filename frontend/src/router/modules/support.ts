import type { RouteRecordRaw } from 'vue-router'

export const supportRoutes: RouteRecordRaw[] = [
  {
    path: '/support',
    name: 'support',
    component: () => import('@/pages/support/index.vue'),
    meta: {
      requiresAuth: true,
      title: 'Support',
    },
  },
  {
    path: '/support/ressources',
    name: 'support-ressources',
    component: () => import('@/pages/support/ressources.vue'),
    meta: {
      requiresAuth: true,
      title: 'Ressources',
    },
  },
  {
    path: '/support/competences',
    name: 'support-competences',
    redirect: '/company/support/training',
    meta: {
      requiresAuth: true,
      title: 'Compétences',
    },
  },
  {
    path: '/support/habilitations',
    name: 'support-habilitations',
    component: () => import('@/views/competences/HabilitationsView.vue'),
    meta: {
      requiresAuth: true,
      title: 'Habilitations',
      permissions: ['sst.habilitations.read'],
    },
  },
  {
    path: '/support/competence-matrix',
    name: 'support-competence-matrix',
    component: () => import('@/views/competences/CompetenceMatrixView.vue'),
    meta: {
      requiresAuth: true,
      title: 'Matrice de Compétences',
      permissions: ['support.competences.read'],
    },
  },
  {
    path: '/support/communication',
    name: 'support-communication',
    component: () => import('@/pages/support/communication.vue'),
    meta: {
      requiresAuth: true,
      title: 'Communication et sensibilisation',
    },
  },
  {
    path: '/support/awareness',
    name: 'support-awareness',
    component: () => import('@/pages/support/communication.vue'),
    meta: {
      requiresAuth: true,
      title: 'Sensibilisation',
    },
  },
  {
    path: '/support/information-documentee',
    name: 'support-information-documentee',
    component: () => import('@/pages/support/information-documentee.vue'),
    meta: {
      requiresAuth: true,
      title: 'Information documentée',
    },
  },
]
