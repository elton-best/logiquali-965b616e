import { canonicalizeCompanyRoute } from './routeCanonicalizer'

const DIRECT_ROUTE_MAP: Record<string, string> = {
  '/company/collaborators': '/company/leadership/personnel',
  '/company/risks': '/company/planning/risks-opportunities',
  '/company/objectives': '/company/planning/objectives',
  '/company/audits': '/company/performance/audits',
  '/subscriptions/dashboard': '/company/subscription',
  '/documents': '/company/documents',
}

function normalizeRawPath (rawUrl: string): string {
  const value = rawUrl.trim()
  if (!value) {
    return ''
  }

  if (value.startsWith('/')) {
    return value
  }

  try {
    const parsed = new URL(value, window.location.origin)
    return `${parsed.pathname}${parsed.search}${parsed.hash}`
  } catch {
    return value.startsWith('company/') ? `/${value}` : `/company/${value}`
  }
}

function remapNotificationPath (path: string): string {
  if (!path) {
    return path
  }

  if (DIRECT_ROUTE_MAP[path]) {
    return DIRECT_ROUTE_MAP[path]
  }

  if (/^\/documents\/\d+$/i.test(path)) {
    return `/company${path}`
  }

  const opportunityMatch = path.match(/^\/improvement\/opportunities\/(\d+)$/i)
  if (opportunityMatch) {
    return `/company/planning/risks-opportunities/${opportunityMatch[1]}`
  }

  const strategicAxisMatch = path.match(/^\/company\/strategic-axes\/(\d+)$/i)
  if (strategicAxisMatch) {
    return `/company/planning/objectives/${strategicAxisMatch[1]}`
  }

  return path
}

export function resolveNotificationActionUrl (notification: any): string | undefined {
  const direct
    = (typeof notification?.data?.action_url === 'string' && notification.data.action_url.trim())
      || (typeof notification?.data?.url === 'string' && notification.data.url.trim())
      || ''

  if (direct) {
    const path = normalizeRawPath(direct)
    return canonicalizeCompanyRoute(remapNotificationPath(path), '/company/notifications')
  }

  const resourceType = notification?.data?.resource_type
  const resourceId = notification?.data?.resource_id
  if (!resourceType || !resourceId) {
    return undefined
  }

  const routes: Record<string, string> = {
    non_conformity: '/company/nonconformities',
    audit: '/company/performance/audits',
    document: '/company/documents',
    user: '/company/leadership/personnel',
    risk: '/company/planning/risks-opportunities',
    opportunity: '/company/planning/risks-opportunities',
    action: '/company/actions',
    objective: '/company/planning/objectives',
    stakeholder: '/company/context/stakeholders',
  }

  const basePath = routes[String(resourceType)] || ''
  if (!basePath) {
    return undefined
  }

  const withId = `${basePath}/${resourceId}`
  return canonicalizeCompanyRoute(remapNotificationPath(withId), '/company/notifications')
}

export function normalizeNotificationNavigationTarget (rawUrl: string): string {
  const path = normalizeRawPath(rawUrl)
  return canonicalizeCompanyRoute(remapNotificationPath(path), '/company/notifications')
}
