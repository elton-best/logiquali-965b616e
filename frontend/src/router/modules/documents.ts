/**
 * Documents Module Routes
 * GED (Gestion Électronique des Documents)
 */

import type { RouteRecordRaw } from 'vue-router'

export const documentsRoutes: RouteRecordRaw[] = [
  {
    path: '/documents',
    name: 'documents',
    component: () => import('@/pages/documents/index.vue'),
    meta: {
      title: 'Documents',
      requiresAuth: true,
      permissions: ['support.documents.read'],
      breadcrumb: 'Documents',
    },
  },
  {
    path: '/documents/:id',
    name: 'documents-detail',
    component: () => import('@/pages/documents/[id].vue'),
    meta: {
      title: 'Détails Document',
      requiresAuth: true,
      permissions: ['support.documents.read'],
      breadcrumb: 'Détails',
    },
  },
  {
    path: '/documents/nomenclature/configurations',
    name: 'nomenclature-configurations',
    component: () => import('@/pages/nomenclature/ConfigurationList.vue'),
    meta: {
      title: 'Configuration des nomenclatures',
      requiresAuth: true,
      permissions: ['support.documents.configure_nomenclature'],
      breadcrumb: 'Nomenclatures',
    },
  },
  {
    path: '/documents/workflow/dashboard',
    name: 'documents-workflow-dashboard',
    component: () => import('@/pages/documents/WorkflowDashboard.vue'),
    meta: {
      title: 'Dashboard Workflow',
      requiresAuth: true,
      permissions: ['support.documents.read'],
      breadcrumb: 'Workflow',
    },
  },
  {
    path: '/documents/workflow/verification',
    name: 'documents-code-verification',
    component: () => import('@/pages/documents/CodeVerification.vue'),
    meta: {
      title: 'Vérification des codes',
      requiresAuth: true,
      permissions: ['verify_documents'],
      breadcrumb: 'Vérification',
    },
  },
  {
    path: '/documents/workflow/approval',
    name: 'documents-code-approval',
    component: () => import('@/pages/documents/CodeApproval.vue'),
    meta: {
      title: 'Approbation des codes',
      requiresAuth: true,
      permissions: ['approve_documents'],
      breadcrumb: 'Approbation',
    },
  },
  {
    path: '/documents/import',
    name: 'documents-import',
    component: () => import('@/pages/documents/DocumentImport.vue'),
    meta: {
      title: 'Import de documents',
      requiresAuth: true,
      permissions: ['support.documents.import'],
      breadcrumb: 'Import',
    },
  },
  {
    path: '/documents/code-recycling',
    name: 'documents-code-recycling',
    component: () => import('@/pages/documents/CodeRecycling.vue'),
    meta: {
      title: 'Recyclage des codes',
      requiresAuth: true,
      permissions: ['support.documents.read'],
      breadcrumb: 'Recyclage des codes',
    },
  },
]

export default documentsRoutes
