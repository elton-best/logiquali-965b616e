/**
 * Service API pour les Programmes d'Audits
 */

import type {
  AuditProgram,
  AuditProgramFilters,
  AuditProgramStatistics,
  CreateAuditProgramPayload,
  PaginatedAuditProgramsResponse,
} from '@/types/audit'
import api from '@/api/client'

const BASE_URL = '/audit-programs'

export const auditProgramService = {
  /**
   * Liste des programmes d'audits
   */
  async getAll (filters?: AuditProgramFilters): Promise<PaginatedAuditProgramsResponse> {
    const response = await api.get<PaginatedAuditProgramsResponse>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Détails d'un programme
   */
  async getById (id: number): Promise<AuditProgram> {
    const response = await api.get<AuditProgram>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Créer un programme
   */
  async create (data: CreateAuditProgramPayload): Promise<AuditProgram> {
    const response = await api.post<AuditProgram>(BASE_URL, data)
    return response.data
  },

  /**
   * Mettre à jour un programme
   */
  async update (id: number, data: Partial<CreateAuditProgramPayload>): Promise<AuditProgram> {
    const response = await api.put<AuditProgram>(`${BASE_URL}/${id}`, data)
    return response.data
  },

  /**
   * Supprimer un programme
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Approuver un programme
   */
  async approve (id: number): Promise<AuditProgram> {
    const response = await api.post<AuditProgram>(`${BASE_URL}/${id}/approve`)
    return response.data
  },

  /**
   * Générer audits à partir du programme
   */
  async generateAudits (id: number): Promise<AuditProgram> {
    const response = await api.post<AuditProgram>(`${BASE_URL}/${id}/generate-audits`)
    return response.data
  },

  /**
   * Obtenir statistiques
   */
  async getStatistics (year?: number): Promise<AuditProgramStatistics> {
    const response = await api.get<AuditProgramStatistics>(`${BASE_URL}/statistics`, {
      params: { year },
    })
    return response.data
  },

  /**
   * Exporter programme en Excel
   */
  async exportExcel (id: number): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/${id}/export`, {
      responseType: 'blob',
    })
    return response.data
  },
}
