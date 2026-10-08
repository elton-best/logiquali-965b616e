/**
 * Module Routes Index
 * Aggregates all module route configurations
 */

import { improvementRoutes } from './improvement'
import { isoModuleRoutes } from './iso'
import { managementRoutes } from './management'
import { processRoutes } from './processes'
import { supportRoutes } from './support'

export const moduleRoutes = [
  ...managementRoutes,
  ...improvementRoutes,
  ...processRoutes,
  ...supportRoutes,
  ...isoModuleRoutes,
]
