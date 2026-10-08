import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface Ipe {
  id: number
  enterprise_id: number
  site_id: number
  nom: string
  description?: string
  formule_calcul: string
  unite: string
  valeur_reference?: number
  objectif_cible?: number
  date_reference?: string
  periodicite: string
  actif: boolean
  site?: any
}

export interface IpeValeur {
  id: number
  ipe_id: number
  periode_debut: string
  periode_fin: string
  valeur: number
  ecart_reference?: number
  ecart_objectif?: number
  commentaire?: string
}

export const useIpeStore = defineStore('ipe', () => {
  const ipes = ref<Ipe[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchIpes (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/ipe', { params: filters })
      ipes.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createIpe (ipe: Partial<Ipe>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/ipe', ipe)
      ipes.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateIpe (id: number, ipe: Partial<Ipe>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.put(`/ipe/${id}`, ipe)
      const index = ipes.value.findIndex(i => i.id === id)
      if (index !== -1) {
        ipes.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteIpe (id: number) {
    loading.value = true
    error.value = null
    try {
      await httpClient.delete(`/ipe/${id}`)
      ipes.value = ipes.value.filter(i => i.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function addValeur (ipeId: number, valeur: Partial<IpeValeur>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post(`/ipe/${ipeId}/valeurs`, valeur)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur ajout valeur'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchValeurs (ipeId: number) {
    try {
      const { data } = await httpClient.get(`/ipe/${ipeId}/valeurs`)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement valeurs'
      throw error_
    }
  }

  async function fetchTendance (ipeId: number) {
    try {
      const { data } = await httpClient.get(`/ipe/${ipeId}/tendance`)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur tendance'
      throw error_
    }
  }

  async function fetchStats () {
    try {
      const { data } = await httpClient.get('/ipe/stats')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur stats'
      throw error_
    }
  }

  return {
    ipes,
    loading,
    error,
    fetchIpes,
    createIpe,
    updateIpe,
    deleteIpe,
    addValeur,
    fetchValeurs,
    fetchTendance,
    fetchStats,
  }
})
