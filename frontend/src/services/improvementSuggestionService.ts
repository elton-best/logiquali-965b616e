import api from '@/api/client'

export type ImprovementSuggestionStatus = 'pending' | 'in_progress' | 'adopted' | 'rejected'
export type ImprovementSuggestionImpact = 'low' | 'medium' | 'high'

export interface ImprovementSuggestion {
  id: number
  ref: string
  site_id: number
  process_id?: number | null
  proposer_id?: number | null
  title: string
  description: string
  impact: ImprovementSuggestionImpact
  status: ImprovementSuggestionStatus
  normes?: string[] | null
  assigned_to?: number | null
  validation_comment?: string | null
  assignee?: { id: number, name?: string, email?: string } | null
  follow_up?: string | null
  proposed_at?: string | null
  created_at: string
  updated_at: string
  process?: { id: number, title?: string, name?: string } | null
  proposer?: { id: number, name?: string, email?: string } | null
  site?: { id: number, name?: string } | null
}

export interface ImprovementSuggestionListParams {
  site_id?: number
  process_id?: number
  proposer_id?: number
  status?: ImprovementSuggestionStatus
  impact?: ImprovementSuggestionImpact
  search?: string
  scope?: 'mine' | 'site' | 'enterprise'
  per_page?: number
}

class ImprovementSuggestionService {
  private readonly basePath = '/improvement-suggestions'

  async list (params: ImprovementSuggestionListParams = {}): Promise<ImprovementSuggestion[]> {
    const { data } = await api.get(this.basePath, { params })
    return Array.isArray(data?.data) ? data.data : []
  }

  async create (payload: Partial<ImprovementSuggestion>): Promise<ImprovementSuggestion> {
    const { data } = await api.post(this.basePath, payload)
    return data
  }

  async update (id: number, payload: Partial<ImprovementSuggestion>): Promise<ImprovementSuggestion> {
    const { data } = await api.put(`${this.basePath}/${id}`, payload)
    return data
  }

  async validate (
    id: number,
    payload: { decision: 'approved' | 'rejected', assigned_to?: number | null, validation_comment?: string },
  ): Promise<ImprovementSuggestion> {
    const { data } = await api.post(`${this.basePath}/${id}/validate`, payload)
    return data
  }

  async remove (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }
}

export const improvementSuggestionService = new ImprovementSuggestionService()
