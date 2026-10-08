/**
 * Service API pour le module Audits
 */

import type {
  Audit,
  AuditFilters,
  AuditStatistics,
  AuditTimelineEvent,
  CompleteAuditPayload,
  ConductAuditPayload,
  CreateAuditPayload,
  PaginatedAuditsResponse,
  UpdateAuditPayload,
} from '@/types/audit'
import type { ApiResponse } from '@/types/shared'
import api from '@/api/client'

const BASE_URL = '/audits'

export const auditService = {
  /**
   * Liste des audits avec pagination et filtres
   */
  async getAll (filters?: AuditFilters): Promise<PaginatedAuditsResponse> {
    const response = await api.get<PaginatedAuditsResponse>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Détails d'un audit
   */
  async getById (id: number): Promise<Audit> {
    const response = await api.get<Audit>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Créer un nouvel audit
   */
  async create (data: CreateAuditPayload): Promise<Audit> {
    const response = await api.post<Audit>(BASE_URL, data)
    return response.data
  },

  /**
   * Mettre à jour un audit
   */
  async update (id: number, data: UpdateAuditPayload): Promise<Audit> {
    const response = await api.put<Audit>(`${BASE_URL}/${id}`, data)
    return response.data
  },

  /**
   * Supprimer un audit
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Démarrer un audit
   */
  async start (id: number, actual_start_date?: string): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/start`, {
      actual_start_date: actual_start_date || new Date().toISOString().split('T')[0],
    })
    return response.data
  },

  /**
   * Conduire audit (saisie terrain)
   */
  async conduct (id: number, data: ConductAuditPayload): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/conduct`, data)
    return response.data
  },

  /**
   * Compléter un audit
   */
  async complete (id: number, data: CompleteAuditPayload): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/complete`, data)
    return response.data
  },

  /**
   * Vérifier un audit
   */
  async verify (id: number, notes?: string): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/verify`, { notes })
    return response.data
  },

  /**
   * Approuver un audit
   */
  async approve (id: number, notes?: string): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/approve`, { notes })
    return response.data
  },

  /**
   * Générer checklist automatique
   */
  async generateChecklist (id: number, clauses?: string[]): Promise<ApiResponse<any>> {
    const response = await api.post<ApiResponse<any>>(
      `${BASE_URL}/${id}/checklist/generate`,
      { clauses_iso: clauses },
    )
    return response.data
  },

  /**
   * Générer rapport PDF
   */
  async generateReport (id: number): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/${id}/report`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Exporter liste audits en Excel
   */
  async exportExcel (filters?: AuditFilters): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/export/excel`, {
      params: filters,
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Obtenir statistiques
   */
  async getStatistics (filters?: Partial<AuditFilters>): Promise<AuditStatistics> {
    const response = await api.get<AuditStatistics>(`${BASE_URL}/statistics`, {
      params: filters,
    })
    return response.data
  },

  /**
   * Obtenir timeline
   */
  async getTimeline (id: number): Promise<AuditTimelineEvent[]> {
    const response = await api.get<AuditTimelineEvent[]>(`${BASE_URL}/${id}/timeline`)
    return response.data
  },

  /**
   * Dupliquer un audit
   */
  async duplicate (id: number): Promise<Audit> {
    const response = await api.post<Audit>(`${BASE_URL}/${id}/duplicate`)
    return response.data
  },
}
