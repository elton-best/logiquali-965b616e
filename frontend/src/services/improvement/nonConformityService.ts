import type {
  AnalyzeNonConformityDto,
  CreateNonConformityDto,
  NonConformity,
} from '@/types/improvement'
import api from '@/api/client'

export const nonConformityService = {
  /**
   * Get all non-conformities with optional filters
   */
  async getAll (filters?: any): Promise<NonConformity[]> {
    const { data } = await api.get('/non-conformities', { params: filters })
    return data
  },

  /**
   * Get single non-conformity by ID
   */
  async getById (id: number): Promise<NonConformity> {
    const { data } = await api.get(`/non-conformities/${id}`)
    return data
  },

  /**
   * Create new non-conformity
   */
  async create (payload: CreateNonConformityDto): Promise<NonConformity> {
    const { data } = await api.post('/non-conformities', payload)
    return data
  },

  /**
   * Update existing non-conformity
   */
  async update (id: number, payload: Partial<NonConformity>): Promise<NonConformity> {
    const { data } = await api.put(`/non-conformities/${id}`, payload)
    return data
  },

  /**
   * Analyze non-conformity (5 Why or Ishikawa)
   */
  async analyze (id: number, payload: AnalyzeNonConformityDto): Promise<NonConformity> {
    const { data } = await api.post(`/non-conformities/${id}/analyze`, payload)
    return data
  },

  /**
   * Validate non-conformity (Quality Manager)
   */
  async validate (id: number, payload: { is_validated: boolean, validation_notes?: string }): Promise<NonConformity> {
    const { data } = await api.post(`/non-conformities/${id}/validate`, payload)
    return data
  },

  /**
   * Verify action effectiveness
   */
  async verify (id: number, payload: { is_effective: boolean, verification_notes?: string }): Promise<NonConformity> {
    const { data } = await api.post(`/non-conformities/${id}/verify`, payload)
    return data
  },

  /**
   * Close non-conformity
   */
  async close (id: number): Promise<NonConformity> {
    const { data } = await api.post(`/non-conformities/${id}/close`)
    return data
  },

  /**
   * Delete non-conformity
   */
  async delete (id: number): Promise<void> {
    await api.delete(`/non-conformities/${id}`)
  },

  /**
   * Get statistics
   */
  async getStatistics (siteId?: number): Promise<any> {
    const params = siteId ? { site_id: siteId } : {}
    const { data } = await api.get('/non-conformities/statistics', { params })
    return data
  },
}
