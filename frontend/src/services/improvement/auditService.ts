import type { Audit, CreateAuditDto } from '@/types/improvement'
import api from '@/api/client'

export const auditService = {
  async getAll (filters?: any): Promise<Audit[]> {
    const { data } = await api.get('/audits', { params: filters })
    return data
  },

  async getById (id: number): Promise<Audit> {
    const { data } = await api.get(`/audits/${id}`)
    return data
  },

  async create (payload: CreateAuditDto): Promise<Audit> {
    const { data } = await api.post('/audits', payload)
    return data
  },

  async update (id: number, payload: Partial<Audit>): Promise<Audit> {
    const { data } = await api.put(`/audits/${id}`, payload)
    return data
  },

  async delete (id: number): Promise<void> {
    await api.delete(`/audits/${id}`)
  },

  async addFinding (id: number, payload: any): Promise<Audit> {
    const { data } = await api.post(`/audits/${id}/finding`, payload)
    return data
  },

  async complete (id: number): Promise<Audit> {
    const { data } = await api.post(`/audits/${id}/complete`)
    return data
  },

  async start (id: number): Promise<any> {
    const { data } = await api.post(`/audits/${id}/start`)
    return data
  },

  async conduct (id: number, findings: any[]): Promise<any> {
    const { data } = await api.post(`/audits/${id}/conduct`, { findings })
    return data
  },

  async generateChecklist (id: number, isoClauses: string[] = [], includeRisks = true): Promise<any> {
    const { data } = await api.post(`/audits/${id}/generate-checklist`, {
      iso_clauses: isoClauses,
      include_risks: includeRisks,
    })
    return data
  },

  async finalize (id: number, conclusion: string, format = 'pdf'): Promise<any> {
    const { data } = await api.post(`/audits/${id}/finalize`, {
      conclusion,
      format,
    })
    return data
  },

  async sendInvitations (id: number): Promise<any> {
    const { data } = await api.post(`/audits/${id}/send-invitations`)
    return data
  },

  async downloadReport (id: number, format = 'pdf'): Promise<Blob> {
    const response = await api.get(`/audits/${id}/download-report`, {
      params: { format },
      responseType: 'blob',
    })
    return (response as any)?.data ?? response
  },

  async generateReport (id: number): Promise<any> {
    const { data } = await api.post(`/audits/${id}/generate-report`)
    return data
  },

  async getStatistics (siteId?: number): Promise<any> {
    const params = siteId ? { site_id: siteId } : {}
    const { data } = await api.get('/audits/statistics', { params })
    return data
  },
}
