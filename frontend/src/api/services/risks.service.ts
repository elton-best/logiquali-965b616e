/**
 * Risks Service
 * API calls for managing QHSE risks
 */

import api from '@/api/client'

export type RiskCategory = 'qualite' | 'hygiene' | 'securite' | 'environnement'
export type RiskTreatment = 'accept' | 'reduce' | 'transfer' | 'avoid'
export type RiskStatus = 'identified' | 'analyzed' | 'treated' | 'monitored' | 'closed'

export interface Risk {
  id: number
  reference: string
  title: string
  description: string
  category: RiskCategory
  process?: string
  site_id: number
  site?: {
    id: number
    name: string
  }
  identified_by: string
  identified_date: string
  probability: number
  impact: number
  risk_score: number
  current_controls?: string
  residual_probability?: number
  residual_impact?: number
  residual_score?: number
  treatment?: RiskTreatment
  action_plan?: string
  responsible_id?: number
  responsible?: {
    id: number
    name: string
    email: string
  }
  review_date?: string
  status: RiskStatus
  created_at: string
  updated_at: string
}

export interface CreateRiskDTO {
  title: string
  description: string
  category: RiskCategory
  process?: string
  site_id: number
  identified_by: string
  identified_date: string
  probability: number
  impact: number
  current_controls?: string
}

export interface UpdateRiskDTO {
  title?: string
  description?: string
  category?: RiskCategory
  process?: string
  site_id?: number
  identified_by?: string
  identified_date?: string
  probability?: number
  impact?: number
  current_controls?: string
  status?: RiskStatus
}

export interface AssessRiskDTO {
  probability: number
  impact: number
  current_controls?: string
}

export interface UpdateTreatmentDTO {
  treatment: RiskTreatment
  action_plan?: string
  responsible_id?: number
  review_date?: string
  residual_probability?: number
  residual_impact?: number
}

export interface AddControlDTO {
  control: string
}

export interface RisksListParams {
  page?: number
  per_page?: number
  search?: string
  category?: RiskCategory
  status?: RiskStatus
  site_id?: number
  min_score?: number
  max_score?: number
}

export interface RisksListResponse {
  data: Risk[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

class RisksService {
  private readonly basePath = '/risks'

  /**
   * Get list of risks
   */
  async getRisks (params: RisksListParams = {}): Promise<RisksListResponse> {
    const response = await api.get<RisksListResponse>(this.basePath, { params })
    return response.data
  }

  /**
   * Get single risk by ID
   */
  async getRisk (id: number): Promise<Risk> {
    const response = await api.get<{ data: Risk }>(`${this.basePath}/${id}`)
    return response.data.data
  }

  /**
   * Create new risk
   */
  async createRisk (data: CreateRiskDTO): Promise<Risk> {
    const response = await api.post<{ data: Risk }>(this.basePath, data)
    return response.data.data
  }

  /**
   * Update risk
   */
  async updateRisk (id: number, data: UpdateRiskDTO): Promise<Risk> {
    const response = await api.put<{ data: Risk }>(`${this.basePath}/${id}`, data)
    return response.data.data
  }

  /**
   * Delete risk
   */
  async deleteRisk (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Assess risk (update probability, impact, and score)
   */
  async assessRisk (id: number, data: AssessRiskDTO): Promise<Risk> {
    const response = await api.post<{ data: Risk }>(`${this.basePath}/${id}/assess`, data)
    return response.data.data
  }

  /**
   * Update risk treatment
   */
  async updateTreatment (id: number, data: UpdateTreatmentDTO): Promise<Risk> {
    const response = await api.post<{ data: Risk }>(`${this.basePath}/${id}/treatment`, data)
    return response.data.data
  }

  /**
   * Add control to risk
   */
  async addControl (id: number, data: AddControlDTO): Promise<Risk> {
    const response = await api.post<{ data: Risk }>(`${this.basePath}/${id}/controls`, data)
    return response.data.data
  }
}

export const risksService = new RisksService()
