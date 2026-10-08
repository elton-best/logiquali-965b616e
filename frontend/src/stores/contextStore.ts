import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

export interface OrganizationContext {
  id?: number
  ref?: string
  site_id: number
  year: number
  // SWOT Analysis
  internal_strengths?: string
  internal_weaknesses?: string
  external_opportunities?: string
  external_threats?: string
  // PESTEL Analysis
  political_factors?: string
  economic_factors?: string
  social_factors?: string
  technological_factors?: string
  environmental_factors?: string
  legal_factors?: string
  site?: any
  created_at?: string
  updated_at?: string
}

export const useContextStore = defineStore('context', () => {
  const contexts = ref<OrganizationContext[]>([])
  const currentContext = ref<OrganizationContext | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed
  const latestContext = computed(() => {
    if (contexts.value.length === 0) {
      return null
    }
    return contexts.value.reduce((latest, ctx) =>
      ctx.year > latest.year ? ctx : latest,
    )
  })

  const contextsByYear = computed(() => {
    return contexts.value.reduce((acc, ctx) => {
      const bucket = acc[ctx.year] ?? []
      bucket.push(ctx)
      acc[ctx.year] = bucket
      return acc
    }, {} as Record<number, OrganizationContext[]>)
  })

  const swotAnalysis = computed(() => {
    if (!currentContext.value) {
      return null
    }
    return {
      strengths: parseTextToArray(currentContext.value.internal_strengths),
      weaknesses: parseTextToArray(currentContext.value.internal_weaknesses),
      opportunities: parseTextToArray(currentContext.value.external_opportunities),
      threats: parseTextToArray(currentContext.value.external_threats),
    }
  })

  const pestelAnalysis = computed(() => {
    if (!currentContext.value) {
      return null
    }
    return {
      political: parseTextToArray(currentContext.value.political_factors),
      economic: parseTextToArray(currentContext.value.economic_factors),
      social: parseTextToArray(currentContext.value.social_factors),
      technological: parseTextToArray(currentContext.value.technological_factors),
      environmental: parseTextToArray(currentContext.value.environmental_factors),
      legal: parseTextToArray(currentContext.value.legal_factors),
    }
  })

  // Helpers
  function parseTextToArray (text?: string): string[] {
    if (!text) {
      return []
    }
    return text.split(/[,\n]+/).map(item => item.trim()).filter(Boolean)
  }

  // Actions
  async function fetchContexts (page = 1, filters: any = {}) {
    loading.value = true
    error.value = null
    try {
      const params = { page, per_page: pagination.value.per_page, ...filters }
      const response = await api.get('/contexts', { params })
      contexts.value = response.data.data
      pagination.value = {
        current_page: response.data.meta.current_page,
        last_page: response.data.meta.last_page,
        per_page: response.data.meta.per_page,
        total: response.data.meta.total,
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchContext (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/contexts/${id}`)
      currentContext.value = response.data.data
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchLatestContext (siteId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/contexts', {
        params: { site_id: siteId, sort: '-year', limit: 1 },
      })
      if (response.data.data.length > 0) {
        currentContext.value = response.data.data[0]
        return response.data.data[0]
      }
      return null
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createContext (data: Partial<OrganizationContext>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/contexts', data)
      contexts.value.unshift(response.data.data)
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateContext (id: number, data: Partial<OrganizationContext>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/contexts/${id}`, data)
      const index = contexts.value.findIndex(c => c.id === id)
      if (index !== -1) {
        contexts.value[index] = response.data.data
      }
      if (currentContext.value?.id === id) {
        currentContext.value = response.data.data
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteContext (id: number) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/contexts/${id}`)
      contexts.value = contexts.value.filter(c => c.id !== id)
      if (currentContext.value?.id === id) {
        currentContext.value = null
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function exportContextDocx (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/contexts/${id}/export-docx`, {
        responseType: 'blob',
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `contexte_organisation_${new Date().toISOString().split('T')[0]}.docx`)
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur d\'export'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function $reset () {
    contexts.value = []
    currentContext.value = null
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
    contexts,
    currentContext,
    loading,
    error,
    pagination,

    // Computed
    latestContext,
    contextsByYear,
    swotAnalysis,
    pestelAnalysis,

    // Actions
    fetchContexts,
    fetchContext,
    fetchLatestContext,
    createContext,
    updateContext,
    deleteContext,
    exportContextDocx,
    $reset,
  }
})
