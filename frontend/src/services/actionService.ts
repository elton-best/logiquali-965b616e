/**
 * Service API pour le module Actions
 */

import type {
  Action,
  ActionFilters,
  ActionStatistics,
  CreateActionPayload,
  UpdateActionPayload,
  UpdateProgressPayload,
  VerifyEffectivenessPayload,
} from '@/types/action'
import type { ApiResponse, PaginatedResponse } from '@/types/shared'
import api from '@/api/client'

const BASE_URL = '/actions'

export const actionService = {
  /**
   * Liste des actions avec pagination et filtres
   */
  async getAll (filters?: ActionFilters): Promise<PaginatedResponse<Action>> {
    const response = await api.get<PaginatedResponse<Action>>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Détails d'une action
   */
  async getById (id: number): Promise<Action> {
    const response = await api.get<Action>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Créer une nouvelle action
   */
  async create (data: CreateActionPayload): Promise<Action> {
    const response = await api.post<Action>(BASE_URL, data)
    return response.data
  },

  /**
   * Mettre à jour une action
   */
  async update (id: number, data: UpdateActionPayload): Promise<Action> {
    const response = await api.put<Action>(`${BASE_URL}/${id}`, data)
    return response.data
  },

  /**
   * Supprimer une action
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  /**
   * Mettre à jour la progression d'une action
   */
  async updateProgress (id: number, data: UpdateProgressPayload): Promise<Action> {
    const response = await api.post<Action>(`${BASE_URL}/${id}/progress`, data)
    return response.data
  },

  /**
   * Vérifier l'efficacité d'une action
   */
  async verifyEffectiveness (id: number, data: VerifyEffectivenessPayload): Promise<Action> {
    const response = await api.post<Action>(`${BASE_URL}/${id}/verify`, data)
    return response.data
  },

  /**
   * Obtenir les statistiques des actions
   */
  async getStatistics (filters?: Partial<ActionFilters>): Promise<ActionStatistics> {
    const response = await api.get<ActionStatistics>(`${BASE_URL}/statistics`, {
      params: filters,
    })
    return response.data
  },

  /**
   * Exporter les actions en Excel
   */
  async exportExcel (filters?: ActionFilters): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/export/excel`, {
      params: filters,
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Exporter un plan d'action en PDF
   */
  async exportPlanPdf (planId: number): Promise<Blob> {
    const response = await api.get(`/plan-actions/${planId}/export/pdf`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Template Excel pour import d'actions
   */
  async downloadTemplate (): Promise<Blob> {
    const response = await api.get(`${BASE_URL}/templates/plan`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Importer un plan d'actions depuis Excel
   */
  async importPlan (file: File): Promise<ApiResponse<any>> {
    const formData = new FormData()
    formData.append('file', file)

    const response = await api.post<ApiResponse<any>>(
      `${BASE_URL}/import-plan`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      },
    )
    return response.data
  },
}
