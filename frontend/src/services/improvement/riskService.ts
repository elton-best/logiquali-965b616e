import type { AssessRiskDto, CreateRiskDto, Risk, TreatRiskDto } from '@/types/improvement'
import api from '@/api/client'

export const riskService = {
  async getAll (filters?: any): Promise<Risk[]> {
    const { data } = await api.get('/risks', { params: filters })
    return data
  },

  async getById (id: number): Promise<Risk> {
    const { data } = await api.get(`/risks/${id}`)
    return data
  },

  async create (payload: CreateRiskDto): Promise<Risk> {
    const { data } = await api.post('/risks', payload)
    return data
  },

  async update (id: number, payload: Partial<Risk>): Promise<Risk> {
    const { data } = await api.put(`/risks/${id}`, payload)
    return data
  },

  async delete (id: number): Promise<void> {
    await api.delete(`/risks/${id}`)
  },

  async assess (id: number, payload: AssessRiskDto): Promise<Risk> {
    const { data } = await api.post(`/risks/${id}/assess`, payload)
    return data
  },

  async treat (id: number, payload: TreatRiskDto): Promise<Risk> {
    const { data } = await api.post(`/risks/${id}/treat`, payload)
    return data
  },

  async getMatrix (filters?: any): Promise<any> {
    const { data } = await api.get('/risks/matrix', { params: filters })
    return data
  },

  async getStatistics (siteId?: number): Promise<any> {
    const params = siteId ? { site_id: siteId } : {}
    const { data } = await api.get('/risks/statistics', { params })
    return data
  },
}

export default riskService
