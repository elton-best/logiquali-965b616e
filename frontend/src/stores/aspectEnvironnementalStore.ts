import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface AspectEnvironnemental {
  id: number
  enterprise_id: number
  site_id: number
  equipement_id?: number
  process_id?: number
  type: string
  designation: string
  description?: string
  condition: 'normale' | 'anormale' | 'urgence'
  gravite: number
  frequence: number
  detectabilite: number
  criticite: number
  aspect_significatif: boolean
  mesures_maitrise?: string
  objectifs_amelioration?: string
  equipement?: any
  process?: any
  site?: any
}

export const useAspectEnvironnementalStore = defineStore('aspectEnvironnemental', () => {
  const aspects = ref<AspectEnvironnemental[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchAspects (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/aspects-environnementaux', { params: filters })
      aspects.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createAspect (aspect: Partial<AspectEnvironnemental>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/aspects-environnementaux', aspect)
      aspects.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateAspect (id: number, aspect: Partial<AspectEnvironnemental>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.put(`/aspects-environnementaux/${id}`, aspect)
      const index = aspects.value.findIndex(a => a.id === id)
      if (index !== -1) {
        aspects.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteAspect (id: number) {
    loading.value = true
    error.value = null
    try {
      await httpClient.delete(`/aspects-environnementaux/${id}`)
      aspects.value = aspects.value.filter(a => a.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStats () {
    try {
      const { data } = await httpClient.get('/aspects-environnementaux/stats')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur stats'
      throw error_
    }
  }

  async function fetchMatrice () {
    try {
      const { data } = await httpClient.get('/aspects-environnementaux/matrice')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur matrice'
      throw error_
    }
  }

  return {
    aspects,
    loading,
    error,
    fetchAspects,
    createAspect,
    updateAspect,
    deleteAspect,
    fetchStats,
    fetchMatrice,
  }
})
