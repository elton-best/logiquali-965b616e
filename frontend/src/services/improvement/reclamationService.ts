import type { Reclamation, ReclamationFilters } from '@/types/improvement'
import api from '@/api/client'

const BASE_URL = '/improvement/reclamations'

export default {
  /**
   * Get all reclamations with optional filters
   */
  async getAll (filters?: ReclamationFilters) {
    const params = new URLSearchParams()
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.severity) {
      params.append('severity', filters.severity)
    }
    if (filters?.source) {
      params.append('source', filters.source)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }
    if (filters?.assigned_to) {
      params.append('assigned_to', filters.assigned_to.toString())
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}?${queryString}` : BASE_URL

    return api.get<{ data: Reclamation[] }>(url)
  },

  /**
   * Get reclamation by ID
   */
  async getById (id: number) {
    return api.get<{ data: Reclamation }>(`${BASE_URL}/${id}`)
  },

  /**
   * Create new reclamation
   */
  async create (data: Partial<Reclamation>) {
    return api.post<{ data: Reclamation }>(BASE_URL, data)
  },

  /**
   * Update reclamation
   */
  async update (id: number, data: Partial<Reclamation>) {
    return api.put<{ data: Reclamation }>(`${BASE_URL}/${id}`, data)
  },

  /**
   * Delete reclamation
   */
  async delete (id: number) {
    return api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Assign reclamation to user
   */
  async assign (id: number, userId: number) {
    return api.post<{ data: Reclamation }>(`${BASE_URL}/${id}/assign`, {
      assigned_to: userId,
    })
  },

  /**
   * Respond to reclamation
   */
  async respond (id: number, response: string) {
    return api.post<{ data: Reclamation }>(`${BASE_URL}/${id}/respond`, {
      response,
    })
  },

  /**
   * Close reclamation
   */
  async close (id: number, satisfaction?: number) {
    return api.post<{ data: Reclamation }>(`${BASE_URL}/${id}/close`, {
      satisfaction,
    })
  },

  /**
   * Get reclamation statistics
   */
  async getStatistics () {
    return api.get<{ data: any }>(`${BASE_URL}/statistics`)
  },

  /**
   * Get satisfaction rate
   */
  async getSatisfactionRate (filters?: { period?: string, axes?: string[] }) {
    const params = new URLSearchParams()
    if (filters?.period) {
      params.append('period', filters.period)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}/satisfaction-rate?${queryString}` : `${BASE_URL}/satisfaction-rate`

    return api.get<{ data: any }>(url)
  },

  /**
   * Export reclamations to CSV
   */
  async export (filters?: ReclamationFilters) {
    const params = new URLSearchParams()
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.severity) {
      params.append('severity', filters.severity)
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
