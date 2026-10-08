/**
 * Processes Service
 * API calls for managing QHSE processes
 */

import api from '@/api/client'

export type ProcessCategory = 'realisation' | 'support' | 'management'
export type ProcessStatus = 'draft' | 'active' | 'under_review' | 'archived'

export interface ProcessInput {
  name: string
  source: string
}

export interface ProcessOutput {
  name: string
  destination: string
}

export interface Process {
  id: number
  code: string
  name: string
  description: string
  category: ProcessCategory
  owner_id: number
  owner?: {
    id: number
    name: string
    email: string
  }
  inputs: ProcessInput[]
  outputs: ProcessOutput[]
  resources: string[]
  indicators: number[]
  risks: number[]
  documents: number[]
  interactions: number[]
  status: ProcessStatus
  version: string
  last_review_date?: string
  next_review_date?: string
  created_at: string
  updated_at: string
}

export interface CreateProcessDTO {
  code: string
  name: string
  description: string
  category: ProcessCategory
  owner_id: number
  inputs?: ProcessInput[]
  outputs?: ProcessOutput[]
  resources?: string[]
  next_review_date?: string
}

export interface UpdateProcessDTO {
  code?: string
  name?: string
  description?: string
  category?: ProcessCategory
  owner_id?: number
  inputs?: ProcessInput[]
  outputs?: ProcessOutput[]
  resources?: string[]
  indicators?: number[]
  risks?: number[]
  documents?: number[]
  status?: ProcessStatus
  next_review_date?: string
}

export interface UpdateInteractionsDTO {
  interactions: number[]
}

export interface ProcessesListParams {
  page?: number
  per_page?: number
  search?: string
  category?: ProcessCategory
  status?: ProcessStatus
  owner_id?: number
  site_id?: number
}

export interface ProcessesListResponse {
  data: Process[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ProcessMapResponse {
  processes: Process[]
  interactions: Array<{
    from: number
    to: number
  }>
}

class ProcessesService {
  private readonly basePath = '/processes'

  /**
   * Get list of processes
   */
  async getProcesses (params: ProcessesListParams = {}): Promise<ProcessesListResponse> {
    const response = await api.get<ProcessesListResponse>(this.basePath, { params })
    return response.data
  }

  /**
   * Get single process by ID
   */
  async getProcess (id: number): Promise<Process> {
    const response = await api.get<{ data: Process }>(`${this.basePath}/${id}`)
    return response.data.data
  }

  /**
   * Create new process
   */
  async createProcess (data: CreateProcessDTO): Promise<Process> {
    const response = await api.post<{ data: Process }>(this.basePath, data)
    return response.data.data
  }

  /**
   * Update process
   */
  async updateProcess (id: number, data: UpdateProcessDTO): Promise<Process> {
    const response = await api.put<{ data: Process }>(`${this.basePath}/${id}`, data)
    return response.data.data
  }

  /**
   * Delete process
   */
  async deleteProcess (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Create process map
   */
  async createProcessMap (): Promise<ProcessMapResponse> {
    const response = await api.get<{ data: ProcessMapResponse }>(`${this.basePath}/map`)
    return response.data.data
  }

  /**
   * Update process interactions
   */
  async updateInteractions (id: number, data: UpdateInteractionsDTO): Promise<Process> {
    const response = await api.post<{ data: Process }>(
      `${this.basePath}/${id}/interactions`,
      data,
    )
    return response.data.data
  }

  /**
   * Archive process
   */
  async archiveProcess (id: number): Promise<Process> {
    const response = await api.post<{ data: Process }>(`${this.basePath}/${id}/archive`)
    return response.data.data
  }
}

export const processesService = new ProcessesService()
