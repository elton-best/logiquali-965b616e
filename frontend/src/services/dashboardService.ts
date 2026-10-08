import api from '@/api/client'

export interface DashboardStats {
  totalSites: number
  totalUsers: number
  totalProcesses: number
  totalRisks: number
  totalActions: number
  totalAudits: number
  totalNonConformities: number
  activeNonConformities: number
  overdueActions: number
  upcomingAudits: number
  leadership: {
    policy_status: string
    policy_last_update: string | null
    total_employees: number
    employees_with_job_description: number
    organization_chart_updated: boolean
  }
  charts: {
    activity: Array<{ label: string, documents: number, actions: number }>
    distribution: { documents: number, nc: number, audits: number, actions: number }
    performance: Array<{ label: string, objectives: number, actions: number }>
  }
  recentActivities: Array<{
    id: number
    type: string
    name?: string
    description?: string
    user?: string
    createdAt: string
    created_at?: string
  }>
}

export interface DashboardKPI {
  label: string
  value: number
  change: number
  trend: 'up' | 'down' | 'neutral'
  icon: string
  color: string
}

export interface CollaboratorActionSummary {
  stats: {
    total_assigned: number
    open_count: number
    in_progress_count: number
    overdue_count: number
    due_soon_count: number
    completed_count: number
  }
  actions: Array<{
    id: number
    title: string
    status: string
    progress: number
    deadline?: string | null
    created_at?: string | null
    updated_at?: string | null
    progress_notes?: Array<{
      date: string
      note: string
      user_id?: number
      user_name?: string
    }>
    process?: { id: number, title?: string, code?: string } | null
    site?: { id: number, name?: string } | null
  }>
  by_process: Array<{
    process_id: number | null
    process_label: string
    process_code?: string | null
    total_count: number
    open_count: number
    overdue_count: number
  }>
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

interface BackendDashboardResponse {
  success: boolean
  data: {
    stats: {
      total_sites: number
      total_users: number
      total_processes: number
      total_risks: number
      total_actions: number
      total_audits: number
      total_non_conformities: number
      active_non_conformities: number
      overdue_actions: number
      upcoming_audits: number
    }
    leadership: {
      policy_status: string
      policy_last_update: string | null
      total_employees: number
      employees_with_job_description: number
      organization_chart_updated: boolean
    }
    charts: {
      activity: Array<{ label: string, documents: number, actions: number }>
      distribution: { documents: number, nc: number, audits: number, actions: number }
      performance: Array<{ label: string, objectives: number, actions: number }>
    }
    recent_activities: Array<{
      id: number
      type: string
      name: string
      created_at: string
    }>
    scope?: {
      applied_scope: 'enterprise' | 'site'
      applied_site_id: number | null
    }
  }
}

export const dashboardService = {
  /**
   * Get dashboard statistics for current enterprise
   */
  async getStats (params?: { site_id?: string | number, scope?: 'enterprise' | 'site' }): Promise<DashboardStats> {
    const { data } = await api.get<BackendDashboardResponse>('/dashboard/stats', { params })

    // Transform backend response to frontend format
    const backendStats = data.data.stats
    const backendActivities = data.data.recent_activities
    const backendLeadership = data.data.leadership
    const backendCharts = data.data.charts

    return {
      totalSites: backendStats.total_sites,
      totalUsers: backendStats.total_users,
      totalProcesses: backendStats.total_processes,
      totalRisks: backendStats.total_risks,
      totalActions: backendStats.total_actions,
      totalAudits: backendStats.total_audits,
      totalNonConformities: backendStats.total_non_conformities,
      activeNonConformities: backendStats.active_non_conformities,
      overdueActions: backendStats.overdue_actions,
      upcomingAudits: backendStats.upcoming_audits,
      leadership: backendLeadership,
      charts: backendCharts,
      recentActivities: backendActivities.map(activity => ({
        id: activity.id,
        type: activity.type,
        description: activity.name,
        name: activity.name,
        createdAt: activity.created_at,
        created_at: activity.created_at,
        user: 'Système',
      })),
    }
  },

  /**
   * Get KPIs for dashboard cards
   */
  async getKPIs (): Promise<DashboardKPI[]> {
    const { data } = await api.get<{ success: boolean, data: DashboardKPI[] | { kpis: DashboardKPI[] } }>('/dashboard/kpis')
    if (Array.isArray(data.data)) {
      return data.data
    }

    return data.data?.kpis || []
  },

  /**
   * Dashboard collaborateur global (actions dont l'utilisateur est responsable)
   */
  async getCollaboratorActions (params?: {
    site_id?: string | number
    scope?: 'enterprise' | 'site'
    include_closed?: boolean
    per_page?: number
  }): Promise<CollaboratorActionSummary> {
    const { data } = await api.get<{
      success: boolean
      data: CollaboratorActionSummary
    }>('/dashboard/collaborator-actions', { params })

    return data.data
  },

  /**
   * Get chart data for processes
   */
  async getProcessesChart (): Promise<any> {
    const { data } = await api.get<{ success: boolean, data: any }>('/dashboard/charts/processes')
    return data?.data?.chart ?? data.data
  },

  /**
   * Get chart data for risks
   */
  async getRisksChart (): Promise<any> {
    const { data } = await api.get<{ success: boolean, data: any }>('/dashboard/charts/risks')
    return data?.data?.chart ?? data.data
  },

  /**
   * Get chart data for non-conformities
   */
  async getNonConformitiesChart (): Promise<any> {
    const { data } = await api.get<{ success: boolean, data: any }>('/dashboard/charts/non-conformities')
    return data?.data?.chart ?? data.data
  },
}

export default dashboardService
