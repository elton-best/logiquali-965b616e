/**
 * Indicators Service
 * API calls for managing QHSE KPIs
 */

import api from '@/api/client'

export type IndicatorCategory = 'qualite' | 'hygiene' | 'securite' | 'environnement' | 'performance'
export type IndicatorType = 'quantitative' | 'qualitative'
export type IndicatorFrequency = 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly'
export type IndicatorStatus = 'active' | 'inactive'

export interface Indicator {
  id: number
  code: string
  name: string
  description: string
  category: IndicatorCategory
  type: IndicatorType
  unit?: string
  calculation_formula?: string
  frequency: IndicatorFrequency
  target_value: number
  threshold_min?: number
  threshold_max?: number
  process_id?: number
  process?: {
    id: number
    name: string
  }
  responsible_id?: number
  responsible?: {
    id: number
    name: string
    email: string
  }
  data_source?: string
  status: IndicatorStatus
  created_at: string
  updated_at: string
}

export interface IndicatorValue {
  id: number
  indicator_id: number
  indicator?: Indicator
  period: string
  value: number
  target: number
  comment?: string
  created_at: string
}

export interface CreateIndicatorDTO {
  code: string
  name: string
  description: string
  category: IndicatorCategory
  type: IndicatorType
  unit?: string
  calculation_formula?: string
  frequency: IndicatorFrequency
  target_value: number
  threshold_min?: number
  threshold_max?: number
  process_id?: number
  responsible_id?: number
  data_source?: string
  status: IndicatorStatus
}

export interface UpdateIndicatorDTO extends Partial<CreateIndicatorDTO> {}

export interface RecordValueDTO {
  period: string
  value: number
  target?: number
  comment?: string
}

export interface IndicatorsListParams {
  page?: number
  per_page?: number
  category?: IndicatorCategory
  status?: IndicatorStatus
  frequency?: IndicatorFrequency
  search?: string
}

export interface ChartDataPoint {
  period: string
  value: number
  target: number
}

class IndicatorsService {
  async getAll (params?: IndicatorsListParams) {
    const response = await api.get<{ data: Indicator[], meta: any }>('/indicators', { params })
    return response.data
  }

  async getById (id: number) {
    const response = await api.get<Indicator>(`/indicators/${id}`)
    return response.data
  }

  async create (data: CreateIndicatorDTO) {
    const response = await api.post<Indicator>('/indicators', data)
    return response.data
  }

  async update (id: number, data: UpdateIndicatorDTO) {
    const response = await api.put<Indicator>(`/indicators/${id}`, data)
    return response.data
  }

  async delete (id: number) {
    await api.delete(`/indicators/${id}`)
  }

  async recordValue (indicatorId: number, data: RecordValueDTO) {
    const response = await api.post<IndicatorValue>(
      `/indicators/${indicatorId}/values`,
      data,
    )
    return response.data
  }

  async getHistory (indicatorId: number, params?: { limit?: number, from?: string, to?: string }) {
    const response = await api.get<IndicatorValue[]>(
      `/indicators/${indicatorId}/history`,
      { params },
    )
    return response.data
  }

  async getChart (indicatorId: number, params?: { periods?: number, from?: string, to?: string }) {
    const response = await api.get<ChartDataPoint[]>(
      `/indicators/${indicatorId}/chart`,
      { params },
    )
    return response.data
  }

  async getValues (params?: { indicator_id?: number, page?: number, per_page?: number }) {
    const response = await api.get<{ data: IndicatorValue[], meta: any }>('/indicator-values', { params })
    return response.data
  }
}

export const indicatorsService = new IndicatorsService()
