import api from '@/api/client'

export interface Action {
  id: number
  reference: string
  title: string
  description: string
  type: ActionType
  source_type?: SourceType
  source_id?: number
  category: ActionCategory
  priority: ActionPriority
  responsible_id?: number
  responsible?: {
    id: number
    name: string
    email?: string
  }
  site_id: number
  process_id: number
  process?: {
    id: number
    title?: string
    code?: string
  }
  site?: {
    id: number
    name: string
  }
  due_date?: string
  completion_date?: string
  status: ActionStatus
  progress: number
  progress_notes?: Array<{
    date: string
    note: string
    user_id?: number
    user_name?: string
  }>
  resources_needed?: string
  estimated_cost?: number
  actual_cost?: number
  effectiveness_criteria?: string
  effectiveness_rating?: number
  created_at: string
  updated_at: string
}

export type ActionType = 'corrective' | 'preventive'
export type SourceType = 'nc' | 'audit' | 'risk'
export type ActionCategory = 'qualite' | 'hygiene' | 'securite' | 'environnement'
export type ActionPriority = 'low' | 'medium' | 'high' | 'urgent'
export type ActionStatus
  = | 'draft'
    | 'assigned'
    | 'in_progress'
    | 'completed'
    | 'verified'
    | 'closed'
    | 'cancelled'

export interface CreateActionDTO {
  title: string
  description: string
  type: ActionType
  source_type?: SourceType
  source_id?: number
  category: ActionCategory
  priority: ActionPriority
  responsible_id?: number
  site_id: number
  process_id: number
  due_date?: string
  resources_needed?: string
  estimated_cost?: number
  effectiveness_criteria?: string
}

export interface UpdateActionDTO extends Partial<CreateActionDTO> {
  completion_date?: string
  actual_cost?: number
  effectiveness_rating?: number
}

export interface ActionListParams {
  page?: number
  per_page?: number
  search?: string
  type?: ActionType
  source_type?: SourceType
  category?: ActionCategory
  priority?: ActionPriority
  status?: ActionStatus
  site_id?: number
  process_id?: number
  responsible_id?: number
}

export interface ActionListResponse {
  data: Action[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface UpdateProgressDTO {
  progress: number
  comments?: string
}

export interface UpdateStatusDTO {
  status: ActionStatus
  comments?: string
}

export interface VerifyEffectivenessDTO {
  effectiveness_rating: number
  comments?: string
}

function mapPriorityToBackend (value: ActionPriority): 'low' | 'medium' | 'high' | 'critical' {
  if (value === 'urgent') {
    return 'critical'
  }
  return value
}

function mapPriorityFromBackend (value?: string): ActionPriority {
  if (value === 'critical') {
    return 'urgent'
  }
  if (value === 'high') {
    return 'high'
  }
  if (value === 'low') {
    return 'low'
  }
  return 'medium'
}

function mapStatusFromBackend (value?: string): ActionStatus {
  const status = String(value || '').toLowerCase()
  if (status === 'in_progress') {
    return 'in_progress'
  }
  if (status === 'completed') {
    return 'completed'
  }
  if (status === 'verified') {
    return 'verified'
  }
  if (status === 'cancelled') {
    return 'cancelled'
  }
  return 'draft'
}

function mapSourceTypeToBackend (value?: SourceType): string | undefined {
  if (value === 'nc') {
    return 'non_conformity'
  }
  if (value === 'audit') {
    return 'audit'
  }
  if (value === 'risk') {
    return 'risk'
  }
  return undefined
}

function mapSourceTypeFromBackend (value?: string): SourceType | undefined {
  if (value === 'non_conformity' || value === 'nc') {
    return 'nc'
  }
  if (value === 'audit') {
    return 'audit'
  }
  if (value === 'risk') {
    return 'risk'
  }
  return undefined
}

function mapCategoryFromType (type: ActionType): ActionCategory {
  if (type === 'preventive') {
    return 'securite'
  }
  return 'qualite'
}

function mapActionFromBackend (item: any): Action {
  const type = (item.type === 'preventive' ? 'preventive' : 'corrective') as ActionType
  return {
    id: Number(item.id),
    reference: String(item.ref || item.reference || `ACT-${item.id}`),
    title: String(item.title || ''),
    description: String(item.description || ''),
    type,
    source_type: mapSourceTypeFromBackend(item.source_type || item.source),
    source_id: item.source_id ? Number(item.source_id) : undefined,
    category: mapCategoryFromType(type),
    priority: mapPriorityFromBackend(item.priority),
    responsible_id: item.responsible_id ? Number(item.responsible_id) : undefined,
    responsible: item.responsible
      ? { id: Number(item.responsible.id), name: item.responsible.name, email: item.responsible.email }
      : undefined,
    site_id: Number(item.site_id || item.site?.id || 0),
    process_id: Number(item.process_id || item.process?.id || 0),
    process: item.process
      ? {
          id: Number(item.process.id),
          title: item.process.title ? String(item.process.title) : undefined,
          code: item.process.code ? String(item.process.code) : undefined,
        }
      : undefined,
    site: item.site ? { id: Number(item.site.id), name: item.site.name } : undefined,
    due_date: item.deadline || undefined,
    completion_date: item.completion_date || undefined,
    status: mapStatusFromBackend(item.status),
    progress: Number(item.progress ?? item.progress_percentage ?? 0),
    progress_notes: Array.isArray(item.progress_notes)
      ? item.progress_notes.map((entry: any) => ({
          date: String(entry?.date || ''),
          note: String(entry?.note || ''),
          user_id: entry?.user_id ? Number(entry.user_id) : undefined,
          user_name: entry?.user_name ? String(entry.user_name) : undefined,
        }))
      : [],
    resources_needed: item.required_resources || undefined,
    estimated_cost: item.estimated_cost ? Number(item.estimated_cost) : undefined,
    actual_cost: item.actual_cost ? Number(item.actual_cost) : undefined,
    effectiveness_criteria: item.effectiveness_comments || undefined,
    effectiveness_rating: item.effectiveness_verified ? 5 : undefined,
    created_at: String(item.created_at || ''),
    updated_at: String(item.updated_at || ''),
  }
}

function mapCreateToBackend (data: CreateActionDTO): Record<string, unknown> {
  return {
    title: data.title,
    description: data.description,
    type: data.type,
    source_type: mapSourceTypeToBackend(data.source_type),
    source_id: data.source_id,
    priority: mapPriorityToBackend(data.priority),
    responsible_id: data.responsible_id,
    site_id: data.site_id,
    process_id: data.process_id,
    deadline: data.due_date || undefined,
    required_resources: data.resources_needed,
    estimated_cost: data.estimated_cost,
    source: mapSourceTypeToBackend(data.source_type),
  }
}

function mapUpdateToBackend (data: UpdateActionDTO): Record<string, unknown> {
  const mapped: Record<string, unknown> = {}
  if (data.title !== undefined) {
    mapped.title = data.title
  }
  if (data.description !== undefined) {
    mapped.description = data.description
  }
  if (data.type !== undefined) {
    mapped.type = data.type
  }
  if (data.source_type !== undefined) {
    mapped.source_type = mapSourceTypeToBackend(data.source_type)
  }
  if (data.source_id !== undefined) {
    mapped.source_id = data.source_id
  }
  if (data.priority !== undefined) {
    mapped.priority = mapPriorityToBackend(data.priority)
  }
  if (data.responsible_id !== undefined) {
    mapped.responsible_id = data.responsible_id
  }
  if (data.site_id !== undefined) {
    mapped.site_id = data.site_id
  }
  if (data.process_id !== undefined) {
    mapped.process_id = data.process_id
  }
  if (data.due_date !== undefined) {
    mapped.deadline = data.due_date || null
  }
  if (data.completion_date !== undefined) {
    mapped.completion_date = data.completion_date
  }
  if (data.resources_needed !== undefined) {
    mapped.required_resources = data.resources_needed
  }
  if (data.estimated_cost !== undefined) {
    mapped.estimated_cost = data.estimated_cost
  }
  if (data.actual_cost !== undefined) {
    mapped.actual_cost = data.actual_cost
  }
  return mapped
}

class ActionsService {
  private readonly basePath = '/actions'

  async getActions (params: ActionListParams = {}): Promise<ActionListResponse> {
    const response = await api.get<any>(this.basePath, { params })
    const payload = response.data || response
    return {
      data: Array.isArray(payload.data) ? payload.data.map(mapActionFromBackend) : [],
      meta: {
        current_page: Number(payload.current_page || 1),
        last_page: Number(payload.last_page || 1),
        per_page: Number(payload.per_page || params.per_page || 15),
        total: Number(payload.total || 0),
      },
    }
  }

  async getAction (id: number): Promise<Action> {
    const response = await api.get<any>(`${this.basePath}/${id}`)
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }

  async createAction (data: CreateActionDTO): Promise<Action> {
    const response = await api.post<any>(this.basePath, mapCreateToBackend(data))
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }

  async updateAction (id: number, data: UpdateActionDTO): Promise<Action> {
    const response = await api.put<any>(`${this.basePath}/${id}`, mapUpdateToBackend(data))
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }

  async deleteAction (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  async updateProgress (id: number, data: UpdateProgressDTO): Promise<Action> {
    const response = await api.post<any>(`${this.basePath}/${id}/progress`, {
      progress: data.progress,
      notes: data.comments,
    })
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }

  async updateStatus (id: number, data: UpdateStatusDTO): Promise<Action> {
    const response = await api.post<any>(`${this.basePath}/${id}/status`, data)
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }

  async verifyEffectiveness (id: number, data: VerifyEffectivenessDTO): Promise<Action> {
    const response = await api.post<any>(`${this.basePath}/${id}/verify`, {
      is_effective: data.effectiveness_rating >= 3,
      verification_notes: data.comments,
    })
    const payload = response.data || response
    return mapActionFromBackend(payload)
  }
}

export const actionsService = new ActionsService()
