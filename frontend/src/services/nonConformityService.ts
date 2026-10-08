/**
 * Service API pour le module Non-Conformités
 */

import type {
  AnalyzeNCPayload,
  CreateNCPayload,
  NCFilters,
  NCStatistics,
  NCTimelineEvent,
  NonConformity,
  UpdateNCPayload,
  ValidateNCPayload,
  VerifyEffectivenessNCPayload,
} from '@/types/nonConformity'
import type { ApiResponse, PaginatedResponse } from '@/types/shared'
import api from '@/api/client'

const BASE_URL = '/non-conformities'

export const nonConformityService = {
  /**
   * Liste des NC avec pagination et filtres
   */
  async getAll (filters?: NCFilters): Promise<PaginatedResponse<NonConformity>> {
    const response = await api.get<PaginatedResponse<NonConformity>>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Détails d'une NC
   */
  async getById (id: number): Promise<NonConformity> {
    const response = await api.get<NonConformity>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Créer une nouvelle NC
   */
  async create (data: CreateNCPayload): Promise<NonConformity> {
    const response = await api.post<NonConformity>(BASE_URL, data)
    return response.data
  },

  /**
   * Mettre à jour une NC
   */
  async update (id: number, data: UpdateNCPayload): Promise<NonConformity> {
    const response = await api.put<NonConformity>(`${BASE_URL}/${id}`, data)
    return response.data
  },

  /**
   * Supprimer une NC
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Analyser les causes d'une NC
   */
  async analyzeCauses (id: number, data: AnalyzeNCPayload): Promise<NonConformity> {
    const response = await api.post<NonConformity>(`${BASE_URL}/${id}/analyze`, data)
    return response.data
  },

  /**
   * Valider une NC (après analyse)
   */
  async validate (id: number, data: ValidateNCPayload): Promise<NonConformity> {
    const response = await api.post<NonConformity>(`${BASE_URL}/${id}/validate`, data)
    return response.data
  },

  /**
   * Vérifier l'efficacité des actions
   */
  async verifyEffectiveness (id: number, data: VerifyEffectivenessNCPayload): Promise<NonConformity> {
    const response = await api.post<NonConformity>(`${BASE_URL}/${id}/verify`, data)
    return response.data
  },

  /**
   * Clôturer une NC
   */
  async close (id: number, notes?: string): Promise<NonConformity> {
    const response = await api.post<NonConformity>(`${BASE_URL}/${id}/close`, { notes })
    return response.data
  },

  /**
   * Rouvrir une NC clôturée
   */
  async reopen (id: number, reason: string): Promise<NonConformity> {
    const response = await api.post<NonConformity>(`${BASE_URL}/${id}/reopen`, { reason })
    return response.data
  },

  /**
   * Obtenir les statistiques des NC
   */
  async getStatistics (filters?: Partial<NCFilters>): Promise<NCStatistics> {
    const response = await api.get<NCStatistics>(`${BASE_URL}/statistics`, {
      params: filters,
    })
    return response.data
  },

  /**
   * Obtenir la timeline d'une NC
   */
  async getTimeline (id: number): Promise<NCTimelineEvent[]> {
    const response = await api.get<NCTimelineEvent[]>(`${BASE_URL}/${id}/timeline`)
    return response.data
  },

  /**
   * Exporter les NC en Excel
   */
  async exportExcel (filters?: NCFilters): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/export/excel`, {
      params: filters,
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Exporter une NC en PDF
   */
  async exportPdf (id: number): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/${id}/export/pdf`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Génère un document d'inventaire pour la NC (source_type = non_conformity_report).
   * Retourne { id, code } du document créé.
   */
  async generateDocumentReport (id: number): Promise<{ id: number, code: string }> {
    const response = await api.post<{ generated_document_id: number, code: string }>(
      `${BASE_URL}/${id}/generate-report`,
    )
    const data = response.data as any
    return {
      id: Number(data?.generated_document_id || data?.data?.generated_document_id),
      code: String(data?.code || ''),
    }
  },

  /**
   * Générer rapport NC (avec analyse causes)
   */
  async generateReport (id: number): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/${id}/report`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Lier une NC à un risque
   */
  async linkToRisk (ncId: number, riskId: number): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(
      `${BASE_URL}/${ncId}/risks/${riskId}`,
    )
    return response.data
  },

  /**
   * Détacher une NC d'un risque
   */
  async unlinkFromRisk (ncId: number, riskId: number): Promise<void> {
    await api.delete(`${BASE_URL}/${ncId}/risks/${riskId}`)
  },
}
