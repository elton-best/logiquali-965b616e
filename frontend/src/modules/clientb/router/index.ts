import type { RouteRecordRaw } from 'vue-router'

export const clientbRoutes: RouteRecordRaw[] = [
  {
    path: '/clientb',
    redirect: '/clientb/dashboard',
    meta: {
      requiresAuth: true,
      requiredRole: 'clientb',
    },
    children: [
      {
        path: 'dashboard',
        name: 'clientb-dashboard',
        component: () => import('@/modules/clientb/pages/dashboard.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'complaints',
        name: 'clientb-complaints',
        component: () => import('@/modules/clientb/pages/ComplaintManagement.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'complaints/create',
        name: 'clientb-complaints-create',
        component: () => import('@/modules/clientb/pages/ComplaintCreate.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'complaints/:id',
        name: 'clientb-complaints-detail',
        component: () => import('@/modules/clientb/pages/ComplaintDetails.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'notifications',
        name: 'clientb-notifications',
        component: () => import('@/modules/clientb/pages/notifications/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'satisfaction',
        name: 'clientb-satisfaction',
        component: () => import('@/modules/clientb/pages/SatisfactionSurveys.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'satisfaction-forms',
        name: 'clientb-satisfaction-forms',
        component: () => import('@/modules/clientb/pages/SatisfactionForms.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'satisfaction-forms/create',
        name: 'clientb-satisfaction-forms-create',
        component: () => import('@/modules/clientb/pages/SatisfactionFormCreate.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'satisfaction-forms/:id',
        name: 'clientb-satisfaction-forms-detail',
        component: () => import('@/modules/clientb/pages/SatisfactionFormDetails.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'satisfaction-forms/:id/edit',
        name: 'clientb-satisfaction-forms-edit',
        component: () => import('@/modules/clientb/pages/SatisfactionFormCreate.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'profile',
        name: 'clientb-profile',
        component: () => import('@/modules/clientb/pages/Profile.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'support',
        name: 'clientb-support',
        component: () => import('@/modules/clientb/pages/Support.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
      {
        path: 'surveys',
        name: 'clientb-surveys',
        component: () => import('@/modules/clientb/pages/SatisfactionSurveys.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'clientb',
        },
      },
    ],
  },
]
