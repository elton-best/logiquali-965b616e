import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface ConsommationEnergie {
  id: number
  enterprise_id: number
  site_id: number
  equipement_id?: number
  periode_debut: string
  periode_fin: string
  type_energie: string
  valeur_consommation: number
  unite: string
  mode_saisie: 'releve_reel' | 'estimation' | 'import_compteur'
  source_donnee?: string
  cout_euro?: number
  emission_co2_kg?: number
  observations?: string
  equipement?: any
  site?: any
}

export const useConsommationEnergieStore = defineStore('consommationEnergie', () => {
  const consommations = ref<ConsommationEnergie[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchConsommations (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/consommations-energie', { params: filters })
      consommations.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createConsommation (consommation: Partial<ConsommationEnergie>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/consommations-energie', consommation)
      consommations.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateConsommation (id: number, consommation: Partial<ConsommationEnergie>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.put(`/consommations-energie/${id}`, consommation)
      const index = consommations.value.findIndex(c => c.id === id)
      if (index !== -1) {
        consommations.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteConsommation (id: number) {
    loading.value = true
    error.value = null
    try {
      await httpClient.delete(`/consommations-energie/${id}`)
      consommations.value = consommations.value.filter(c => c.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStats (siteId?: number) {
    try {
      const { data } = await httpClient.get('/consommations-energie/stats', { params: { site_id: siteId } })
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur stats'
      throw error_
    }
  }

  async function importCsv (formData: FormData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/consommations-energie/import-file', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur import'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    consommations,
    loading,
    error,
    fetchConsommations,
    createConsommation,
    updateConsommation,
    deleteConsommation,
    fetchStats,
    importCsv,
  }
})
