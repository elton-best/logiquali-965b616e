import type { ApiResponse, PaginatedResponse } from '@/types/api'
import api from '@/api/client'

export interface EvaluationCriteria {
  id: number
  ref: string | null
  enterprise_id: number
  site_id: number | null
  name: string
  code: string | null
  description: string | null
  category: string | null
  scale_type: 'numeric' | 'stars' | 'percentage' | 'custom'
  scale_min: number
  scale_max: number
  scale_labels: Record<string, string> | null
  weight: number
  is_mandatory: boolean
  is_active: boolean
  form_type: EvaluationFormType
  display_order: number
  created_by: number | null
  updated_by: number | null
  created_at: string
  updated_at: string
  // Computed
  scale_options?: Record<number, string>
  form_type_label?: string
  scale_type_label?: string
  max_weighted_score?: number
  // Relations
  site?: { id: number, name: string }
  created_by_user?: { id: number, name: string }
  updated_by_user?: { id: number, name: string }
}

export type EvaluationFormType
  = | 'satisfaction_client'
    | 'satisfaction_personnel'
    | 'performance_personnel'
    | 'evaluation_personnel'
    | 'evaluation_auditeur'
    | 'satisfaction_fournisseur'
    | 'performance_fournisseur'
    | 'evaluation_fournisseur'
    | 'audit_interne'
    | 'custom'

export interface EvaluationCriteriaFilters {
  form_type?: EvaluationFormType
  category?: string
  is_active?: boolean
  site_id?: number
  per_page?: number
}

export interface CreateEvaluationCriteriaPayload {
  name: string
  code?: string
  description?: string
  category?: string
  scale_type?: 'numeric' | 'stars' | 'percentage' | 'custom'
  scale_min?: number
  scale_max?: number
  scale_labels?: Record<string, string>
  weight?: number
  is_mandatory?: boolean
  is_active?: boolean
  form_type: EvaluationFormType
  display_order?: number
  site_id?: number | null
}

export interface UpdateEvaluationCriteriaPayload extends Partial<CreateEvaluationCriteriaPayload> {}

export interface DefaultCriteriaTemplate {
  code: string
  name: string
  category: string
  scale_labels: Record<string, string>
}

export interface ReorderPayload {
  criteria: Array<{ id: number, display_order: number }>
}

// API Functions
export const evaluationCriteriaApi = {
  /**
   * Liste des critères d'évaluation avec filtres
   */
  async list (filters?: EvaluationCriteriaFilters): Promise<PaginatedResponse<EvaluationCriteria>> {
    const params = new URLSearchParams()
    if (filters?.form_type) {
      params.append('form_type', filters.form_type)
    }
    if (filters?.category) {
      params.append('category', filters.category)
    }
    if (filters?.is_active !== undefined) {
      params.append('is_active', String(filters.is_active))
    }
    if (filters?.site_id) {
      params.append('site_id', String(filters.site_id))
    }
    if (filters?.per_page) {
      params.append('per_page', String(filters.per_page))
    }

    const { data } = await api.get<PaginatedResponse<EvaluationCriteria>>(
      `/evaluation-criteria?${params.toString()}`,
    )
    return data
  },

  /**
   * Récupérer un critère par ID
   */
  async get (id: number): Promise<ApiResponse<EvaluationCriteria>> {
    const { data } = await api.get<ApiResponse<EvaluationCriteria>>(`/evaluation-criteria/${id}`)
    return data
  },

  /**
   * Créer un nouveau critère
   */
  async create (payload: CreateEvaluationCriteriaPayload): Promise<ApiResponse<EvaluationCriteria>> {
    const { data } = await api.post<ApiResponse<EvaluationCriteria>>('/evaluation-criteria', payload)
    return data
  },

  /**
   * Mettre à jour un critère
   */
  async update (id: number, payload: UpdateEvaluationCriteriaPayload): Promise<ApiResponse<EvaluationCriteria>> {
    const { data } = await api.put<ApiResponse<EvaluationCriteria>>(`/evaluation-criteria/${id}`, payload)
    return data
  },

  /**
   * Supprimer un critère
   */
  async delete (id: number): Promise<ApiResponse<null>> {
    const { data } = await api.delete<ApiResponse<null>>(`/evaluation-criteria/${id}`)
    return data
  },

  /**
   * Obtenir les critères par défaut pour un type de formulaire
   */
  async getDefaults (formType: EvaluationFormType): Promise<ApiResponse<DefaultCriteriaTemplate[]>> {
    const { data } = await api.get<ApiResponse<DefaultCriteriaTemplate[]>>(
      `/evaluation-criteria/defaults?form_type=${formType}`,
    )
    return data
  },

  /**
   * Initialiser les critères par défaut pour une entreprise
   */
  async initializeDefaults (formType: EvaluationFormType): Promise<ApiResponse<EvaluationCriteria[]>> {
    const { data } = await api.post<ApiResponse<EvaluationCriteria[]>>(
      '/evaluation-criteria/initialize-defaults',
      { form_type: formType },
    )
    return data
  },

  /**
   * Réorganiser l'ordre des critères
   */
  async reorder (payload: ReorderPayload): Promise<ApiResponse<null>> {
    const { data } = await api.post<ApiResponse<null>>('/evaluation-criteria/reorder', payload)
    return data
  },

  /**
   * Lister les catégories existantes
   */
  async getCategories (): Promise<ApiResponse<string[]>> {
    const { data } = await api.get<ApiResponse<string[]>>('/evaluation-criteria/categories')
    return data
  },

  /**
   * Dupliquer un critère
   */
  async duplicate (id: number): Promise<ApiResponse<EvaluationCriteria>> {
    const { data } = await api.post<ApiResponse<EvaluationCriteria>>(`/evaluation-criteria/${id}/duplicate`)
    return data
  },

  /**
   * Activer/Désactiver un critère
   */
  async toggleActive (id: number): Promise<ApiResponse<EvaluationCriteria>> {
    const { data } = await api.post<ApiResponse<EvaluationCriteria>>(`/evaluation-criteria/${id}/toggle-active`)
    return data
  },
}

export default evaluationCriteriaApi
