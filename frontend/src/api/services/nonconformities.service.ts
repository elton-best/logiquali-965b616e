import api from '@/api/client'

export interface NonConformity {
  id: number
  reference: string
  title: string
  description: string
  type: NCType
  source: NCSource
  site_id: number
  process_id?: number | null
  site?: { id: number, name: string }
  process?: { id: number, title?: string, name?: string }
  detected_by?: string
  detected_by_id?: number | null
  detected_date: string
  status: NCStatus
  severity: number
  root_cause?: string
  immediate_action?: string
  corrective_action?: string
  preventive_action?: string
  responsible_id?: number | null
  responsible?: {
    id: number
    name: string
    email?: string
  }
  due_date?: string | null
  closure_date?: string | null
  requirement_reference?: string | null
  finding_type?: 'non_conformity' | 'gap'
  detection_source?: DetectionSource | null
  result_summary?: string | null
  rq_signature_date?: string | null
  actions?: Array<{
    id: number
    type: 'corrective' | 'preventive' | 'improvement' | 'curative' | 'emergency' | string
    title?: string
    description?: string
    responsible_id?: number | null
    responsible?: { id: number, name: string }
    deadline?: string | null
  }>
  created_at: string
  updated_at: string
}

export type NCType = 'mineure' | 'majeure' | 'critique'
export type NCSource = 'audit' | 'reclamation' | 'interne'
export type NCStatus = 'open' | 'analysis' | 'corrective_action' | 'verification' | 'closed'
export type DetectionSource
  = | 'internal_audit'
    | 'external_audit'
    | 'customer_complaint'
    | 'internal_control'
    | 'management_review'
    | 'other'

export interface NCActionInput {
  type: 'corrective' | 'preventive' | 'improvement'
  description: string
  responsible_id: number
  deadline: string
  process_id?: number
  title?: string
}

export interface CreateNCDTO {
  title: string
  description: string
  type: NCType
  source: NCSource
  site_id: number
  process_id?: number
  detected_by?: string | number
  detected_date: string
  severity: number
  immediate_action?: string
  root_cause?: string
  corrective_action?: string
  preventive_action?: string
  responsible_id?: number
  due_date?: string
  requirement_reference?: string
  finding_type?: 'non_conformity' | 'gap'
  detection_source?: DetectionSource
  result_summary?: string
  rq_signature_date?: string
  actions?: NCActionInput[]
}

export interface UpdateNCDTO extends Partial<CreateNCDTO> {}

export interface NCListParams {
  page?: number
  per_page?: number
  search?: string
  type?: NCType
  source?: NCSource
  status?: NCStatus
  site_id?: number
  responsible_id?: number
}

export interface NCListResponse {
  data: NonConformity[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface UpdateStatusDTO {
  status: NCStatus
  comments?: string
}

export interface AssignResponsibleDTO {
  responsible_id: number
}

function mapSeverityToBackend (value: number): 'minor' | 'major' | 'critical' {
  if (value >= 5) {
    return 'critical'
  }
  if (value >= 4) {
    return 'major'
  }
  return 'minor'
}

function mapSeverityFromBackend (value?: string): number {
  if (value === 'critical') {
    return 5
  }
  if (value === 'major') {
    return 4
  }
  return 2
}

function mapTypeToBackend (_value: NCType): 'normative' {
  return 'normative'
}

function mapTypeFromBackend (value?: string, severity?: string): NCType {
  if (severity === 'critical') {
    return 'critique'
  }
  if (severity === 'major') {
    return 'majeure'
  }
  if (value === 'legal' || value === 'regulatory') {
    return 'majeure'
  }
  return 'mineure'
}

function mapSourceToBackend (value: NCSource): 'audit' | 'complaint' | 'internal' {
  if (value === 'audit') {
    return 'audit'
  }
  if (value === 'reclamation') {
    return 'complaint'
  }
  return 'internal'
}

function mapSourceFromBackend (value?: string): NCSource {
  if (value === 'audit') {
    return 'audit'
  }
  if (value === 'complaint') {
    return 'reclamation'
  }
  return 'interne'
}

function mapStatusFromBackend (item: any): NCStatus {
  const workflow = String(item?.workflow_state?.code || item?.workflowState?.code || '')
  const raw = String(item?.status || '').toLowerCase()

  if (workflow.includes('verification') || raw === 'verified') {
    return 'verification'
  }
  if (workflow === 'closed' || raw === 'closed') {
    return 'closed'
  }
  if (workflow.includes('analysis')) {
    return 'analysis'
  }
  if (workflow === 'validated' || workflow.includes('action') || raw === 'resolved') {
    return 'corrective_action'
  }
  if (raw === 'in_progress') {
    return 'analysis'
  }
  return 'open'
}

function mapRootCauseOut (value: unknown): string | undefined {
  if (!value) {
    return undefined
  }
  if (typeof value === 'string') {
    return value
  }
  if (Array.isArray(value)) {
    const lines = value
      .map(item => (typeof item === 'string' ? item.trim() : ''))
      .filter(Boolean)
    return lines.length > 0 ? lines.join('\n') : undefined
  }
  if (typeof value === 'object') {
    const cause = value as Record<string, unknown>
    const directKeys = ['summary', 'cause', 'text', 'description']
    for (const key of directKeys) {
      const content = cause[key]
      if (typeof content === 'string' && content.trim()) {
        return content.trim()
      }
    }

    const data = cause.data
    if (Array.isArray(data)) {
      const fromData = data
        .map(item => (typeof item === 'string' ? item.trim() : ''))
        .filter(Boolean)
      if (fromData.length > 0) {
        return fromData.join('\n')
      }
    }
  }
  return undefined
}

function mapNcFromBackend (item: any): NonConformity {
  const actions = Array.isArray(item?.actions)
    ? item.actions.map((action: any) => ({
        id: Number(action.id),
        type: action.type || 'corrective',
        title: action.title || undefined,
        description: action.description || undefined,
        responsible_id: action.responsible_id ?? action.responsible?.id ?? null,
        responsible: action.responsible
          ? { id: Number(action.responsible.id), name: action.responsible.name }
          : undefined,
        deadline: action.deadline || null,
      }))
    : undefined
  return {
    id: Number(item.id),
    reference: String(item.ref || item.reference || `NC-${item.id}`),
    title: String(item.title || 'Non-conformité'),
    description: String(item.description || ''),
    type: mapTypeFromBackend(item.type, item.severity),
    source: mapSourceFromBackend(item.source),
    site_id: Number(item.site_id || item.site?.id || 0),
    process_id: item.process_id ?? item.process?.id ?? null,
    site: item.site ? { id: Number(item.site.id), name: item.site.name } : undefined,
    process: item.process ? { id: Number(item.process.id), title: item.process.title, name: item.process.name } : undefined,
    detected_by: item.detectedByUser?.name || item.detected_by_user?.name || undefined,
    detected_by_id: item.detected_by ?? null,
    detected_date: String(item.detected_at || item.created_at || ''),
    status: mapStatusFromBackend(item),
    severity: mapSeverityFromBackend(item.severity),
    root_cause: mapRootCauseOut(item.root_cause_analysis),
    immediate_action: item.immediate_action || undefined,
    corrective_action: item.corrective_action || undefined,
    preventive_action: item.preventive_action || undefined,
    responsible_id: item.responsible_id ?? null,
    responsible: item.responsible
      ? { id: Number(item.responsible.id), name: item.responsible.name, email: item.responsible.email }
      : undefined,
    due_date: item.deadline || null,
    closure_date: item.resolution_date || null,
    requirement_reference: item.requirement_reference || null,
    finding_type: item.finding_type || 'non_conformity',
    detection_source: item.detection_source || null,
    result_summary: item.result_summary || null,
    rq_signature_date: item.rq_signature_date || null,
    actions,
    created_at: String(item.created_at || ''),
    updated_at: String(item.updated_at || ''),
  }
}

function mapNcCreateToBackend (data: CreateNCDTO): Record<string, unknown> {
  return {
    title: data.title,
    description: data.description,
    type: mapTypeToBackend(data.type),
    source: mapSourceToBackend(data.source),
    site_id: data.site_id,
    process_id: data.process_id,
    detected_by: typeof data.detected_by === 'number' ? data.detected_by : undefined,
    detected_at: data.detected_date,
    severity: mapSeverityToBackend(data.severity),
    root_cause_analysis: data.root_cause ? { summary: data.root_cause } : undefined,
    corrective_action: data.corrective_action,
    preventive_action: data.preventive_action,
    responsible_id: data.responsible_id,
    deadline: data.due_date || undefined,
    requirement_reference: data.requirement_reference,
    finding_type: data.finding_type,
    detection_source: data.detection_source,
    result_summary: data.result_summary,
    rq_signature_date: data.rq_signature_date || undefined,
    actions: data.actions?.map(action => ({
      type: action.type,
      description: action.description,
      responsible_id: action.responsible_id,
      deadline: action.deadline,
      process_id: action.process_id,
      title: action.title,
    })),
  }
}

function mapNcUpdateToBackend (data: UpdateNCDTO): Record<string, unknown> {
  const mapped: Record<string, unknown> = {}
  if (data.title !== undefined) {
    mapped.title = data.title
  }
  if (data.description !== undefined) {
    mapped.description = data.description
  }
  if (data.type !== undefined) {
    mapped.type = mapTypeToBackend(data.type)
  }
  if (data.source !== undefined) {
    mapped.source = mapSourceToBackend(data.source)
  }
  if (data.site_id !== undefined) {
    mapped.site_id = data.site_id
  }
  if (data.process_id !== undefined) {
    mapped.process_id = data.process_id
  }
  if (data.detected_date !== undefined) {
    mapped.detected_at = data.detected_date
  }
  if (data.severity !== undefined) {
    mapped.severity = mapSeverityToBackend(data.severity)
  }
  if (data.root_cause !== undefined) {
    mapped.root_cause_analysis = data.root_cause ? { summary: data.root_cause } : null
  }
  if (data.corrective_action !== undefined) {
    mapped.corrective_action = data.corrective_action
  }
  if (data.preventive_action !== undefined) {
    mapped.preventive_action = data.preventive_action
  }
  if (data.responsible_id !== undefined) {
    mapped.responsible_id = data.responsible_id
  }
  if (data.due_date !== undefined) {
    mapped.deadline = data.due_date || null
  }
  if (data.requirement_reference !== undefined) {
    mapped.requirement_reference = data.requirement_reference
  }
  if (data.finding_type !== undefined) {
    mapped.finding_type = data.finding_type
  }
  if (data.detection_source !== undefined) {
    mapped.detection_source = data.detection_source
  }
  if (data.result_summary !== undefined) {
    mapped.result_summary = data.result_summary
  }
  if (data.rq_signature_date !== undefined) {
    mapped.rq_signature_date = data.rq_signature_date || null
  }
  if (data.actions !== undefined) {
    mapped.actions = data.actions?.map(action => ({
      type: action.type,
      description: action.description,
      responsible_id: action.responsible_id,
      deadline: action.deadline,
      process_id: action.process_id,
      title: action.title,
    }))
  }
  return mapped
}

class NonConformitiesService {
  private readonly basePath = '/non-conformities'

  async getNonConformities (params: NCListParams = {}): Promise<NCListResponse> {
    const response = await api.get<any>(this.basePath, { params })
    const payload = response.data || response

    return {
      data: Array.isArray(payload.data) ? payload.data.map(mapNcFromBackend) : [],
      meta: {
        current_page: Number(payload.current_page || 1),
        last_page: Number(payload.last_page || 1),
        per_page: Number(payload.per_page || params.per_page || 15),
        total: Number(payload.total || 0),
      },
    }
  }

  async getNonConformity (id: number): Promise<NonConformity> {
    const response = await api.get<any>(`${this.basePath}/${id}`)
    const payload = response.data || response
    return mapNcFromBackend(payload)
  }

  async createNonConformity (data: CreateNCDTO): Promise<NonConformity> {
    const response = await api.post<any>(this.basePath, mapNcCreateToBackend(data))
    const payload = response.data || response
    return mapNcFromBackend(payload)
  }

  async updateNonConformity (id: number, data: UpdateNCDTO): Promise<NonConformity> {
    const response = await api.put<any>(`${this.basePath}/${id}`, mapNcUpdateToBackend(data))
    const payload = response.data || response
    return mapNcFromBackend(payload)
  }

  async deleteNonConformity (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  async updateStatus (id: number, data: UpdateStatusDTO): Promise<NonConformity> {
    const response = await api.post<any>(`${this.basePath}/${id}/status`, data)
    const payload = response.data || response
    return mapNcFromBackend(payload)
  }

  async assignResponsible (id: number, data: AssignResponsibleDTO): Promise<NonConformity> {
    const response = await api.post<any>(`${this.basePath}/${id}/assign`, data)
    const payload = response.data || response
    return mapNcFromBackend(payload)
  }
}

export const nonConformitiesService = new NonConformitiesService()
