/**
 * Centralized module routes
 * Import all module routes here
 */

import type { RouteRecordRaw } from 'vue-router'
import { clientaRoutes } from '@/modules/clienta/router'
import { clientbRoutes } from '@/modules/clientb/router'
import { superadminRoutes } from '@/modules/superadmin/router'
import { documentsRoutes } from './modules/documents'
import { improvementRoutes } from './modules/improvement'
import { isoModuleRoutes } from './modules/iso'
import { managementRoutes } from './modules/management'
import { processRoutes } from './modules/processes'

export const moduleRoutes: RouteRecordRaw[] = [
  ...superadminRoutes,
  ...clientaRoutes,
  ...clientbRoutes,
  ...improvementRoutes,
  ...managementRoutes,
  ...processRoutes,
  ...documentsRoutes,
  ...isoModuleRoutes,
]
