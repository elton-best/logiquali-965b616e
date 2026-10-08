import type {
  Enterprise,
  Norm,
  Offer,
  PaginatedResponse,
  Subscription,
} from '@/types/api'
import api from '@/api/client'

class SuperAdminService {
  /**
   * ENTERPRISES
   */

  // Get all enterprises with pagination
  async getEnterprises (params?: {
    page?: number
    per_page?: number
    status?: 'pending' | 'active' | 'rejected' | 'suspended' | 'all'
    approval_status?: 'pending' | 'approved' | 'rejected' | 'all'
    search?: string
    kyc_priority?: boolean
  }): Promise<PaginatedResponse<Enterprise>> {
    const { data } = await api.get<PaginatedResponse<Enterprise>>('/superadmin/enterprises', { params })
    return data
  }

  // Get single enterprise
  async getEnterprise (id: number): Promise<Enterprise> {
    const { data } = await api.get<{ data: Enterprise }>(`/superadmin/enterprises/${id}`)
    return data.data
  }

  // Approve enterprise
  async approveEnterprise (id: number): Promise<Enterprise> {
    const { data } = await api.post<{ success: boolean, data: Enterprise }>(`/superadmin/enterprises/${id}/approve`)
    return data.data
  }

  // Reject enterprise
  async rejectEnterprise (id: number, reason: string): Promise<Enterprise> {
    const { data } = await api.post<{ success: boolean, data: Enterprise }>(`/superadmin/enterprises/${id}/reject`, { reason })
    return data.data
  }

  // Suspend enterprise
  async suspendEnterprise (id: number, reason: string): Promise<Enterprise> {
    const { data } = await api.post<{ success: boolean, data: Enterprise }>(`/superadmin/enterprises/${id}/suspend`, { reason })
    return data.data
  }

  // Reactivate enterprise
  async reactivateEnterprise (id: number): Promise<Enterprise> {
    const { data } = await api.post<{ success: boolean, data: Enterprise }>(`/superadmin/enterprises/${id}/reactivate`)
    return data.data
  }

  // Delete enterprise
  async deleteEnterprise (id: number): Promise<void> {
    await api.delete(`/superadmin/enterprises/${id}`)
  }

  /**
   * SUBSCRIPTIONS
   */

  // Get all subscriptions
  async getSubscriptions (params?: {
    page?: number
    per_page?: number
    status?: string
    enterprise_id?: number
    search?: string
  }): Promise<{ data: Subscription[], meta: any }> {
    const { data } = await api.get<{ data: Subscription[], meta: any }>('/superadmin/subscriptions', { params })
    return data
  }

  async getSubscription (id: number): Promise<Subscription> {
    const { data } = await api.get<{ data: Subscription }>(`/superadmin/subscriptions/${id}`)
    return data.data
  }

  /**
   * NORMS (ISO, etc.)
   */

  // Get all norms
  async getNorms (): Promise<Norm[]> {
    const { data } = await api.get<{ data: Norm[] }>('/superadmin/norms')
    return data.data
  }

  // Get single norm with full details
  async getNorm (id: number): Promise<Norm> {
    const { data } = await api.get<{ data: Norm }>(`/superadmin/norms/${id}`)
    return data.data
  }

  // Create norm
  async createNorm (payload: {
    code: string
    name: string
    version_code: string
    domain: string
    description?: string
    structure?: any[] // Hierarchical structure (nodes)
  }): Promise<Norm> {
    const { data } = await api.post<{ data: Norm }>('/superadmin/norms', payload)
    return data.data
  }

  // Update norm
  async updateNorm (id: number, payload: {
    code?: string
    name?: string
    version_code?: string
    domain?: string
    description?: string
    structure?: any[] // Hierarchical structure (nodes)
  }): Promise<Norm> {
    const { data } = await api.put<{ data: Norm }>(`/superadmin/norms/${id}`, payload)
    return data.data
  }

  // Delete norm
  async deleteNorm (id: number): Promise<void> {
    await api.delete(`/superadmin/norms/${id}`)
  }

  /**
   * OFFERS
   */

  // Get all offers
  async getOffers (): Promise<Offer[]> {
    const { data } = await api.get<{ data: Offer[] }>('/superadmin/offers')
    return data.data
  }

  // Get single offer
  async getOffer (id: number): Promise<Offer> {
    const { data } = await api.get<{ data: Offer }>(`/superadmin/offers/${id}`)
    return data.data
  }

  // Create offer
  async createOffer (payload: {
    name: string
    description?: string
    price: number
    duration_months: number
    norms: number[]
  }): Promise<Offer> {
    const { data } = await api.post<{ data: Offer }>('/superadmin/offers', payload)
    return data.data
  }

  // Update offer
  async updateOffer (id: number, payload: {
    name: string
    description?: string
    price: number
    duration_months: number
    norms: number[]
  }): Promise<Offer> {
    const { data } = await api.put<{ data: Offer }>(`/superadmin/offers/${id}`, payload)
    return data.data
  }

  // Delete offer
  async deleteOffer (id: number): Promise<void> {
    await api.delete(`/superadmin/offers/${id}`)
  }

  /**
   * DASHBOARD STATS
   */
  async getDashboardStats (): Promise<{
    kyc: {
      pending: number
      approved: number
      rejected: number
      total: number
    }
    companies: {
      total: number
      active: number
      suspended: number
    }
    subscriptions: {
      active: number
      total: number
    }
    revenue: {
      total: number
      series?: number[]
      trend_pct?: number
    }
    weekly: {
      new_registrations: number
      kyc_validations: number
    }
  }> {
    try {
      // Try to get stats from backend with silent error handling
      const { data } = await api.get<{ data: any }>('/superadmin/stats', {
        headers: { 'X-Skip-Error-Toast': 'true' }, // Custom header to skip toast
      })
      return data.data
    } catch {
      // Fallback: calculer les stats à partir des données disponibles
      console.warn('Stats endpoint not available, using fallback')

      try {
        const enterprisesResponse = await this.getEnterprises({ per_page: 1000 })
        const enterprises = enterprisesResponse.data || []

        const subscriptionsResponse = await this.getSubscriptions({ per_page: 1000 })
        const subscriptions = subscriptionsResponse.data || []

        return {
          kyc: {
            pending: enterprises.filter((e: any) => e.status === 'pending').length,
            approved: enterprises.filter((e: any) => e.approval_status === 'approved').length,
            rejected: enterprises.filter((e: any) => e.approval_status === 'rejected').length,
            total: enterprises.length,
          },
          companies: {
            total: enterprises.length,
            active: enterprises.filter((e: any) => e.status === 'active').length,
            suspended: enterprises.filter((e: any) => e.status === 'suspended').length,
          },
          subscriptions: {
            active: subscriptions.filter((s: any) => s.status === 'active').length,
            total: subscriptions.length,
          },
          revenue: {
            total: subscriptions.reduce((sum: number, s: any) => sum + (s.offer?.price || 0), 0),
            series: Array.from({ length: 10 }, () => 0),
            trend_pct: 0,
          },
          weekly: {
            new_registrations: 0,
            kyc_validations: 0,
          },
        }
      } catch {
        // Return empty stats if everything fails
        return {
          kyc: { pending: 0, approved: 0, rejected: 0, total: 0 },
          companies: { total: 0, active: 0, suspended: 0 },
          subscriptions: { active: 0, total: 0 },
          revenue: { total: 0 },
          weekly: { new_registrations: 0, kyc_validations: 0 },
        }
      }
    }
  }

  /**
   * SETTINGS
   */
  async getSettings (): Promise<{
    general: any
    email: any
    security: any
    system: any
    system_info: any
  }> {
    const { data } = await api.get<{ data: any }>('/superadmin/settings')
    return data.data
  }

  async updateSettings (payload: {
    general?: any
    email?: any
    security?: any
    system?: any
  }): Promise<{
    general: any
    email: any
    security: any
    system: any
    system_info: any
  }> {
    const { data } = await api.put<{ data: any }>('/superadmin/settings', payload)
    return data.data
  }

  async testEmailSettings (): Promise<{ message?: string }> {
    const { data } = await api.post<{ message?: string }>('/superadmin/settings/email/test')
    return data
  }

  /**
   * SESSIONS
   */
  async getSessions (): Promise<Array<{
    id: number
    name: string
    created_at: string | null
    last_used_at: string | null
    mfa_verified_at: string | null
    is_current: boolean
  }>> {
    const { data } = await api.get<{ data: any[] }>('/superadmin/sessions')
    return data.data
  }

  async revokeSession (tokenId: number): Promise<void> {
    await api.delete(`/superadmin/sessions/${tokenId}`)
  }

  /**
   * SEARCH
   */
  async search (query: string): Promise<Record<string, Array<{
    type: string
    id: number
    title: string
    subtitle?: string
    meta?: string
    route?: string
  }>>> {
    const { data } = await api.get<{ data: any }>('/superadmin/search', { params: { q: query } })
    return data.data
  }

  // Suspend subscription
  async suspendSubscription (id: number, reason: string): Promise<Subscription> {
    const { data } = await api.post<{ data: Subscription }>(`/superadmin/subscriptions/${id}/suspend`, { reason })
    return data.data
  }

  // Reactivate subscription
  async reactivateSubscription (id: number): Promise<Subscription> {
    const { data } = await api.post<{ data: Subscription }>(`/superadmin/subscriptions/${id}/reactivate`)
    return data.data
  }

  // Delete subscription
  async deleteSubscription (id: number): Promise<void> {
    await api.delete(`/superadmin/subscriptions/${id}`)
  }

  /**
   * USERS MANAGEMENT
   */

  // Get all platform users
  async getAllUsers (params?: {
    page?: number
    per_page?: number
    user_type?: 'super_admin' | 'company' | 'clientb' | 'all'
    is_active?: string
    search?: string
    enterprise_id?: number
  }): Promise<{ data: any[], meta: any }> {
    const { data } = await api.get<{ data: any[], meta: any }>('/superadmin/users', { params })
    return data
  }

  // Get users statistics
  async getUsersStats (): Promise<{
    total: number
    by_type: {
      super_admin: number
      company: number
      clientb: number
    }
    active: number
    inactive: number
    client_b_total: number
    recent: {
      today: number
      this_week: number
      this_month: number
    }
  }> {
    const { data } = await api.get<{ data: any }>('/superadmin/users/stats')
    return data.data
  }

  /**
   * SUPER ADMIN ROLES
   */
  async getSuperAdminRoles (): Promise<Array<{ id: number, name: string, label: string }>> {
    const { data } = await api.get<{ data: Array<{ id: number, name: string, label: string }> }>('/superadmin/roles')
    return data.data
  }

  /**
   * SUPER ADMIN USERS
   */
  async createSuperAdmin (payload: {
    first_name: string
    last_name: string
    name?: string
    username: string
    email: string
    password: string
    password_confirmation: string
    role_name: string
    is_active?: boolean
  }): Promise<any> {
    const { data } = await api.post<{ data: any }>('/superadmin/users', payload)
    return data.data
  }

  async updateSuperAdmin (id: number, payload: {
    first_name?: string
    last_name?: string
    name?: string
    username?: string
    email?: string
    password?: string
    password_confirmation?: string
    role_name?: string
    is_active?: boolean
  }): Promise<any> {
    const { data } = await api.put<{ data: any }>(`/superadmin/users/${id}`, payload)
    return data.data
  }
}

export default new SuperAdminService()
