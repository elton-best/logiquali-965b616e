import api from '@/api/client'

export interface ClientSatisfactionFormCriteria {
  amabilite_ecoute: number
  disponibilite_spontaneite: number
  rapidite_traitement: number
  respect_delais: number
  conformite_produits: number
  traitement_reclamations: number
}

export interface ClientSatisfactionFormData {
  site_id: number
  client_name: string
  survey_date: string
  amabilite_ecoute: number
  disponibilite_spontaneite: number
  rapidite_traitement: number
  respect_delais: number
  conformite_produits: number
  traitement_reclamations: number
  recommendations?: string
  status?: 'draft' | 'submitted' | 'reviewed'
}

export interface ClientSatisfactionForm {
  id: number
  ref: string
  site_id: number
  site?: any
  client_name: string
  survey_date: string
  survey_date_formatted: string
  criteria: Record<string, { label: string, score: number, color: string }>
  total_score: number
  max_score: number
  satisfaction_percentage: number
  satisfaction_level: 'satisfied' | 'moderately_satisfied' | 'dissatisfied'
  satisfaction_level_label: string
  satisfaction_color: string
  recommendations?: string
  status: string
  status_label: string
  submitted_at?: string
  reviewed_at?: string
  reviewed_by?: number
  reviewer?: any
  created_at: string
  updated_at: string
  created_by: number
}

export interface ClientSatisfactionFormFilters {
  status?: string
  satisfaction_level?: string
  year?: number
  site_id?: number
  search?: string
  per_page?: number
  page?: number
}

export interface ClientSatisfactionFormStatistics {
  total_forms: number
  by_status: Record<string, number>
  by_satisfaction_level: Record<string, number>
  average_score: number
  average_percentage: number
  highest_score: number
  lowest_score: number
  criteria_averages: {
    amabilite_ecoute: number
    disponibilite_spontaneite: number
    rapidite_traitement: number
    respect_delais: number
    conformite_produits: number
    traitement_reclamations: number
  }
  monthly_trend: Array<{
    month: number
    total: number
    avg_satisfaction: number
  }>
}

class ClientSatisfactionFormService {
  private baseUrl = '/client-satisfaction-forms'

  /**
   * Récupérer la liste des fiches
   */
  async getAll (filters?: ClientSatisfactionFormFilters) {
    const response = await api.get(this.baseUrl, { params: filters })
    return response.data
  }

  /**
   * Récupérer une fiche spécifique
   */
  async getById (id: number): Promise<ClientSatisfactionForm> {
    const response = await api.get(`${this.baseUrl}/${id}`)
    return response.data.data
  }

  /**
   * Créer une nouvelle fiche
   */
  async create (data: ClientSatisfactionFormData): Promise<ClientSatisfactionForm> {
    const response = await api.post(this.baseUrl, data)
    return response.data.data
  }

  /**
   * Mettre à jour une fiche
   */
  async update (id: number, data: Partial<ClientSatisfactionFormData>): Promise<ClientSatisfactionForm> {
    const response = await api.put(`${this.baseUrl}/${id}`, data)
    return response.data.data
  }

  /**
   * Supprimer une fiche
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${this.baseUrl}/${id}`)
  }

  /**
   * Soumettre une fiche
   */
  async submit (id: number): Promise<ClientSatisfactionForm> {
    const response = await api.post(`${this.baseUrl}/${id}/submit`)
    return response.data.data
  }

  /**
   * Marquer une fiche comme examinée
   */
  async review (id: number, reviewNotes?: string): Promise<ClientSatisfactionForm> {
    const response = await api.post(`${this.baseUrl}/${id}/review`, {
      review_notes: reviewNotes,
    })
    return response.data.data
  }

  /**
   * Récupérer les statistiques
   */
  async getStatistics (params?: { year?: number, site_id?: number }): Promise<ClientSatisfactionFormStatistics> {
    const response = await api.get(`${this.baseUrl}/statistics`, {
      params,
    })
    return response.data
  }

  /**
   * Exporter une fiche en PDF
   */
  async exportPdf (id: number): Promise<Blob> {
    const response = await api.get(`${this.baseUrl}/${id}/export-pdf`, {
      responseType: 'blob',
    })
    return response.data
  }

  async exportPdfWithMeta (id: number): Promise<{ blob: Blob, generatedDocumentId: number | null }> {
    const response = await api.get(`${this.baseUrl}/${id}/export-pdf`, {
      responseType: 'blob',
    })
    return {
      blob: response.data,
      generatedDocumentId: Number(response.headers?.['x-generated-document-id'] || 0) || null,
    }
  }
}

export default new ClientSatisfactionFormService()
