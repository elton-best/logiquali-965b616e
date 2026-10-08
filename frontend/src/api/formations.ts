import type {
  Formation,
  FormationFormData,
  FormationInternalEvaluation,
  FormationStats,
  TrainingPlan,
} from '@/modules/clienta/types/formation.types'
import api from './client'

function normalizeStatus (status: string | undefined): Formation['status'] {
  if (status === 'completed') {
    return 'realisee'
  }
  if (
    status === 'realisee'
    || status === 'en_attente'
    || status === 'replanifiee'
    || status === 'annulee'
  ) {
    return status
  }
  return 'planifiee'
}

function resolveTargetUserIds (raw: any): number[] {
  return Array.isArray(raw?.target_user_ids)
    ? raw.target_user_ids.map(Number)
    : []
}

function resolveChronogramme (raw: any): boolean[] {
  return Array.isArray(raw?.chronogramme)
    ? raw.chronogramme.map(Boolean)
    : Array.from({ length: 12 }, () => false)
}

function resolveDates (raw: any): { dateDebut: string | null, dateFin: string | null } {
  const rawDebut = raw?.date_debut || raw?.dateDebut || null
  const rawFin = raw?.date_fin || raw?.dateFin || null
  return {
    dateDebut: rawDebut ? String(rawDebut) : null,
    dateFin: rawFin ? String(rawFin) : null,
  }
}

function resolvePeriod (raw: any): {
  periodMode: 'standard' | 'custom'
  periodLabel?: string
} {
  const rawMode = String(raw?.period_mode || raw?.periodMode || 'custom')
  return {
    periodMode: rawMode === 'standard' ? 'standard' : 'custom',
    periodLabel: raw?.period_label || raw?.periodLabel || undefined,
  }
}

function mapTargets (rawTargets: any[]): Formation['targets'] {
  return rawTargets.map((target: any) => ({
    id: Number(target?.id || 0),
    name: String(target?.name || ''),
    email: target?.email ? String(target.email) : undefined,
  }))
}

function mapProofs (rawProofs: any[]): Formation['proofs'] {
  return rawProofs.map((proof: any) => ({
    id: String(proof?.id || ''),
    filename: String(proof?.filename || ''),
    url: String(proof?.url || ''),
    uploadedAt: String(proof?.uploaded_at || proof?.uploadedAt || ''),
    uploadedBy: String(proof?.uploader?.name || proof?.uploaded_by || ''),
  }))
}

function mapHistory (rawHistory: any[]): Formation['history'] {
  return rawHistory.map((item: any) => ({
    id: String(item?.id || ''),
    action: item?.action || 'updated',
    date: String(item?.created_at || item?.date || ''),
    userId: String(item?.user_id || item?.userId || ''),
    userName: String(item?.user_name || item?.userName || ''),
    comment: item?.comment || undefined,
    previousDate: item?.previous_date || item?.previousDate || undefined,
    newDate: item?.new_date || item?.newDate || undefined,
  }))
}

function mapAlerts (rawAlerts: any[], rawFormationId: any): Formation['alerts'] {
  return rawAlerts.map((alert: any) => ({
    id: String(alert?.id || ''),
    formationId: String(
      alert?.formation_id || alert?.formationId || rawFormationId || '',
    ),
    type: alert?.type || 'J-7',
    date: String(alert?.date || ''),
    sent: Boolean(alert?.sent),
    sentAt: alert?.sent_at || alert?.sentAt || undefined,
  }))
}

function mapInternalEvaluation (
  rawEvaluation: any,
  rawFormationId: any,
): FormationInternalEvaluation | undefined {
  if (!rawEvaluation) {
    return undefined
  }
  return {
    id: String(rawEvaluation?.id || ''),
    formationId: String(rawEvaluation?.formation_id || rawFormationId || ''),
    evaluatorId: rawEvaluation?.evaluator_id
      ? String(rawEvaluation.evaluator_id)
      : undefined,
    method: 'combinaison',
    scores: rawEvaluation?.scores || undefined,
    globalScore:
      rawEvaluation?.global_score !== undefined
      && rawEvaluation?.global_score !== null
        ? Number(rawEvaluation.global_score)
        : undefined,
    strengths: Array.isArray(rawEvaluation?.strengths)
      ? rawEvaluation.strengths
      : undefined,
    improvements: Array.isArray(rawEvaluation?.improvements)
      ? rawEvaluation.improvements
      : undefined,
    comment: rawEvaluation?.comment || undefined,
    evaluatedAt: rawEvaluation?.evaluated_at || undefined,
    createdAt: rawEvaluation?.created_at || undefined,
    updatedAt: rawEvaluation?.updated_at || undefined,
  }
}

function toFormation (raw: any): Formation {
  const proofs = Array.isArray(raw?.proofs) ? raw.proofs : []
  const history = Array.isArray(raw?.history) ? raw.history : []
  const alerts = Array.isArray(raw?.alerts) ? raw.alerts : []
  const targetsRaw = Array.isArray(raw?.targets) ? raw.targets : []
  const internalEvaluationRaw
    = raw?.internal_evaluation || raw?.internalEvaluation || null
  const { dateDebut, dateFin } = resolveDates(raw)
  const { periodMode, periodLabel } = resolvePeriod(raw)

  return {
    id: String(raw?.id || ''),
    numero: Number(raw?.numero || 0),
    designation: String(raw?.designation || ''),
    cibles: Array.isArray(raw?.cibles) ? raw.cibles : [],
    targetUserIds: resolveTargetUserIds(raw),
    targets: mapTargets(targetsRaw),
    chronogramme: resolveChronogramme(raw),
    formateur: String(raw?.formateur || ''),
    dateDebut,
    dateFin,
    periodMode,
    periodLabel,
    status: normalizeStatus(raw?.status),
    alertState: raw?.alert_state || undefined,
    frequency: raw?.frequency || 'ponctuelle',
    observations: raw?.observations || '',
    proofs: mapProofs(proofs),
    history: mapHistory(history),
    alerts: mapAlerts(alerts, raw?.id),
    internalEvaluation: mapInternalEvaluation(internalEvaluationRaw, raw?.id),
    createdAt: String(raw?.created_at || raw?.createdAt || ''),
    updatedAt: String(raw?.updated_at || raw?.updatedAt || ''),
    createdBy: String(raw?.created_by || raw?.creator?.name || ''),
    siteId:
      raw?.site_id !== undefined && raw?.site_id !== null
        ? String(raw.site_id)
        : undefined,
    planYear:
      raw?.plan_year !== undefined && raw?.plan_year !== null
        ? Number(raw.plan_year)
        : undefined,
  }
}

function toInternalEvaluation (raw: any): FormationInternalEvaluation {
  return {
    id: String(raw?.id || ''),
    formationId: String(raw?.formation_id || raw?.formationId || ''),
    evaluatorId: raw?.evaluator_id ? String(raw.evaluator_id) : undefined,
    method: 'combinaison',
    scores: raw?.scores || undefined,
    globalScore:
      raw?.global_score !== undefined && raw?.global_score !== null
        ? Number(raw.global_score)
        : undefined,
    strengths: Array.isArray(raw?.strengths) ? raw.strengths : undefined,
    improvements: Array.isArray(raw?.improvements)
      ? raw.improvements
      : undefined,
    comment: raw?.comment || undefined,
    evaluatedAt: raw?.evaluated_at || undefined,
    createdAt: raw?.created_at || undefined,
    updatedAt: raw?.updated_at || undefined,
  }
}

function resolveTrainingPlanStats (raw: any): TrainingPlan['stats'] {
  return {
    totalFormations: Number(raw?.stats?.total_formations || 0),
    planifiees: Number(raw?.stats?.planifiees || 0),
    replanifiees: Number(raw?.stats?.replanifiees || 0),
    enAttente: Number(raw?.stats?.en_attente || 0),
    realisees: Number(raw?.stats?.realisees || 0),
    annulees: Number(raw?.stats?.annulees || 0),
    tauxRealisation: Number(raw?.stats?.taux_realisation || 0),
  }
}

function resolveTrainingPlanBudgets (raw: any) {
  const totalBudget = Number(raw?.total_budget || 0)
  const budgetEngaged = Number(raw?.budget_engaged ?? raw?.spent_amount ?? 0)
  const budgetRealized = Number(raw?.budget_realized ?? raw?.spent_amount ?? 0)
  const budgetRemaining = Number(
    raw?.budget_remaining ?? Math.max(0, totalBudget - budgetEngaged),
  )
  return { totalBudget, budgetEngaged, budgetRealized, budgetRemaining }
}

function toTrainingPlan (raw: any): TrainingPlan {
  const { totalBudget, budgetEngaged, budgetRealized, budgetRemaining }
    = resolveTrainingPlanBudgets(raw)
  const stats = resolveTrainingPlanStats(raw)

  return {
    id: Number(raw?.id || 0),
    enterpriseId: Number(raw?.enterprise_id || 0),
    siteId:
      raw?.site_id !== undefined && raw?.site_id !== null
        ? Number(raw.site_id)
        : undefined,
    year: Number(raw?.year || 0),
    status: raw?.status || 'draft',
    totalBudget,
    spentAmount: budgetRealized,
    budgetEngaged,
    budgetRealized,
    budgetRemaining,
    plannedFormations:
      raw?.planned_formations !== undefined && raw?.planned_formations !== null
        ? Number(raw.planned_formations)
        : undefined,
    stats,
    createdAt: raw?.created_at || undefined,
    updatedAt: raw?.updated_at || undefined,
  }
}

export const formationApi = {
  getAll: async (params?: any) => {
    const rows = (await api.get<any[]>('/formations', { params })).data
    return Array.isArray(rows) ? rows.map(row => toFormation(row)) : []
  },

  getById: async (id: string) =>
    toFormation((await api.get<any>(`/formations/${id}`)).data),

  create: async (data: FormationFormData) => {
    const payload: Record<string, any> = {
      ...data,
      target_user_ids: data.targetUserIds,
      period_mode: data.periodMode,
      period_label: data.periodLabel,
    }
    if (data.planYear !== undefined) {
      payload.plan_year = data.planYear
    }
    return toFormation((await api.post<any>('/formations', payload)).data)
  },

  importFile: async (payload: {
    file: File
    year: number
    targetUserIds: number[]
  }) => {
    const formData = new FormData()
    formData.append('file', payload.file)
    formData.append('year', String(payload.year))
    for (const userId of payload.targetUserIds) {
      formData.append('target_user_ids[]', String(userId))
    }

    const response = await api.post<{
      message: string
      created: number
      updated: number
      skipped: number
      incomplete: number
    }>('/formations/import-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    return response.data
  },

  update: async (id: string, data: Partial<FormationFormData>) => {
    const payload: Record<string, any> = { ...data }
    if (payload.dateDebut !== undefined && !payload.date_debut) {
      payload.date_debut = payload.dateDebut
    }
    if (payload.dateFin !== undefined && !payload.date_fin) {
      payload.date_fin = payload.dateFin
    }
    if (payload.targetUserIds && !payload.target_user_ids) {
      payload.target_user_ids = payload.targetUserIds
    }
    if (payload.periodMode && !payload.period_mode) {
      payload.period_mode = payload.periodMode
    }
    if (payload.periodLabel && !payload.period_label) {
      payload.period_label = payload.periodLabel
    }
    if (payload.planYear !== undefined && !payload.plan_year) {
      payload.plan_year = payload.planYear
    }
    return toFormation((await api.put<any>(`/formations/${id}`, payload)).data)
  },

  delete: (id: string) => api.delete(`/formations/${id}`),

  complete: async (id: string, proofs: File[]) => {
    const formData = new FormData()
    for (const file of proofs) {
      formData.append('proofs[]', file)
    }
    return toFormation(
      (
        await api.post<any>(`/formations/${id}/complete`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      ).data,
    )
  },

  reschedule: async (
    id: string,
    dateDebut: string,
    dateFin: string,
    comment?: string,
  ) =>
    (
      await api.post<Formation>(`/formations/${id}/reschedule`, {
        date_debut: dateDebut,
        date_fin: dateFin,
        comment,
      })
    ).data,

  cancel: async (id: string, reason: string) =>
    (await api.post<Formation>(`/formations/${id}/cancel`, { reason })).data,

  uploadProof: async (id: string, file: File) => {
    const formData = new FormData()
    formData.append('proof', file)
    const proof = (
      await api.post<any>(`/formations/${id}/proofs`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    ).data
    return {
      id: String(proof?.id || ''),
      filename: String(proof?.filename || ''),
      url: String(proof?.url || ''),
      uploadedAt: String(proof?.uploaded_at || proof?.uploadedAt || ''),
      uploadedBy: String(proof?.uploader?.name || proof?.uploaded_by || ''),
    }
  },

  deleteProof: (formationId: string, proofId: string) =>
    api.delete(`/formations/${formationId}/proofs/${proofId}`),

  getStats: async (params?: any) =>
    (await api.get<FormationStats>('/formations/stats', { params })).data,

  getInternalEvaluation: async (formationId: string) => {
    const response = await api.get<any>(
      `/formations/${formationId}/internal-evaluation`,
    )
    if (!response.data) {
      return null
    }
    return toInternalEvaluation(response.data)
  },

  upsertInternalEvaluation: async (
    formationId: string,
    payload: {
      method?: 'combinaison'
      scores?: {
        pedagogie?: number
        contenu?: number
        applicabilite?: number
        animation?: number
      }
      global_score?: number
      strengths?: string[]
      improvements?: string[]
      comment?: string
      evaluated_at?: string
    },
  ) =>
    toInternalEvaluation(
      (
        await api.post<any>(
          `/formations/${formationId}/internal-evaluation`,
          payload,
        )
      ).data,
    ),

  deleteInternalEvaluation: (formationId: string) =>
    api.delete(`/formations/${formationId}/internal-evaluation`),
}

export const trainingPlanApi = {
  getAll: async (params?: { year?: number, site_id?: number }) => {
    const rows = (await api.get<any[]>('/training-plans', { params })).data
    return Array.isArray(rows) ? rows.map(row => toTrainingPlan(row)) : []
  },

  create: async (payload: {
    year: number
    status?: 'draft' | 'active' | 'closed'
    total_budget?: number
    planned_formations?: number
    site_id?: number
  }) => toTrainingPlan((await api.post<any>('/training-plans', payload)).data),

  update: async (
    id: number,
    payload: {
      status?: 'draft' | 'active' | 'closed'
      total_budget?: number
      planned_formations?: number
      site_id?: number
    },
  ) =>
    toTrainingPlan((await api.put<any>(`/training-plans/${id}`, payload)).data),

  delete: async (id: number) => api.delete(`/training-plans/${id}`),

  sync: async (id: number) =>
    toTrainingPlan((await api.post<any>(`/training-plans/${id}/sync`)).data),

  exportXlsx: async (id: number) => {
    const response = await api.get(`/training-plans/${id}/export-xlsx`, {
      responseType: 'blob',
      headers: { 'X-Skip-Error-Toast': 'false' },
    })
    return response.data as Blob
  },
  downloadTemplate: async () => {
    const response = await api.get('/training-plans/template', {
      responseType: 'blob',
      headers: { 'X-Skip-Error-Toast': 'false' },
    })
    return response.data as Blob
  },
}
