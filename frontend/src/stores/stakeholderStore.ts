import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import api from '@/api/client'

export interface Stakeholder {
  id?: number
  ref?: string
  site_id: number
  name: string
  type: 'client' | 'supplier' | 'partner' | 'regulator' | 'employee' | 'shareholder' | 'other'
  relevance_degree?: string
  needs_expectations?: string
  requirements?: string
  actions?: string
  responsible_id?: number
  deadline?: string
  site?: any
  responsible?: any
  created_at?: string
  updated_at?: string
}

export const useStakeholderStore = defineStore('stakeholder', () => {
  const stakeholders = ref<Stakeholder[]>([])
  const currentStakeholder = ref<Stakeholder | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed
  const stakeholdersByType = computed(() => {
    return stakeholders.value.reduce((acc, s) => {
      const bucket = acc[s.type] ?? []
      bucket.push(s)
      acc[s.type] = bucket
      return acc
    }, {} as Record<string, Stakeholder[]>)
  })

  const highRelevanceStakeholders = computed(() => {
    return stakeholders.value.filter(s =>
      s.relevance_degree && ['high', 'élevé', 'critique'].some(k =>
        s.relevance_degree!.toLowerCase().includes(k),
      ),
    )
  })

  // Actions
  async function fetchStakeholders (page = 1, filters: any = {}) {
    loading.value = true
    error.value = null
    try {
      const params = { page, per_page: pagination.value.per_page, ...filters }
      const response = await api.get('/stakeholders', { params })
      stakeholders.value = response.data.data
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

  async function fetchStakeholder (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/stakeholders/${id}`)
      currentStakeholder.value = response.data.data
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createStakeholder (data: Partial<Stakeholder>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.post('/stakeholders', data)
      stakeholders.value.unshift(response.data.data)
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateStakeholder (id: number, data: Partial<Stakeholder>) {
    loading.value = true
    error.value = null
    try {
      const response = await api.put(`/stakeholders/${id}`, data)
      const index = stakeholders.value.findIndex(s => s.id === id)
      if (index !== -1) {
        stakeholders.value[index] = response.data.data
      }
      if (currentStakeholder.value?.id === id) {
        currentStakeholder.value = response.data.data
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteStakeholder (id: number) {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/stakeholders/${id}`)
      stakeholders.value = stakeholders.value.filter(s => s.id !== id)
      if (currentStakeholder.value?.id === id) {
        currentStakeholder.value = null
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur de suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function exportStakeholdersDocx (siteId?: number) {
    loading.value = true
    error.value = null
    try {
      const params = siteId ? { site_id: siteId } : {}
      const response = await api.get('/stakeholders/export-docx', {
        params,
        responseType: 'blob',
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `registre_parties_interessees_${new Date().toISOString().split('T')[0]}.docx`)
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
    stakeholders.value = []
    currentStakeholder.value = null
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
    stakeholders,
    currentStakeholder,
    loading,
    error,
    pagination,

    // Computed
    stakeholdersByType,
    highRelevanceStakeholders,

    // Actions
    fetchStakeholders,
    fetchStakeholder,
    createStakeholder,
    updateStakeholder,
    deleteStakeholder,
    exportStakeholdersDocx,
    $reset,
  }
})
