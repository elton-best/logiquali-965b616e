import type { Communication, CommunicationFormData, CommunicationPlan, CommunicationStats } from '@/modules/clienta/types/communication.types'
import api from './client'

function resolveDates (raw: any): { dateDebut: string | null, dateFin: string | null } {
  const rawDebut = raw?.date_debut || raw?.dateDebut || null
  const rawFin = raw?.date_fin || raw?.dateFin || null
  return {
    dateDebut: rawDebut ? String(rawDebut) : null,
    dateFin: rawFin ? String(rawFin) : null,
  }
}

function toCommunication (raw: any): Communication {
  const { dateDebut, dateFin } = resolveDates(raw)
  return {
    id: String(raw?.id || ''),
    numero: Number(raw?.numero || 0),
    type: raw?.type || 'communication',
    designation: String(raw?.designation || ''),
    cibles: Array.isArray(raw?.cibles) ? raw.cibles : [],
    moyens: Array.isArray(raw?.moyens) ? raw.moyens : [],
    chronogramme: Array.isArray(raw?.chronogramme) ? raw.chronogramme : Array.from({ length: 12 }).fill(false),
    responsable: String(raw?.responsable || ''),
    cout: raw?.cout !== undefined && raw?.cout !== null ? Number(raw.cout) : undefined,
    dateDebut,
    dateFin,
    periodMode: raw?.period_mode || raw?.periodMode || 'custom',
    status: raw?.status || 'planifiee',
    frequency: raw?.frequency || 'ponctuelle',
    observations: raw?.observations || undefined,
    proofs: Array.isArray(raw?.proofs) ? raw.proofs : [],
    history: Array.isArray(raw?.history) ? raw.history : [],
    alerts: Array.isArray(raw?.alerts) ? raw.alerts : [],
    createdAt: String(raw?.created_at || raw?.createdAt || ''),
    updatedAt: String(raw?.updated_at || raw?.updatedAt || ''),
    createdBy: String(raw?.created_by || raw?.creator?.name || ''),
    siteId: raw?.site_id !== undefined && raw?.site_id !== null ? String(raw.site_id) : undefined,
    planYear: raw?.plan_year !== undefined && raw?.plan_year !== null ? Number(raw.plan_year) : undefined,
  }
}

function toCommunicationPlan (raw: any): CommunicationPlan {
  return {
    id: Number(raw?.id || 0),
    enterpriseId: Number(raw?.enterprise_id || 0),
    siteId: raw?.site_id !== undefined && raw?.site_id !== null ? Number(raw.site_id) : undefined,
    year: Number(raw?.year || 0),
    status: raw?.status || 'draft',
    plannedBudget: raw?.planned_budget !== undefined && raw?.planned_budget !== null ? Number(raw.planned_budget) : undefined,
    plannedActions: raw?.planned_actions !== undefined && raw?.planned_actions !== null ? Number(raw.planned_actions) : undefined,
    spentAmount: Number(raw?.spent_amount || 0),
    budgetEngaged: Number(raw?.budget_engaged || 0),
    budgetRealized: Number(raw?.budget_realized || 0),
    budgetRemaining: Number(raw?.budget_remaining || 0),
    stats: {
      totalActions: Number(raw?.stats?.total_actions || 0),
      planifiees: Number(raw?.stats?.planifiees || 0),
      replanifiees: Number(raw?.stats?.replanifiees || 0),
      enAttente: Number(raw?.stats?.en_attente || 0),
      realisees: Number(raw?.stats?.realisees || 0),
      annulees: Number(raw?.stats?.annulees || 0),
      tauxRealisation: Number(raw?.stats?.taux_realisation || 0),
    },
    createdAt: raw?.created_at || undefined,
    updatedAt: raw?.updated_at || undefined,
  }
}

export const communicationApi = {
  getAll: async (params?: any) => {
    const rows = (await api.get<any[]>('/communications', { params })).data
    return Array.isArray(rows) ? rows.map(row => toCommunication(row)) : []
  },

  getById: async (id: string) => toCommunication((await api.get<any>(`/communications/${id}`)).data),

  create: async (data: CommunicationFormData) => {
    const payload: Record<string, any> = { ...data }
    if (data.planYear !== undefined) {
      payload.plan_year = data.planYear
    }
    if (data.periodMode) {
      payload.period_mode = data.periodMode
    }
    return toCommunication((await api.post<any>('/communications', payload)).data)
  },

  importFile: async (payload: { file: File, year: number }) => {
    const formData = new FormData()
    formData.append('file', payload.file)
    formData.append('year', String(payload.year))
    const response = await api.post<{
      message: string
      created: number
      updated: number
      skipped: number
      incomplete: number
    }>('/communications/import-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  update: async (id: string, data: Partial<CommunicationFormData>) => {
    const payload: Record<string, any> = { ...data }
    if (payload.planYear !== undefined && !payload.plan_year) {
      payload.plan_year = payload.planYear
    }
    if (payload.periodMode && !payload.period_mode) {
      payload.period_mode = payload.periodMode
    }
    return toCommunication((await api.put<any>(`/communications/${id}`, payload)).data)
  },

  delete: (id: string) => api.delete(`/communications/${id}`),

  complete: async (id: string, proofs: File[]) => {
    const formData = new FormData()
    for (const file of proofs) {
      formData.append('proofs[]', file)
    }
    return toCommunication((await api.post<any>(`/communications/${id}/complete`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })).data)
  },

  reschedule: async (id: string, dateDebut: string, dateFin: string, comment?: string) =>
    toCommunication((await api.post<any>(`/communications/${id}/reschedule`, { date_debut: dateDebut, date_fin: dateFin, comment })).data),

  cancel: async (id: string, reason: string) =>
    toCommunication((await api.post<any>(`/communications/${id}/cancel`, { reason })).data),

  uploadProof: async (id: string, file: File) => {
    const formData = new FormData()
    formData.append('proof', file)
    return (await api.post(`/communications/${id}/proofs`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })).data
  },

  deleteProof: (communicationId: string, proofId: string) =>
    api.delete(`/communications/${communicationId}/proofs/${proofId}`),

  getStats: async (params?: any) => (await api.get<CommunicationStats>('/communications/stats', { params })).data,

  downloadTemplate: async () => {
    const response = await api.get('/communications/template', {
      responseType: 'blob',
      headers: { 'X-Skip-Error-Toast': 'false' },
    })
    return response.data as Blob
  },
}

export const communicationPlanApi = {
  getAll: async (params?: { year?: number, site_id?: number }) => {
    const rows = (await api.get<any[]>('/communication-plans', { params })).data
    return Array.isArray(rows) ? rows.map(row => toCommunicationPlan(row)) : []
  },
  create: async (payload: {
    year: number
    status?: 'draft' | 'active' | 'closed'
    planned_budget?: number
    planned_actions?: number
    site_id?: number
  }) => toCommunicationPlan((await api.post<any>('/communication-plans', payload)).data),
  update: async (
    id: number,
    payload: {
      status?: 'draft' | 'active' | 'closed'
      planned_budget?: number
      planned_actions?: number
      site_id?: number
    },
  ) => toCommunicationPlan((await api.put<any>(`/communication-plans/${id}`, payload)).data),
  delete: async (id: number) => api.delete(`/communication-plans/${id}`),
  sync: async (id: number) => toCommunicationPlan((await api.post<any>(`/communication-plans/${id}/sync`)).data),
}
