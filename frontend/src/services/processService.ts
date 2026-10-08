import api from '@/api/client'

// Types
export interface Process {
  id: number
  code: string
  site_id?: number | null
  title: string
  category: 'pilotage' | 'support' | 'operationnel' | 'mesure_amelioration'
  pilot_id: number
  copilot_id?: number
  copilot_ids?: number[]
  purpose: string
  finalite?: string
  observation?: string
  acteurs?: string[]
  ressources?: string[]
  methodes?: string[]
  interfaces?: string[]
  aspect_qualite: boolean
  aspect_environnement: boolean
  aspect_sante_securite: boolean
  normes_iso?: string[]
  status: 'draft' | 'in_review' | 'validated' | 'active' | 'obsolete'
  version: string
  parent_process_id?: number
  level: number
  order: number
  pilot?: any
  copilot?: any
  indicators?: ProcessIndicator[]
  risks_opportunities?: ProcessRiskOpportunity[]
  sequences?: Array<ProcessSequence | Partial<ProcessSequence>>
  created_at?: string
  updated_at?: string
}

export interface ProcessIndicator {
  id: number
  process_id: number
  code: string
  name: string
  type: 'efficacite' | 'efficience' | 'conformite' | 'performance'
  category: 'qualite' | 'environnement' | 'sante_securite' | 'global'
  unit: string
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  status: 'ok' | 'warning' | 'critical' | 'unknown'
  values?: ProcessIndicatorValue[]
}

export interface ProcessIndicatorValue {
  id: number
  indicator_id: number
  measurement_date: string
  value: number
  comment?: string
  status: 'ok' | 'warning' | 'critical'
}

export interface ProcessRiskOpportunity {
  id: number
  process_id: number
  type: 'risque' | 'opportunite'
  code: string
  title: string
  description: string
  probabilite: number
  gravite: number
  criticite: number
  niveau: 'faible' | 'moyen' | 'eleve' | 'critique'
  status: string
}

export interface ProcessFilters {
  site_id?: number
  category?: string
  status?: string
  level?: number
  aspect?: string
  search?: string
}

export interface ProcessSequence {
  id: number
  process_id: number
  sequence_order: number
  input_description: string
  activity_description: string
  output_description: string
  responsible_user_id?: number
  duration_minutes?: number
  created_by?: number
  updated_by?: number
  responsible?: any
  documents?: any[]
}

export interface ProcessVersion {
  id: number
  process_id: number
  version_number: string
  changes_description: string
  status: 'draft' | 'verified' | 'approved'
  author_user_id: number
  verifier_user_id?: number
  approver_user_id?: number
  verified_at?: string
  approved_at?: string
  version_date: string
  is_current: boolean
  author?: any
  verifier?: any
  approver?: any
}

export interface ProcessObjective {
  id: number
  process_id: number
  indicator_id: number
  indicator_name?: string
  title: string
  strategic_axis?: string
  strategic_axes?: string[]
  description?: string
  target_value?: number
  target_date?: string
  status: 'not_started' | 'in_progress' | 'achieved' | 'failed'
  achievement_percentage: number
  indicator?: any
}

export interface ProcessReviewDraft {
  id: number
  process_id: number
  status: 'planned' | 'in_progress' | 'completed' | 'cancelled'
  identification: Record<string, any>
  sections: Record<string, any>
  metrics_snapshot: Record<string, any>
  started_at?: string | null
  ended_at?: string | null
  updated_at?: string
}

export interface ProcessReviewLinkedDuerpItem {
  id: number
  process_id: number | null
  process_title: string
  danger_type: string
  danger_description: string
  criticality_score: number
  criticality_level: string
  overdue_actions: number
  linked_actions_count?: number
}

export interface ProcessReviewLinkedAesItem {
  id: number
  process_id: number | null
  process_title: string
  designation: string
  type: string
  criticite: number
  aspect_significatif: boolean
  action_status: string
  linked_actions_count?: number
}

export interface ProcessReviewLinkedIncidentItem {
  id: number
  title: string
  category: string
  severity: string
  status: string
  due_date?: string | null
  description?: string
  is_overdue: boolean
  linked_actions_count?: number
}

export interface ProcessReviewLinkedData {
  duerp_top: ProcessReviewLinkedDuerpItem[]
  aes_top: ProcessReviewLinkedAesItem[]
  incidents_top: ProcessReviewLinkedIncidentItem[]
  summary: {
    duerp_overdue_actions: number
    aes_missing_actions: number
    incidents_open_count: number
    incidents_overdue_count: number
    linked_incident_actions_overdue_count: number
    alerts: Array<{
      code: string
      severity: string
      message: string
    }>
  }
}

export interface CreateLinkedReviewActionPayload {
  source_type: 'duerp_danger' | 'aes_aspect' | 'reclamation'
  source_id: number
  title: string
  description?: string
  responsible_id?: number | null
  deadline: string
  type?: 'corrective' | 'preventive' | 'improvement' | 'emergency' | 'curative'
  priority?: 'low' | 'medium' | 'high' | 'critical'
}

// API calls
export default {
  // GET /processes
  async getProcesses (filters: ProcessFilters = {}, page = 1, perPage = 20) {
    const params = new URLSearchParams()
    for (const [key, value] of Object.entries(filters)) {
      if (value) {
        params.append(key, value.toString())
      }
    }
    params.append('page', page.toString())
    params.append('per_page', perPage.toString())

    const response = await api.get(`/processes?${params.toString()}`)
    const extractRelationshipArray = (value: any) => {
      if (Array.isArray(value)) {
        return value
      }
      if (Array.isArray(value?.data)) {
        return value.data
      }
      return []
    }
    const normalizeList = (value: any) => {
      const list = extractRelationshipArray(value)
      return list.map((entry: any) => entry?.attributes ? { id: entry.id, ...entry.attributes } : entry)
    }

    // Transform JSON:API format to simple format
    const normalizeRelationshipArray = (value: any) => {
      if (Array.isArray(value)) {
        return value
      }
      if (Array.isArray(value?.data)) {
        return value.data
      }
      return []
    }

    const transformed = {
      data: response.data.data?.map((item: any) => ({
        id: item.id,
        code: item.attributes.ref,
        ...item.attributes,
        objectives: normalizeList(item.relationships?.objectives),
        indicators: normalizeList(item.relationships?.indicators),
        // Extract relationships
        site: item.relationships?.site
          ? {
              id: item.relationships.site.id,
              ...item.relationships.site.attributes,
            }
          : null,
        pilot: item.relationships?.pilot
          ? {
              id: item.relationships.pilot.id,
              ...item.relationships.pilot.attributes,
            }
          : null,
        copilot: item.relationships?.copilot
          ? {
              id: item.relationships.copilot.id,
              ...item.relationships.copilot.attributes,
            }
          : null,
      })) || [],
      meta: response.data.meta || {},
    }

    return transformed
  },

  // GET /processes/:id
  async getProcess (id: number) {
    const response = await api.get(`/processes/${id}?include=sequences,objectives,indicators,risks_opportunities`)

    // Transform JSON:API format to simple format
    const item = response.data.data
    if (!item) {
      return response.data
    }

    const relationships = item.relationships || {}
    const extractRelationshipArray = (value: any) => {
      if (Array.isArray(value)) {
        return value
      }
      if (Array.isArray(value?.data)) {
        return value.data
      }
      return []
    }
    const normalizeList = (value: any) => {
      const list = extractRelationshipArray(value)
      return list.map((entry: any) => entry?.attributes ? { id: entry.id, ...entry.attributes } : entry)
    }

    const attrs = item.attributes || item || {}
    const transformed = {
      data: {
        id: item.id,
        code: attrs.ref || attrs.code || item.code || '',
        ...attrs,
        // Extract relationships
        site: relationships?.site
          ? {
              id: relationships.site.id,
              name: relationships.site.attributes?.name,
            }
          : null,
        pilot: relationships?.pilot
          ? {
              id: relationships.pilot.id,
              name: relationships.pilot.attributes?.name,
            }
          : null,
        copilot: relationships?.copilot
          ? {
              id: relationships.copilot.id,
              name: relationships.copilot.attributes?.name,
            }
          : null,
        // Process-specific relationships
        sequences: normalizeList(relationships?.sequences),
        objectives: normalizeList(relationships?.objectives),
        versions: normalizeList(relationships?.versions),
        indicators: normalizeList(relationships?.indicators),
        risks_opportunities: normalizeList(relationships?.risks_opportunities),
        current_version: relationships?.current_version?.attributes
          ? { id: relationships.current_version.id, ...relationships.current_version.attributes }
          : relationships?.current_version || null,
      },
    }

    return transformed
  },

  // POST /processes
  async createProcess (data: Partial<Process>) {
    const response = await api.post('/processes', data)
    return response.data
  },

  // PATCH /processes/:id
  async updateProcess (id: number, data: Partial<Process>) {
    const response = await api.patch(`/processes/${id}`, data)
    return response.data
  },

  // DELETE /processes/:id
  async deleteProcess (id: number) {
    await api.delete(`/processes/${id}`)
  },

  // POST /processes/:id/indicators
  async addIndicator (processId: number, data: Partial<ProcessIndicator>) {
    const response = await api.post(`/processes/${processId}/indicators`, data)
    return response.data
  },

  // POST /indicators/:id/values
  async addIndicatorValue (indicatorId: number, data: Partial<ProcessIndicatorValue>) {
    const response = await api.post(`/indicators/${indicatorId}/values`, data)
    return response.data
  },

  // POST /processes/:id/risks-opportunities
  async addRiskOpportunity (processId: number, data: Partial<ProcessRiskOpportunity>) {
    const response = await api.post(`/processes/${processId}/risks-opportunities`, data)
    return response.data
  },

  // GET /processes-cartography
  async getCartography (siteId?: number | null) {
    const params = siteId ? `?site_id=${siteId}` : ''
    const response = await api.get(`/processes-cartography${params}`)
    return response.data
  },

  // GET /processes-cartography/export-pdf
  async exportCartographyPdfBlob (siteId?: number | null, preview = true): Promise<Blob> {
    const query = new URLSearchParams()
    if (siteId) query.set('site_id', String(siteId))
    if (preview) query.set('preview', '1')
    const qs = query.toString() ? `?${query.toString()}` : ''
    const response = await api.get(`/processes-cartography/export-pdf${qs}`, {
      responseType: 'blob',
    })
    return response.data
  },

  // GET /processes-cartography/export-docx
  async exportCartographyDocxBlob (siteId?: number | null): Promise<Blob> {
    const query = new URLSearchParams()
    if (siteId) query.set('site_id', String(siteId))
    const qs = query.toString() ? `?${query.toString()}` : ''
    const response = await api.get(`/processes-cartography/export-docx${qs}`, {
      responseType: 'blob',
    })
    return response.data
  },

  // GET /processes/:id/export-docx
  async exportProcessDocxBlob (processId: number): Promise<{ blob: Blob, documentId?: number }> {
    const response = await api.get(`/processes/${processId}/export-docx`, {
      responseType: 'blob',
    })
    const documentId = Number(response.headers?.['x-generated-document-id'] || 0)
    return {
      blob: new Blob([response.data], {
        type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      }),
      documentId: documentId > 0 ? documentId : undefined,
    }
  },

  // GET /processes/:id/export-pdf
  async exportProcessPdfBlob (processId: number, preview = true): Promise<Blob> {
    const query = preview ? '?preview=1' : ''
    const response = await api.get(`/processes/${processId}/export-pdf${query}`, {
      responseType: 'blob',
    })
    return new Blob([response.data], { type: 'application/pdf' })
  },

  async exportProcessDocx (processId: number) {
    return this.exportProcessDocxBlob(processId)
  },

  async exportProcessPdf (processId: number, preview = true) {
    return this.exportProcessPdfBlob(processId, preview)
  },

  // GET /system-settings
  async getSystemSettings (siteId?: number) {
    const params = siteId ? `?site_id=${siteId}` : ''
    const response = await api.get(`/system-settings${params}`)
    return response.data
  },

  async getCurrentReview (processId: number) {
    const response = await api.get(`/processes/${processId}/reviews/current`)
    return response.data
  },

  async saveCurrentReview (
    processId: number,
    payload: {
      identification?: Record<string, any>
      sections?: Record<string, any>
      metrics_snapshot?: Record<string, any>
      status?: 'planned' | 'in_progress'
    },
  ) {
    const response = await api.put(`/processes/${processId}/reviews/current`, payload)
    return response.data
  },

  async closeCurrentReview (processId: number) {
    const response = await api.post(`/processes/${processId}/reviews/current/close`)
    return response.data
  },

  async getCurrentReviewLinkedData (processId: number): Promise<ProcessReviewLinkedData> {
    const response = await api.get(`/processes/${processId}/reviews/current/linked-data`)
    const payload = response?.data?.data || {}

    return {
      duerp_top: Array.isArray(payload.duerp_top) ? payload.duerp_top : [],
      aes_top: Array.isArray(payload.aes_top) ? payload.aes_top : [],
      incidents_top: Array.isArray(payload.incidents_top) ? payload.incidents_top : [],
      summary: {
        duerp_overdue_actions: Number(payload?.summary?.duerp_overdue_actions || 0),
        aes_missing_actions: Number(payload?.summary?.aes_missing_actions || 0),
        incidents_open_count: Number(payload?.summary?.incidents_open_count || 0),
        incidents_overdue_count: Number(payload?.summary?.incidents_overdue_count || 0),
        linked_incident_actions_overdue_count: Number(payload?.summary?.linked_incident_actions_overdue_count || 0),
        alerts: Array.isArray(payload?.summary?.alerts) ? payload.summary.alerts : [],
      },
    }
  },

  async createCurrentReviewLinkedAction (processId: number, payload: CreateLinkedReviewActionPayload) {
    const response = await api.post(`/processes/${processId}/reviews/current/linked-data/actions`, payload)
    return response.data
  },

  async exportCurrentReviewPdf (processId: number) {
    const response = await api.get(`/processes/${processId}/reviews/current/export-pdf`, {
      responseType: 'blob',
    })
    return response
  },

  async exportCurrentReviewDocx (processId: number) {
    const response = await api.get(`/processes/${processId}/reviews/current/export-docx`, {
      responseType: 'blob',
    })
    return response
  },


  // === SEQUENCES ===
  // POST /processes/:id/sequences
  async addSequence (processId: number, data: Partial<ProcessSequence>) {
    const response = await api.post(`/processes/${processId}/sequences`, data)
    return response.data
  },

  // PUT /processes/:id/sequences/:seqId
  async updateSequence (processId: number, sequenceId: number, data: Partial<ProcessSequence>) {
    const response = await api.put(`/processes/${processId}/sequences/${sequenceId}`, data)
    return response.data
  },

  // DELETE /processes/:id/sequences/:seqId
  async deleteSequence (processId: number, sequenceId: number) {
    await api.delete(`/processes/${processId}/sequences/${sequenceId}`)
  },

  // === VERSIONS ===
  // GET /processes/:id/versions
  async getVersions (processId: number) {
    const response = await api.get(`/processes/${processId}/versions`)
    return response.data
  },

  // POST /processes/:id/versions
  async createVersion (processId: number, data: { changes_description: string }) {
    const response = await api.post(`/processes/${processId}/versions`, data)
    return response.data
  },

  // POST /processes/:id/versions/:vId/verify
  async verifyVersion (processId: number, versionId: number) {
    const response = await api.post(`/processes/${processId}/versions/${versionId}/verify`)
    return response.data
  },

  // POST /processes/:id/versions/:vId/approve
  async approveVersion (processId: number, versionId: number) {
    const response = await api.post(`/processes/${processId}/versions/${versionId}/approve`)
    return response.data
  },

  // === OBJECTIVES ===
  // POST /processes/:id/objectives
  async addObjective (processId: number, data: Partial<ProcessObjective>) {
    const response = await api.post(`/processes/${processId}/objectives`, data)
    return response.data
  },

  // PUT /processes/:id/objectives/:objId
  async updateObjective (processId: number, objectiveId: number, data: Partial<ProcessObjective>) {
    const response = await api.put(`/processes/${processId}/objectives/${objectiveId}`, data)
    return response.data
  },

  // DELETE /processes/:id/objectives/:objId
  async deleteObjective (processId: number, objectiveId: number) {
    await api.delete(`/processes/${processId}/objectives/${objectiveId}`)
  },

  // POST /processes-cartography/generate-draft
  async generateCartographyDraft (payload: any) {
    const response = await api.post('/processes-cartography/generate-draft', payload)
    return response.data
  },
}
