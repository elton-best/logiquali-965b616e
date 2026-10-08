import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

export interface ManagementReview {
  id?: number
  ref?: string
  title?: string
  site_id: number
  scheduled_date?: string
  planned_date: string
  actual_date?: string
  chairman_id: number
  participants?: number[] | string[]
  year?: number
  quarter?: string
  status: 'planned' | 'in_progress' | 'completed' | 'reported'
  // Input data
  previous_actions_status?: string
  context_changes?: string
  performance_indicators?: string
  customer_satisfaction?: string
  audit_results?: string
  nc_complaints_status?: string
  resources_adequacy?: string
  improvement_opportunities?: string
  // Outputs
  decisions?: any[]
  action_items?: any[]
  input_data?: any
  output_decisions?: any
  action_ids?: any
  kpi_data?: any
  objectives_data?: any
  actions_data?: any
  risks_data?: any
  nc_data?: any
  audit_data?: any
  report_path?: string
  generated_at?: string
  site?: any
  chairman?: any
  created_at?: string
  updated_at?: string
}

export const useManagementReviewStore = defineStore('managementReview', () => {
  const reviews = ref<ManagementReview[]>([])
  const currentReview = ref<ManagementReview | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed
  const upcomingReviews = computed(() => {
    const now = new Date()
    return reviews.value.filter(r =>
      r.status === 'planned' && new Date(r.planned_date) > now,
    ).slice().toSorted((a: ManagementReview, b: ManagementReview) =>
      new Date(a.planned_date).getTime() - new Date(b.planned_date).getTime(),
    )
  })

  const pastReviews = computed(() => {
    return reviews.value.filter(r =>
      r.status === 'completed' || r.status === 'reported',
    ).slice().toSorted((a: ManagementReview, b: ManagementReview) =>
      new Date(b.actual_date || b.planned_date).getTime()
        - new Date(a.actual_date || a.planned_date).getTime(),
    )
  })

  const reviewsByStatus = computed(() => {
    return reviews.value.reduce((acc, r) => {
      const statusKey = r.status || 'planned'
      if (!acc[statusKey]) {
        acc[statusKey] = []
      }
      acc[statusKey]!.push(r)
      return acc
    }, {} as Record<string, ManagementReview[]>)
  })

  const nextReview = computed(() => {
    return upcomingReviews.value[0] || null
  })

  function normalizeReview (item: any): ManagementReview {
    if (!item) {
      return {} as ManagementReview
    }

    const attrs = item.attributes || item
    const siteRelationship = item.relationships?.site
    const chairmanRelationship = item.relationships?.chairman

    return {
      id: Number(item.id || attrs.id),
      ref: attrs.ref,
      site_id: attrs.site_id ?? Number(siteRelationship?.id),
      planned_date: attrs.planned_date || attrs.scheduled_date || '',
      actual_date: attrs.actual_date || undefined,
      chairman_id: attrs.chairman_id ?? Number(chairmanRelationship?.id),
      participants: attrs.participants || [],
      status: attrs.status || 'planned',
      previous_actions_status: attrs.previous_actions_status,
      context_changes: attrs.context_changes,
      performance_indicators: attrs.performance_indicators,
      customer_satisfaction: attrs.customer_satisfaction,
      audit_results: attrs.audit_results,
      nc_complaints_status: attrs.nc_complaints_status,
      resources_adequacy: attrs.resources_adequacy,
      improvement_opportunities: attrs.improvement_opportunities,
      decisions: attrs.decisions || [],
      report_path: attrs.report_path,
      generated_at: attrs.generated_at,
      created_at: attrs.created_at,
      updated_at: attrs.updated_at,
      site: siteRelationship,
      chairman: chairmanRelationship,
      title: attrs.title,
      scheduled_date: attrs.scheduled_date,
      year: attrs.year,
      quarter: attrs.quarter,
      kpi_data: attrs.kpi_data,
      objectives_data: attrs.objectives_data,
      actions_data: attrs.actions_data,
      risks_data: attrs.risks_data,
      nc_data: attrs.nc_data,
      audit_data: attrs.audit_data,
      action_items: attrs.action_items,
      input_data: attrs.input_data,
      output_decisions: attrs.output_decisions,
      action_ids: attrs.action_ids,
    } as ManagementReview
  }

  // Actions
  async function fetchReviews (page = 1, filters: any = {}) {
    loading.value = true
    error.value = null
    try {
      const params = { page, per_page: pagination.value.per_page, ...filters }
      const response = await api.get('/management-reviews', { params })
      const data = response.data?.data || []
      const meta = response.data?.meta || {}

      reviews.value = Array.isArray(data) ? data.map((item: any) => normalizeReview(item)) : []
      pagination.value = {
        current_page: Number(meta.current_page || 1),
        last_page: Number(meta.last_page || 1),
        per_page: Number(meta.per_page || pagination.value.per_page),
        total: Number(meta.total ?? reviews.value.length),
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchReview (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/management-reviews/${id}`)
      const review = normalizeReview(response.data?.data)
      currentReview.value = review
      return review
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createReview (data: Partial<ManagementReview>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/management-reviews', data)
      const review = normalizeReview(response.data?.data)
      reviews.value.unshift(review)
      return review
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateReview (id: number, data: Partial<ManagementReview>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/management-reviews/${id}`, data)
      const review = normalizeReview(response.data?.data)
      const index = reviews.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reviews.value[index] = review
      }
      if (currentReview.value?.id === id) {
        currentReview.value = review
      }
      return review
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateStatus (id: number, status: ManagementReview['status']) {
    return updateReview(id, { status })
  }

  async function startReview (id: number) {
    return updateStatus(id, 'in_progress')
  }

  async function completeReview (id: number, actualDate: string) {
    return updateReview(id, { status: 'completed', actual_date: actualDate })
  }

  async function deleteReview (id: number) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/management-reviews/${id}`)
      reviews.value = reviews.value.filter(r => r.id !== id)
      if (currentReview.value?.id === id) {
        currentReview.value = null
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateInputData (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/management-reviews/${id}/generate-data`)
      if (currentReview.value?.id === id) {
        currentReview.value = { ...currentReview.value, ...response.data.data }
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de génération'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateSmSynthesis (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/management-reviews/${id}/generate-sm-synthesis`)
      return response.data?.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de génération de la synthèse du SM'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function exportReportDocx (id: number, download = true) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/management-reviews/${id}/export-docx`, {
        responseType: 'blob',
      })

      if (download) {
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rapport_revue_direction_${new Date().toISOString().split('T')[0]}.docx`)
        document.body.append(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      }
      return {
        generatedDocumentId: Number(response.headers?.['x-generated-document-id'] || 0) || null,
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur d\'export'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function sendInvitations (id: number, userIds: Array<number | string> = []) {
    loading.value = true
    error.value = null
    try {
      const normalizedUserIds = userIds
        .map(Number)
        .filter(value => Number.isFinite(value))

      const response = await api.post(`/management-reviews/${id}/send-invitations`, {
        user_ids: normalizedUserIds,
      })
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur d\'envoi'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function closeReview (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/management-reviews/${id}/close`)
      const review = normalizeReview(response.data?.data || response.data)
      const index = reviews.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reviews.value[index] = review
      }
      if (currentReview.value?.id === id) {
        currentReview.value = review
      }
      return review
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de clôture'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function $reset () {
    reviews.value = []
    currentReview.value = null
    loading.value = false
    error.value = null
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
    }
  }

  return {
    // State
    reviews,
    currentReview,
    loading,
    error,
    pagination,

    // Computed
    upcomingReviews,
    pastReviews,
    reviewsByStatus,
    nextReview,

    // Actions
    fetchReviews,
    fetchReview,
    createReview,
    updateReview,
    updateStatus,
    startReview,
    completeReview,
    deleteReview,
    generateInputData,
    generateSmSynthesis,
    exportReportDocx,
    sendInvitations,
    closeReview,
    $reset,
  }
})
