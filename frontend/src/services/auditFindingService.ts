/**
 * Service API pour les Constats d'Audits (Findings)
 */

import type {
  AuditFinding,
  AuditFindingFilters,
  AuditFindingStatistics,
  CreateAuditFindingPayload,
  PaginatedAuditFindingsResponse,
  UpdateAuditFindingPayload,
} from '@/types/audit'
import type { ApiResponse } from '@/types/shared'
import api from '@/api/client'

const BASE_URL = '/audit-findings'

export const auditFindingService = {
  /**
   * Liste des constats
   */
  async getAll (filters?: AuditFindingFilters): Promise<PaginatedAuditFindingsResponse> {
    const response = await api.get<PaginatedAuditFindingsResponse>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Détails d'un constat
   */
  async getById (id: number): Promise<AuditFinding> {
    const response = await api.get<AuditFinding>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Créer un constat
   */
  async create (data: CreateAuditFindingPayload): Promise<AuditFinding> {
    const response = await api.post<AuditFinding>(BASE_URL, data)
    return response.data
  },

  /**
   * Mettre à jour un constat
   */
  async update (id: number, data: UpdateAuditFindingPayload): Promise<AuditFinding> {
    const response = await api.put<AuditFinding>(`${BASE_URL}/${id}`, data)
    return response.data
  },

  /**
   * Supprimer un constat
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Générer NC à partir d'un constat
   */
  async generateNC (id: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(`${BASE_URL}/${id}/generate-nc`)
    return response.data
  },

  /**
   * Résoudre un constat
   */
  async resolve (id: number, notes?: string): Promise<AuditFinding> {
    const response = await api.post<AuditFinding>(`${BASE_URL}/${id}/resolve`, { notes })
    return response.data
  },

  /**
   * Vérifier un constat résolu
   */
  async verify (id: number, notes?: string): Promise<AuditFinding> {
    const response = await api.post<AuditFinding>(`${BASE_URL}/${id}/verify`, { notes })
    return response.data
  },

  /**
   * Clôturer un constat
   */
  async close (id: number): Promise<AuditFinding> {
    const response = await api.post<AuditFinding>(`${BASE_URL}/${id}/close`)
    return response.data
  },

  /**
   * Upload photo/pièce jointe
   */
  async uploadAttachment (id: number, file: File): Promise<ApiResponse<{ url: string }>> {
    const formData = new FormData()
    formData.append('file', file)

    const response = await api.post<ApiResponse<{ url: string }>>(
      `${BASE_URL}/${id}/attachments`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      },
    )
    return response.data
  },

  /**
   * Supprimer une pièce jointe
   */
  async deleteAttachment (id: number, url: string): Promise<void> {
    await api.delete(`${BASE_URL}/${id}/attachments`, {
      data: { url },
    })
  },

  /**
   * Obtenir statistiques
   */
  async getStatistics (filters?: Partial<AuditFindingFilters>): Promise<AuditFindingStatistics> {
    const response = await api.get<AuditFindingStatistics>(`${BASE_URL}/statistics`, {
      params: filters,
    })
    return response.data
  },
}
