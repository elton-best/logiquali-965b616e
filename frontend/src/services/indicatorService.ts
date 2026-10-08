/**
 * Indicator Service
 * API client for managing QHSE performance indicators
 */

import type {
  CreateIndicatorPayload,
  CreateObjectivePayload,
  Dashboard,
  DashboardResponse,
  Indicator,
  IndicatorFilters,
  IndicatorsResponse,
  IndicatorStatistics,
  IndicatorValue,
  IndicatorValueFilters,
  IndicatorValuesResponse,
  Objective,
  ObjectiveFilters,
  ObjectivesResponse,
  ObjectiveStatistics,
  PerformanceSnapshot,
  RecordIndicatorValuePayload,
  TrendData,
  UpdateIndicatorPayload,
  UpdateObjectivePayload,
} from '@/types/indicator'
import api from '@/api/client'

// ============================================
// INDICATORS
// ============================================

export const indicatorService = {
  /**
   * Get all indicators with filters
   */
  async getAll (filters?: IndicatorFilters): Promise<IndicatorsResponse> {
    const params = new URLSearchParams()
    if (filters?.type) {
      params.append('type', filters.type)
    }
    if (filters?.category) {
      params.append('category', filters.category)
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.frequency) {
      params.append('frequency', filters.frequency)
    }
    if (filters?.alert_level) {
      params.append('alert_level', filters.alert_level)
    }
    if (filters?.responsible_id) {
      params.append('responsible_id', String(filters.responsible_id))
    }
    if (filters?.processus_id) {
      params.append('processus_id', String(filters.processus_id))
    }
    if (filters?.active_only) {
      params.append('active_only', '1')
    }
    if (filters?.search) {
      params.append('search', filters.search)
    }
    if (filters?.page) {
      params.append('page', String(filters.page))
    }
    if (filters?.per_page) {
      params.append('per_page', String(filters.per_page))
    }

    const response = await api.get<IndicatorsResponse>(`/indicators?${params}`)
    return response.data
  },

  /**
   * Get indicator by ID
   */
  async getById (id: number): Promise<Indicator> {
    const response = await api.get<{ data: Indicator }>(`/indicators/${id}`)
    return response.data.data
  },

  /**
   * Create new indicator
   */
  async create (payload: CreateIndicatorPayload): Promise<Indicator> {
    const response = await api.post<{ data: Indicator }>('/indicators', payload)
    return response.data.data
  },

  /**
   * Update indicator
   */
  async update (id: number, payload: UpdateIndicatorPayload): Promise<Indicator> {
    const response = await api.put<{ data: Indicator }>(`/indicators/${id}`, payload)
    return response.data.data
  },

  /**
   * Delete indicator
   */
  async delete (id: number): Promise<void> {
    await api.delete(`/indicators/${id}`)
  },

  /**
   * Activate indicator
   */
  async activate (id: number): Promise<Indicator> {
    const response = await api.post<{ data: Indicator }>(`/indicators/${id}/activate`)
    return response.data.data
  },

  /**
   * Deactivate indicator
   */
  async deactivate (id: number): Promise<Indicator> {
    const response = await api.post<{ data: Indicator }>(`/indicators/${id}/deactivate`)
    return response.data.data
  },

  /**
   * Archive indicator
   */
  async archive (id: number): Promise<Indicator> {
    const response = await api.post<{ data: Indicator }>(`/indicators/${id}/archive`)
    return response.data.data
  },

  /**
   * Get indicator statistics
   */
  async getStatistics (): Promise<IndicatorStatistics> {
    const response = await api.get<{ data: IndicatorStatistics }>('/indicators/statistics')
    return response.data.data
  },

  /**
   * Export indicators to Excel
   */
  async exportExcel (filters?: IndicatorFilters): Promise<Blob> {
    const params = new URLSearchParams()
    if (filters?.category) {
      params.append('category', filters.category)
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }

    const response = await api.get(`/indicators/export/excel?${params}`, {
      responseType: 'blob',
    })
    return response.data
  },

  // ============================================
  // INDICATOR VALUES
  // ============================================

  /**
   * Get indicator values (historical data)
   */
  async getValues (filters: IndicatorValueFilters): Promise<IndicatorValuesResponse> {
    const params = new URLSearchParams()
    if (filters.indicator_id) {
      params.append('indicator_id', String(filters.indicator_id))
    }
    if (filters.period_start) {
      params.append('period_start', filters.period_start)
    }
    if (filters.period_end) {
      params.append('period_end', filters.period_end)
    }
    if (filters.validated !== undefined) {
      params.append('validated', filters.validated ? '1' : '0')
    }

    const response = await api.get<IndicatorValuesResponse>(`/indicator-values?${params}`)
    return response.data
  },

  /**
   * Record new indicator value
   */
  async recordValue (payload: RecordIndicatorValuePayload): Promise<IndicatorValue> {
    const response = await api.post<{ data: IndicatorValue }>('/indicator-values', payload)
    return response.data.data
  },

  /**
   * Update indicator value
   */
  async updateValue (id: number, payload: Partial<RecordIndicatorValuePayload>): Promise<IndicatorValue> {
    const response = await api.put<{ data: IndicatorValue }>(`/indicator-values/${id}`, payload)
    return response.data.data
  },

  /**
   * Delete indicator value
   */
  async deleteValue (id: number): Promise<void> {
    await api.delete(`/indicator-values/${id}`)
  },

  /**
   * Validate indicator value
   */
  async validateValue (id: number): Promise<IndicatorValue> {
    const response = await api.post<{ data: IndicatorValue }>(`/indicator-values/${id}/validate`)
    return response.data.data
  },

  /**
   * Get trend data for indicator
   */
  async getTrend (indicatorId: number, periodStart: string, periodEnd: string): Promise<TrendData> {
    const response = await api.get<{ data: TrendData }>(
      `/indicators/${indicatorId}/trend?period_start=${periodStart}&period_end=${periodEnd}`,
    )
    return response.data.data
  },

  // ============================================
  // OBJECTIVES
  // ============================================

  /**
   * Get all objectives
   */
  async getAllObjectives (filters?: ObjectiveFilters): Promise<ObjectivesResponse> {
    const params = new URLSearchParams()
    if (filters?.category) {
      params.append('category', filters.category)
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.priority) {
      params.append('priority', filters.priority)
    }
    if (filters?.responsible_id) {
      params.append('responsible_id', String(filters.responsible_id))
    }
    if (filters?.overdue) {
      params.append('overdue', '1')
    }
    if (filters?.search) {
      params.append('search', filters.search)
    }
    if (filters?.page) {
      params.append('page', String(filters.page))
    }
    if (filters?.per_page) {
      params.append('per_page', String(filters.per_page))
    }

    const response = await api.get<ObjectivesResponse>(`/objectives?${params}`)
    return response.data
  },

  /**
   * Get objective by ID
   */
  async getObjectiveById (id: number): Promise<Objective> {
    const response = await api.get<{ data: Objective }>(`/objectives/${id}`)
    return response.data.data
  },

  /**
   * Create new objective
   */
  async createObjective (payload: CreateObjectivePayload): Promise<Objective> {
    const response = await api.post<{ data: Objective }>('/objectives', payload)
    return response.data.data
  },

  /**
   * Update objective
   */
  async updateObjective (id: number, payload: UpdateObjectivePayload): Promise<Objective> {
    const response = await api.put<{ data: Objective }>(`/objectives/${id}`, payload)
    return response.data.data
  },

  /**
   * Delete objective
   */
  async deleteObjective (id: number): Promise<void> {
    await api.delete(`/objectives/${id}`)
  },

  /**
   * Mark objective as achieved
   */
  async achieveObjective (id: number, achievedDate?: string): Promise<Objective> {
    const response = await api.post<{ data: Objective }>(`/objectives/${id}/achieve`, {
      achieved_date: achievedDate,
    })
    return response.data.data
  },

  /**
   * Update objective milestone
   */
  async updateMilestone (
    objectiveId: number,
    milestoneId: number,
    data: Partial<{ achieved: boolean, achieved_date: string, note: string }>,
  ): Promise<Objective> {
    const response = await api.put<{ data: Objective }>(
      `/objectives/${objectiveId}/milestones/${milestoneId}`,
      data,
    )
    return response.data.data
  },

  /**
   * Get objective statistics
   */
  async getObjectiveStatistics (): Promise<ObjectiveStatistics> {
    const response = await api.get<{ data: ObjectiveStatistics }>('/objectives/statistics')
    return response.data.data
  },

  // ============================================
  // DASHBOARDS
  // ============================================

  /**
   * Get all dashboards for current user
   */
  async getDashboards (): Promise<Dashboard[]> {
    const response = await api.get<{ data: Dashboard[] }>('/dashboards')
    return response.data.data
  },

  /**
   * Get dashboard by ID with data
   */
  async getDashboard (id: number): Promise<DashboardResponse> {
    const response = await api.get<DashboardResponse>(`/dashboards/${id}`)
    return response.data
  },

  /**
   * Create dashboard
   */
  async createDashboard (payload: Partial<Dashboard>): Promise<Dashboard> {
    const response = await api.post<{ data: Dashboard }>('/dashboards', payload)
    return response.data.data
  },

  /**
   * Update dashboard
   */
  async updateDashboard (id: number, payload: Partial<Dashboard>): Promise<Dashboard> {
    const response = await api.put<{ data: Dashboard }>(`/dashboards/${id}`, payload)
    return response.data.data
  },

  /**
   * Delete dashboard
   */
  async deleteDashboard (id: number): Promise<void> {
    await api.delete(`/dashboards/${id}`)
  },

  /**
   * Get performance snapshot for period
   */
  async getPerformanceSnapshot (period: string): Promise<PerformanceSnapshot> {
    const response = await api.get<{ data: PerformanceSnapshot }>(
      `/indicators/performance-snapshot?period=${period}`,
    )
    return response.data.data
  },
}
