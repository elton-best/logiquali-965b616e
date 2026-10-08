import api from '@/api/client'

export interface SatisfactionSurvey {
  id: number
  ref: string
  site_id: number
  type: 'client' | 'employee' | 'supplier'
  year: number
  period?: string
  respondent_name?: string
  respondent_email?: string
  responses: Record<string, any>
  total_score?: number
  satisfaction_level?: 'very_satisfied' | 'satisfied' | 'moderately_satisfied' | 'dissatisfied' | 'very_dissatisfied'
  recommendations?: string
  created_at: string
  updated_at: string
  site?: {
    id: number
    name: string
  }
}

export interface SurveyResponse {
  site_id: number
  type: 'client' | 'employee' | 'supplier'
  year?: number
  period?: string
  respondent_name?: string
  respondent_email?: string
  responses: Record<string, any>
  recommendations?: string
}

export interface SatisfactionSurveyFilters {
  per_page?: number
  page?: number
  type?: 'client' | 'employee' | 'supplier'
  site_id?: number
  year?: number
  search?: string
}

class SatisfactionSurveyService {
  /**
   * Get all satisfaction surveys
   */
  async getSurveys (params?: SatisfactionSurveyFilters): Promise<{ data: SatisfactionSurvey[], meta: any }> {
    const { data } = await api.get('/satisfaction-surveys', { params })
    return {
      data: Array.isArray(data?.data) ? data.data.map((item: any) => normalizeSurvey(item)) : [],
      meta: data?.meta || {},
    }
  }

  /**
   * Get single survey
   */
  async getSurvey (id: number): Promise<SatisfactionSurvey> {
    const { data } = await api.get(`/satisfaction-surveys/${id}`)
    return normalizeSurvey(data?.data)
  }

  /**
   * Submit survey response
   */
  async submitSurvey (surveyData: SurveyResponse): Promise<SatisfactionSurvey> {
    const { data } = await api.post('/satisfaction-surveys', surveyData)
    return normalizeSurvey(data?.data)
  }

  /**
   * Create survey response
   */
  async createSurvey (surveyData: SurveyResponse): Promise<SatisfactionSurvey> {
    return this.submitSurvey(surveyData)
  }

  /**
   * Update survey response
   */
  async updateSurvey (id: number, surveyData: Partial<SurveyResponse>): Promise<SatisfactionSurvey> {
    const { data } = await api.put(`/satisfaction-surveys/${id}`, surveyData)
    return normalizeSurvey(data?.data)
  }

  /**
   * Delete survey response
   */
  async deleteSurvey (id: number): Promise<void> {
    await api.delete(`/satisfaction-surveys/${id}`)
  }

  /**
   * Get satisfaction level color
   */
  getSatisfactionColor (level?: string): string {
    const colors: Record<string, string> = {
      very_satisfied: 'success',
      satisfied: 'success',
      moderately_satisfied: 'warning',
      dissatisfied: 'error',
      very_dissatisfied: 'error',
    }
    return colors[level || ''] || 'grey'
  }

  /**
   * Get satisfaction level label
   */
  getSatisfactionLabel (level?: string): string {
    const labels: Record<string, string> = {
      very_satisfied: 'Très satisfait',
      satisfied: 'Satisfait',
      moderately_satisfied: 'Moyennement satisfait',
      dissatisfied: 'Insatisfait',
      very_dissatisfied: 'Très insatisfait',
    }
    return labels[level || ''] || 'Non évalué'
  }

  /**
   * Get satisfaction level icon
   */
  getSatisfactionIcon (level?: string): string {
    const icons: Record<string, string> = {
      very_satisfied: 'mdi-emoticon-excited-outline',
      satisfied: 'mdi-emoticon-happy-outline',
      moderately_satisfied: 'mdi-emoticon-neutral-outline',
      dissatisfied: 'mdi-emoticon-sad-outline',
      very_dissatisfied: 'mdi-emoticon-dead-outline',
    }
    return icons[level || ''] || 'mdi-emoticon-neutral-outline'
  }
}

function normalizeSurvey (item: any): SatisfactionSurvey {
  if (!item) {
    return item
  }

  if (item.attributes) {
    const attrs = item.attributes || {}
    const siteIdFromRelation = Number(item?.relationships?.site?.id || 0)
    return {
      id: Number(item.id),
      ...attrs,
      site_id: Number(attrs.site_id || siteIdFromRelation || 0),
    } as SatisfactionSurvey
  }

  const siteIdFromRelation = Number(item?.site?.id || item?.relationships?.site?.id || 0)
  return {
    ...item,
    id: Number(item.id),
    site_id: Number(item.site_id || siteIdFromRelation || 0),
  } as SatisfactionSurvey
}

export const satisfactionSurveyService = new SatisfactionSurveyService()
export default satisfactionSurveyService
