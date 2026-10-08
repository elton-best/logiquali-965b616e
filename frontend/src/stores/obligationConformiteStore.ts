import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface ObligationConformite {
  id: number
  enterprise_id: number
  site_id: number
  type: 'reglementaire' | 'volontaire' | 'contractuelle'
  reference: string
  titre: string
  description?: string
  autorite_competente?: string
  date_application?: string
  periodicite_controle?: string
  date_prochain_controle?: string
  statut_conformite: 'conforme' | 'non_conforme' | 'en_cours' | 'non_applicable'
  preuves_conformite?: string
  document_path?: string
  actions_correctives?: string
  site?: any
}

export const useObligationConformiteStore = defineStore('obligationConformite', () => {
  const obligations = ref<ObligationConformite[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchObligations (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/obligations-conformite-environnementales', { params: filters })
      obligations.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createObligation (formData: FormData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/obligations-conformite-environnementales', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      obligations.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateObligation (id: number, formData: FormData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post(`/obligations-conformite-environnementales/${id}?_method=PUT`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      const index = obligations.value.findIndex(o => o.id === id)
      if (index !== -1) {
        obligations.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteObligation (id: number) {
    loading.value = true
    error.value = null
    try {
      await httpClient.delete(`/obligations-conformite-environnementales/${id}`)
      obligations.value = obligations.value.filter(o => o.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAlertes (days = 30) {
    try {
      const { data } = await httpClient.get('/obligations-conformite-environnementales/alertes', { params: { days } })
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur alertes'
      throw error_
    }
  }

  async function fetchStats () {
    try {
      const { data } = await httpClient.get('/obligations-conformite-environnementales/stats')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur stats'
      throw error_
    }
  }

  return {
    obligations,
    loading,
    error,
    fetchObligations,
    createObligation,
    updateObligation,
    deleteObligation,
    fetchAlertes,
    fetchStats,
  }
})
