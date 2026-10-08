import api from '@/api/client'

export interface EvaluationRequest {
  id: number
  enterprise_id: number
  form_type:
    | 'satisfaction_client'
    | 'satisfaction_personnel'
    | 'performance_personnel'
    | 'evaluation_personnel'
    | 'evaluation_auditeur'
    | 'satisfaction_fournisseur'
    | 'performance_fournisseur'
    | 'evaluation_fournisseur'
    | 'audit_interne'
    | 'custom'
  title: string
  token: string
  status: 'draft' | 'pending' | 'sent' | 'opened' | 'completed' | 'expired' | 'cancelled'
  recipient_email: string | null
  recipient_name: string | null
  related_entity_type: string | null
  related_entity_id: number | null
  criteria_ids: number[]
  custom_message: string | null
  instructions: string | null
  expires_at: string | null
  sent_at: string | null
  opened_at: string | null
  completed_at: string | null
  reminder_count: number
  last_reminder_at: string | null
  created_by: number
  public_url: string
  created_at: string
  updated_at: string
  responses?: EvaluationResponse[]
  creator?: { id: number, name: string }
}

export interface EvaluationResponse {
  id: number
  evaluation_request_id: number
  respondent_name: string | null
  respondent_email: string | null
  scores: Record<number, number>
  comments: Record<number, string> | null
  global_comment: string | null
  overall_score: number | null
  metadata: Record<string, any> | null
  score_rows?: Array<{
    criterion_id: number
    criterion_name: string
    score: number | null
    comment?: string | null
  }>
  submitted_at: string
  created_at: string
  updated_at: string
}

export interface CreateEvaluationRequestPayload {
  form_type: EvaluationRequest['form_type']
  title: string
  recipient_email?: string
  recipient_name?: string
  site_id?: number
  related_entity_type?: string
  related_entity_id?: number
  criteria_ids: number[]
  custom_message?: string
  instructions?: string
  expires_at?: string
}

export interface PublicFormData {
  request: EvaluationRequest
  criteria: Array<{
    id: number
    name: string
    description: string | null
    category: string | null
    scale_type: string
    scale_min: number
    scale_max: number
    scale_labels: Record<string, string> | null
    display_order: number
  }>
}

export interface SubmitResponsePayload {
  scores: Record<number, number>
  comments?: Record<number, string>
  global_comment?: string
}

export interface EvaluationStatistics {
  total_requests: number
  by_status: Record<string, number>
  by_form_type: Record<string, number>
  response_rate: number
  average_response_time_hours: number | null
  average_score: number | null
}

interface BackendWrapped<T> {
  success?: boolean
  message?: string
  data?: T
  meta?: any
  public_url?: string
}

interface BackendEvaluationRequest {
  id: number
  enterprise_id: number
  type: EvaluationRequest['form_type']
  subject: string
  token: string
  status: EvaluationRequest['status']
  recipient_email: string
  recipient_name: string | null
  requestable_type: string | null
  requestable_id: number | null
  message: string | null
  expires_at: string | null
  sent_at: string | null
  opened_at: string | null
  responded_at: string | null
  reminder_count: number
  last_reminder_at: string | null
  created_by: number
  created_at: string
  updated_at: string
  metadata?: Record<string, any> | null
  criteria?: Array<{ id: number }>
  response?: {
    id: number
    responses?: Record<string, { score?: number, comment?: string }>
    general_comment?: string | null
    percentage?: number | null
    created_at?: string
    updated_at?: string
  } | null
}

function getWrappedData<T> (payload: BackendWrapped<T> | T): T {
  if (payload && typeof payload === 'object' && 'data' in (payload as any)) {
    return (payload as BackendWrapped<T>).data as T
  }

  return payload as T
}

function mapRequest (raw: BackendEvaluationRequest, fallback?: Partial<EvaluationRequest>): EvaluationRequest {
  const criteriaIds = Array.isArray(raw.criteria)
    ? raw.criteria.map(item => Number(item.id)).filter(value => Number.isFinite(value))
    : (fallback?.criteria_ids || [])

  const instructions = typeof raw.metadata?.instructions === 'string'
    ? raw.metadata.instructions
    : (fallback?.instructions || null)

  const publicUrl = fallback?.public_url || `/evaluation/${raw.token}`

  return {
    id: Number(raw.id),
    enterprise_id: Number(raw.enterprise_id),
    form_type: raw.type,
    title: raw.subject,
    token: raw.token,
    status: raw.status,
    recipient_email: raw.recipient_email || null,
    recipient_name: raw.recipient_name,
    related_entity_type: raw.requestable_type,
    related_entity_id: raw.requestable_id,
    criteria_ids: criteriaIds,
    custom_message: raw.message,
    instructions,
    expires_at: raw.expires_at,
    sent_at: raw.sent_at,
    opened_at: raw.opened_at,
    completed_at: raw.responded_at,
    reminder_count: Number(raw.reminder_count || 0),
    last_reminder_at: raw.last_reminder_at,
    created_by: Number(raw.created_by || 0),
    public_url: publicUrl,
    created_at: raw.created_at,
    updated_at: raw.updated_at,
  }
}

function mapCreatePayload (payload: CreateEvaluationRequestPayload) {
  return {
    type: payload.form_type,
    subject: payload.title,
    recipient_email: payload.recipient_email || undefined,
    recipient_name: payload.recipient_name,
    site_id: payload.site_id,
    requestable_type: payload.related_entity_type,
    requestable_id: payload.related_entity_id,
    criteria_ids: payload.criteria_ids,
    message: payload.custom_message,
    expires_at: payload.expires_at,
    metadata: payload.instructions ? { instructions: payload.instructions } : undefined,
  }
}

function mapResponse (raw: any): EvaluationResponse {
  return {
    id: Number(raw.id),
    evaluation_request_id: Number(raw.evaluation_request_id),
    respondent_name: raw.respondent_name || null,
    respondent_email: raw.respondent_email || null,
    scores: raw.scores || {},
    comments: raw.comments || null,
    global_comment: raw.global_comment || null,
    overall_score: raw.overall_score !== undefined && raw.overall_score !== null ? Number(raw.overall_score) : null,
    metadata: raw.metadata || null,
    score_rows: Array.isArray(raw.score_rows)
      ? raw.score_rows.map((row: any) => ({
          criterion_id: Number(row.criterion_id),
          criterion_name: String(row.criterion_name || `Critère ${row.criterion_id || ''}`),
          score: row.score !== undefined && row.score !== null ? Number(row.score) : null,
          comment: row.comment || null,
        }))
      : [],
    submitted_at: raw.submitted_at || raw.created_at,
    created_at: raw.created_at,
    updated_at: raw.updated_at,
  }
}

const evaluationRequestsApi = {
  async getAll (params?: {
    form_type?: string
    status?: string
    per_page?: number
    page?: number
  }) {
    const backendParams = {
      ...params,
      type: params?.form_type,
    }

    const { data } = await api.get<BackendWrapped<BackendEvaluationRequest[]>>('/evaluation-requests', {
      params: backendParams,
    })

    const rows = Array.isArray(data?.data) ? data.data : []
    return {
      data: {
        data: rows.map(item => mapRequest(item)),
        meta: data?.meta,
      },
    }
  },

  async get (id: number) {
    const { data } = await api.get<BackendWrapped<BackendEvaluationRequest>>(`/evaluation-requests/${id}`)
    const row = getWrappedData(data)

    return {
      data: mapRequest(row),
    }
  },

  async create (payload: CreateEvaluationRequestPayload) {
    const { data } = await api.post<BackendWrapped<BackendEvaluationRequest>>('/evaluation-requests', mapCreatePayload(payload))
    const row = getWrappedData(data)

    return {
      data: mapRequest(row, {
        criteria_ids: payload.criteria_ids,
        instructions: payload.instructions || null,
        public_url: (data as BackendWrapped<BackendEvaluationRequest>)?.public_url || undefined,
      }),
    }
  },

  async update (id: number, payload: Partial<CreateEvaluationRequestPayload>) {
    const { data } = await api.put<BackendWrapped<BackendEvaluationRequest>>(`/evaluation-requests/${id}`, {
      ...(payload.form_type ? { type: payload.form_type } : {}),
      ...(payload.title ? { subject: payload.title } : {}),
      ...(payload.recipient_email === undefined ? {} : { recipient_email: payload.recipient_email || null }),
      ...(payload.recipient_name === undefined ? {} : { recipient_name: payload.recipient_name }),
      ...(payload.related_entity_type === undefined ? {} : { requestable_type: payload.related_entity_type }),
      ...(payload.related_entity_id === undefined ? {} : { requestable_id: payload.related_entity_id }),
      ...(payload.criteria_ids === undefined ? {} : { criteria_ids: payload.criteria_ids }),
      ...(payload.custom_message === undefined ? {} : { message: payload.custom_message }),
      ...(payload.expires_at === undefined ? {} : { expires_at: payload.expires_at }),
      ...(payload.instructions === undefined ? {} : { metadata: { instructions: payload.instructions } }),
    })

    const row = getWrappedData(data)

    return {
      data: mapRequest(row, {
        criteria_ids: payload.criteria_ids,
        instructions: payload.instructions || null,
      }),
    }
  },

  async delete (id: number) {
    return api.delete(`/evaluation-requests/${id}`)
  },

  async send (id: number) {
    const { data } = await api.post<BackendWrapped<BackendEvaluationRequest>>(`/evaluation-requests/${id}/send`)
    return {
      data: mapRequest(getWrappedData(data)),
    }
  },

  async sendReminder (id: number) {
    const { data } = await api.post<BackendWrapped<BackendEvaluationRequest>>(`/evaluation-requests/${id}/reminder`)
    return {
      data: mapRequest(getWrappedData(data)),
    }
  },

  async cancel (id: number) {
    const { data } = await api.post<BackendWrapped<BackendEvaluationRequest>>(`/evaluation-requests/${id}/cancel`)
    return {
      data: mapRequest(getWrappedData(data)),
    }
  },

  async getResponses (id: number) {
    const { data } = await api.get<BackendWrapped<any[]>>(`/evaluation-requests/${id}/responses`)
    const rows = Array.isArray(data?.data) ? data.data : []

    return {
      data: {
        data: rows.map(row => mapResponse(row)),
      },
    }
  },

  async getStatistics (params?: { form_type?: string }) {
    const backendParams = {
      ...params,
      type: params?.form_type,
    }

    const { data } = await api.get<BackendWrapped<any>>('/evaluation-requests/statistics', { params: backendParams })
    const raw = getWrappedData(data) || {}

    const mapped: EvaluationStatistics = {
      total_requests: Number(raw.total_requests ?? raw.total ?? 0),
      by_status: raw.by_status || {
        draft: Number(raw.draft || 0),
        sent: Number(raw.sent || 0),
        opened: Number(raw.opened || 0),
        completed: Number(raw.completed || 0),
        expired: Number(raw.expired || 0),
      },
      by_form_type: raw.by_form_type || {},
      response_rate: Number(raw.response_rate || 0),
      average_response_time_hours:
        raw.average_response_time_hours !== undefined && raw.average_response_time_hours !== null
          ? Number(raw.average_response_time_hours)
          : null,
      average_score:
        raw.average_score !== undefined && raw.average_score !== null
          ? Number(raw.average_score)
          : null,
    }

    return { data: mapped }
  },

  public: {
    async getForm (token: string) {
      const { data } = await api.get<BackendWrapped<any>>(`/public/evaluation/${token}`)
      const raw = getWrappedData(data)
      const requestRaw = raw?.request || {}
      const criteria = Array.isArray(raw?.criteria) ? raw.criteria : []

      const mapped: PublicFormData = {
        request: {
          id: Number(requestRaw.id || 0),
          enterprise_id: Number(requestRaw.enterprise_id || 0),
          form_type: requestRaw.type || 'satisfaction_client',
          title: requestRaw.subject || requestRaw.title || '',
          token,
          status: requestRaw.status || 'sent',
          recipient_email: requestRaw.recipient_email || null,
          recipient_name: requestRaw.recipient_name || null,
          related_entity_type: null,
          related_entity_id: null,
          criteria_ids: criteria.map((item: any) => Number(item.id)).filter((value: number) => Number.isFinite(value)),
          custom_message: requestRaw.message || null,
          instructions: requestRaw.instructions || null,
          expires_at: requestRaw.expires_at || null,
          sent_at: requestRaw.sent_at || null,
          opened_at: requestRaw.opened_at || null,
          completed_at: requestRaw.responded_at || null,
          reminder_count: Number(requestRaw.reminder_count || 0),
          last_reminder_at: requestRaw.last_reminder_at || null,
          created_by: Number(requestRaw.created_by || 0),
          public_url: `/evaluation/${token}`,
          created_at: requestRaw.created_at || new Date().toISOString(),
          updated_at: requestRaw.updated_at || new Date().toISOString(),
        },
        criteria,
      }

      return { data: mapped }
    },

    async submit (token: string, payload: SubmitResponsePayload) {
      const responses = Object.entries(payload.scores || {}).map(([criterionId, score]) => ({
        criterion_id: Number(criterionId),
        score: Number(score),
        comment: payload.comments?.[Number(criterionId)] || undefined,
      }))

      const { data } = await api.post<BackendWrapped<{ overall_score?: number, percentage?: number }>>(
        `/public/evaluation/${token}`,
        {
          responses,
          general_comment: payload.global_comment,
          recommendations: payload.global_comment,
        },
      )

      const inner = getWrappedData(data) || {}

      return {
        data: {
          message: data?.message || 'Merci pour votre réponse !',
          overall_score: Number(inner.overall_score ?? inner.percentage ?? 0),
        },
      }
    },

    async requestResetLink (token: string) {
      const { data } = await api.post<BackendWrapped<{ new_public_url?: string, expires_at?: string }>>(
        `/public/evaluation/${token}/reset-link`,
      )
      const inner = getWrappedData(data) || {}

      return {
        data: {
          message: data?.message || 'Un nouveau lien a ete genere.',
          new_public_url: inner.new_public_url || null,
          expires_at: inner.expires_at || null,
        },
      }
    },
  },
}

export default evaluationRequestsApi
