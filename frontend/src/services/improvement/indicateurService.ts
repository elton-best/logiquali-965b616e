import type { AddKpiValueDto, CreateKpiDto, Indicateur } from '@/types/improvement'
import api from '@/api/client'

export const indicateurService = {
  async getAll (filters?: any): Promise<Indicateur[]> {
    const { data } = await api.get('/indicateurs', { params: filters })
    return data
  },

  async getById (id: number): Promise<Indicateur> {
    const { data } = await api.get(`/indicateurs/${id}`)
    return data
  },

  async create (payload: CreateKpiDto): Promise<Indicateur> {
    const { data } = await api.post('/indicateurs', payload)
    return data
  },

  async update (id: number, payload: Partial<Indicateur>): Promise<Indicateur> {
    const { data } = await api.put(`/indicateurs/${id}`, payload)
    return data
  },

  async delete (id: number): Promise<void> {
    await api.delete(`/indicateurs/${id}`)
  },

  async addValue (id: number, payload: AddKpiValueDto): Promise<Indicateur> {
    const { data } = await api.post(`/indicateurs/${id}/value`, payload)
    return data
  },

  async getChartData (id: number): Promise<any> {
    const { data } = await api.get(`/indicateurs/${id}/chart-data`)
    return data
  },

  async getAlerts (siteId?: number): Promise<any[]> {
    const params = siteId ? { site_id: siteId } : {}
    const { data } = await api.get('/indicateurs/alerts', { params })
    return data
  },
}
