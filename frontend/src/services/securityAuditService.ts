import api from '@/api/client'

export interface SecurityAuditLogEntry {
  id: number
  user_id: number | null
  enterprise_id: number | null
  site_id: number | null
  event_type: string
  resource_type: string | null
  resource_id: number | null
  action: string
  ip_address: string
  user_agent: string
  metadata: Record<string, any> | null
  risk_level: 'low' | 'medium' | 'high' | 'critical'
  status: string
  created_at: string
  updated_at: string
  user?: { id: number, name?: string, email?: string } | null
  site?: { id: number, name?: string } | null
}

export interface SecurityAuditLogsResponse {
  data: SecurityAuditLogEntry[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface SecurityAuditStats {
  total_events: number
  high_risk_events: number
  critical_events: number
  events_today: number
  failed_logins_today: number
}

export interface ShadowRbacSummary {
  window_days: number
  total_divergences: number
  by_reason: Record<string, number>
  top_permissions: Array<{ permission: string, count: number }>
  timeline: Array<{ day: string, count: number }>
}

export const securityAuditService = {
  async getLogs (params: Record<string, any>): Promise<SecurityAuditLogsResponse> {
    const response = await api.get('/security-audit-logs', { params })
    return response.data as SecurityAuditLogsResponse
  },

  async getStats (params: Record<string, any>): Promise<SecurityAuditStats> {
    const response = await api.get('/security-audit-logs/stats', { params })
    return response.data as SecurityAuditStats
  },

  async getShadowRbacSummary (params: Record<string, any>): Promise<ShadowRbacSummary> {
    const response = await api.get('/security-audit-logs/shadow-rbac-summary', { params })
    return response.data as ShadowRbacSummary
  },
}
