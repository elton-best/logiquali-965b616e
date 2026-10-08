import type { Action } from '@/types/action'
import api from '@/api/client'

const BASE_URL = '/improvement/actions'

export default {
  /**
   * Get all actions with optional filters
   */
  async getAll (filters?: Record<string, any>) {
    const params = new URLSearchParams()
    if (filters?.type) {
      params.append('type', filters.type)
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }
    if (filters?.responsible_id) {
      params.append('responsible_id', filters.responsible_id.toString())
    }
    if (filters?.source_type) {
      params.append('source_type', filters.source_type)
    }
    if (filters?.source_id) {
      params.append('source_id', filters.source_id.toString())
    }
    if (filters?.plan_action_id) {
      params.append('plan_action_id', filters.plan_action_id.toString())
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}?${queryString}` : BASE_URL

    return api.get<{ data: Action[] }>(url)
  },

  /**
   * Get action by ID
   */
  async getById (id: number) {
    return api.get<{ data: Action }>(`${BASE_URL}/${id}`)
  },

  /**
   * Create new action
   */
  async create (data: Partial<Action>) {
    return api.post<{ data: Action }>(BASE_URL, data)
  },

  /**
   * Update action
   */
  async update (id: number, data: Partial<Action>) {
    return api.put<{ data: Action }>(`${BASE_URL}/${id}`, data)
  },

  /**
   * Delete action
   */
  async delete (id: number) {
    return api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Update action progress
   */
  async updateProgress (id: number, progress: number, comment?: string) {
    return api.put<{ data: Action }>(`${BASE_URL}/${id}/progress`, {
      progress,
      comment,
    })
  },

  /**
   * Assign action to user
   */
  async assign (id: number, userId: number) {
    return api.post<{ data: Action }>(`${BASE_URL}/${id}/assign`, {
      responsible_id: userId,
    })
  },

  /**
   * Complete action
   */
  async complete (id: number, verification?: any) {
    return api.post<{ data: Action }>(`${BASE_URL}/${id}/complete`, {
      verification,
    })
  },

  /**
   * Verify action effectiveness
   */
  async verify (id: number, verification: any) {
    return api.post<{ data: Action }>(`${BASE_URL}/${id}/verify`, verification)
  },

  /**
   * Get action statistics
   */
  async getStatistics () {
    return api.get<{ data: any }>(`${BASE_URL}/statistics`)
  },

  /**
   * Get actions by source (polymorphic)
   */
  async getBySource (sourceType: string, sourceId: number) {
    return api.get<{ data: Action[] }>(`${BASE_URL}/source/${sourceType}/${sourceId}`)
  },

  /**
   * Bulk create actions (for action plans)
   */
  async bulkCreate (actions: Partial<Action>[]) {
    return api.post<{ data: Action[] }>(`${BASE_URL}/bulk`, { actions })
  },

  /**
   * Export actions to CSV
   */
  async export (filters?: Record<string, any>) {
    const params = new URLSearchParams()
    if (filters?.type) {
      params.append('type', filters.type)
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}/export?${queryString}` : `${BASE_URL}/export`

    return api.get(url, {
      responseType: 'blob',
    })
  },
}
