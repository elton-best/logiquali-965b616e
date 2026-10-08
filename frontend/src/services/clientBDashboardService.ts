import api from '@/api/client'

export interface ClientBDashboardStats {
  total_complaints: number
  pending_complaints: number
  in_progress_complaints: number
  resolved_complaints: number
  closed_complaints: number
  avg_response_time_days: number
  resolution_rate: number
}

export interface RecentActivity {
  id: number
  ref: string
  title: string
  status: string
  site: string | null
  assigned_to: string | null
  created_at: string
  updated_at: string
}

export interface QuickAction {
  id: string
  title: string
  description: string
  icon: string
  color: string
  route: string
}

class ClientBDashboardService {
  /**
   * Get dashboard statistics
   */
  async getStats (): Promise<ClientBDashboardStats> {
    const { data } = await api.get('/clientb/dashboard/stats')
    return data.data
  }

  /**
   * Get recent activity
   */
  async getRecentActivity (limit?: number): Promise<RecentActivity[]> {
    const { data } = await api.get('/clientb/dashboard/recent-activity', {
      params: { limit },
    })
    return data.data
  }

  /**
   * Get quick actions
   */
  async getQuickActions (): Promise<QuickAction[]> {
    const { data } = await api.get('/clientb/dashboard/quick-actions')
    return data.data
  }
}

export const clientBDashboardService = new ClientBDashboardService()
export default clientBDashboardService
