import type { AuditProgram, AuditProgramFilters } from '@/types/models/auditPrograms'
import api from '@/api/client'

class AuditProgramService {
  private basePath = '/audit-programs'

  async getAll (filters?: AuditProgramFilters) {
    const params = new URLSearchParams()
    if (filters?.year) {
      params.append('year', filters.year.toString())
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }
    if (filters?.site_id) {
      params.append('site_id', filters.site_id.toString())
    }
    if (filters?.per_page) {
      params.append('per_page', filters.per_page.toString())
    }

    const response = await api.get(`${this.basePath}?${params.toString()}`)
    return response.data
  }

  async getById (id: number) {
    const response = await api.get(`${this.basePath}/${id}`)
    return response.data
  }

  async create (data: Partial<AuditProgram>) {
    const response = await api.post(this.basePath, data)
    return response.data
  }

  async update (id: number, data: Partial<AuditProgram>) {
    const response = await api.put(`${this.basePath}/${id}`, data)
    return response.data
  }

  async delete (id: number) {
    const response = await api.delete(`${this.basePath}/${id}`)
    return response.data
  }

  async validate (id: number) {
    const response = await api.post(`${this.basePath}/${id}/validate`)
    return response.data
  }

  async generateFromRisks (id: number, minCriticality = 12, includeAllProcesses = false) {
    const response = await api.post(`${this.basePath}/${id}/generate-from-risks`, {
      min_criticality: minCriticality,
      include_all_processes: includeAllProcesses,
    })
    return response.data
  }

  async exportCalendar (id: number): Promise<Blob> {
    const response = await api.get(`${this.basePath}/${id}/export-calendar`, {
      responseType: 'blob',
    })
    return response.data
  }

  async getStatistics (id: number) {
    const response = await api.get(`${this.basePath}/${id}/statistics`)
    return response.data
  }
}

export default new AuditProgramService()
