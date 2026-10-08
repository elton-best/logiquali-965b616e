import type { Objective } from '@/types/improvement'
import api from '@/api/client'

const BASE_URL = '/improvement/objectives'

interface ObjectiveFilters {
  status?: string
  axes?: string[]
  indicateur_id?: number
  year?: number
}

export default {
  /**
   * Get all objectives with optional filters
   */
  async getAll (filters?: ObjectiveFilters) {
    const params = new URLSearchParams()
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }
    if (filters?.indicateur_id) {
      params.append('indicateur_id', filters.indicateur_id.toString())
    }
    if (filters?.year) {
      params.append('year', filters.year.toString())
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}?${queryString}` : BASE_URL

    return api.get<{ data: Objective[] }>(url)
  },

  /**
   * Get objective by ID
   */
  async getById (id: number) {
    return api.get<{ data: Objective }>(`${BASE_URL}/${id}`)
  },

  /**
   * Create new objective
   */
  async create (data: Partial<Objective>) {
    return api.post<{ data: Objective }>(BASE_URL, data)
  },

  /**
   * Update objective
   */
  async update (id: number, data: Partial<Objective>) {
    return api.put<{ data: Objective }>(`${BASE_URL}/${id}`, data)
  },

  /**
   * Delete objective
   */
  async delete (id: number) {
    return api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Update objective progress
   */
  async updateProgress (id: number, progress: number, comment?: string) {
    return api.put<{ data: Objective }>(`${BASE_URL}/${id}/progress`, {
      progress,
      comment,
    })
  },

  /**
   * Link indicator to objective
   */
  async linkIndicator (objectiveId: number, indicatorId: number) {
    return api.post<{ data: Objective }>(`${BASE_URL}/${objectiveId}/link-indicator`, {
      indicateur_id: indicatorId,
    })
  },

  /**
   * Unlink indicator from objective
   */
  async unlinkIndicator (objectiveId: number) {
    return api.delete(`${BASE_URL}/${objectiveId}/link-indicator`)
  },

  /**
   * Get objective statistics
   */
  async getStatistics () {
    return api.get<{ data: any }>(`${BASE_URL}/statistics`)
  },

  /**
   * Get objectives progress report
   */
  async getProgressReport (filters?: { year?: number, axes?: string[] }) {
    const params = new URLSearchParams()
    if (filters?.year) {
      params.append('year', filters.year.toString())
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}/progress-report?${queryString}` : `${BASE_URL}/progress-report`

    return api.get<{ data: any }>(url)
  },

  /**
   * Export objectives to PDF
   */
  async exportPdf (filters?: ObjectiveFilters) {
    const params = new URLSearchParams()
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}/export/pdf?${queryString}` : `${BASE_URL}/export/pdf`

    return api.get(url, {
      responseType: 'blob',
    })
  },

  /**
   * Export objectives to CSV
   */
  async exportCsv (filters?: ObjectiveFilters) {
    const params = new URLSearchParams()
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.axes) {
      params.append('axes', filters.axes.join(','))
    }

    const queryString = params.toString()
    const url = queryString ? `${BASE_URL}/export/csv?${queryString}` : `${BASE_URL}/export/csv`

    return api.get(url, {
      responseType: 'blob',
    })
  },
}
