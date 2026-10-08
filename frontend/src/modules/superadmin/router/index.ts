import type { RouteRecordRaw } from 'vue-router'

export const superadminRoutes: RouteRecordRaw[] = [
  {
    path: '/superadmin',
    redirect: '/superadmin/dashboard',
    meta: {
      requiresAuth: true,
      requiredRole: 'super_admin',
    },
    children: [
      {
        path: 'dashboard',
        name: 'superadmin-dashboard',
        component: () => import('@/modules/superadmin/pages/dashboard.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'enterprises',
        name: 'superadmin-enterprises',
        component: () => import('@/modules/superadmin/pages/enterprises/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'kyc',
        name: 'superadmin-kyc',
        component: () => import('@/modules/superadmin/pages/kyc/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'kyc/:id',
        name: 'superadmin-kyc-detail',
        component: () => import('@/modules/superadmin/pages/kyc/[id].vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'companies',
        name: 'superadmin-companies',
        component: () => import('@/modules/superadmin/pages/companies/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'companies/:id',
        name: 'superadmin-company-detail',
        component: () => import('@/modules/superadmin/pages/companies/[id].vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'subscriptions/:id',
        name: 'superadmin-subscription-detail',
        component: () => import('@/modules/superadmin/pages/subscriptions/[id].vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'subscriptions',
        name: 'superadmin-subscriptions',
        component: () => import('@/modules/superadmin/pages/subscriptions/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'offers',
        name: 'superadmin-offers',
        component: () => import('@/modules/superadmin/pages/offers/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'offers/:id',
        name: 'superadmin-offer-detail',
        component: () => import('@/modules/superadmin/pages/offers/[id].vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'norms',
        name: 'superadmin-norms',
        component: () => import('@/modules/superadmin/pages/norms/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'norms/:id',
        name: 'superadmin-norm-detail',
        component: () => import('@/modules/superadmin/pages/norms/[id].vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'profile',
        name: 'superadmin-profile',
        component: () => import('@/modules/superadmin/pages/profile/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'settings',
        name: 'superadmin-settings',
        component: () => import('@/modules/superadmin/pages/settings/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
      {
        path: 'users',
        name: 'superadmin-users',
        component: () => import('@/modules/superadmin/pages/users/index.vue'),
        meta: {
          requiresAuth: true,
          requiredRole: 'super_admin',
        },
      },
    ],
  },
]
