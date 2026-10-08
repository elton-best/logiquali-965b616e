/**
 * Process Management Routes
 * Process mapping, sequences, resources, objectives, KPIs
 */

import type { RouteRecordRaw } from 'vue-router'

function getRouteParamId (to: { params: Record<string, unknown> }): string {
  const rawId = to.params.id
  if (Array.isArray(rawId)) {
    return String(rawId[0] || '')
  }
  return String(rawId || '')
}

export const processRoutes: RouteRecordRaw[] = [
  // Process List
  {
    path: '/company/processes',
    name: 'processes',
    redirect: '/company/context/management-system',
    meta: {
      requiresAuth: true,
      breadcrumb: 'Processus',
    },
  },

  // Create Process
  {
    path: '/company/processes/create',
    name: 'processes-create',
    redirect: '/company/context/management-system?openCreate=1',
    meta: {
      requiresAuth: true,
      breadcrumb: 'Créer un processus',
    },
  },

  // Process Cartography
  {
    path: '/company/processes/cartography',
    name: 'processes-cartography',
    component: () => import('@/modules/clienta/pages/processes/cartography.vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'Cartographie des processus',
    },
  },

  // Process Detail
  {
    path: '/company/processes/:id',
    name: 'processes-view',
    component: () => import('@/modules/clienta/pages/processes/[id].vue'),
    meta: {
      requiresAuth: true,
      breadcrumb: 'Détails du processus',
    },
  },

  // Edit Process (stepper editor with id param)
  {
    path: '/company/processes/:id/edit',
    name: 'processes-edit',
    redirect: to =>
      `/company/context/management-system/${getRouteParamId(to as { params: Record<string, unknown> })}?mode=edit`,
    meta: {
      requiresAuth: true,
      breadcrumb: 'Modifier le processus',
    },
  },
]
