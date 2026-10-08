import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface VerificationReglementaire {
  id: number
  equipement_id: number
  type_verification: string
  organisme_agree: string
  numero_rapport?: string
  date_verification: string
  date_prochaine_verification: string
  resultat: 'conforme' | 'non_conforme' | 'reserve'
  observations?: string
  reserves?: string
  document_path?: string
  cout?: number
  equipement?: any
  created_at: string
  updated_at: string
}

export const useVgpStore = defineStore('vgp', () => {
  const verifications = ref<VerificationReglementaire[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchVerifications (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/verifications-reglementaires', { params: filters })
      verifications.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement vérifications'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createVerification (formData: FormData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/verifications-reglementaires', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      verifications.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création vérification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateVerification (id: number, formData: FormData) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post(`/verifications-reglementaires/${id}?_method=PUT`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      const index = verifications.value.findIndex(v => v.id === id)
      if (index !== -1) {
        verifications.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification vérification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteVerification (id: number) {
    loading.value = true
    error.value = null
    try {
      await httpClient.delete(`/verifications-reglementaires/${id}`)
      verifications.value = verifications.value.filter(v => v.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur suppression vérification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAlertes (days = 30) {
    try {
      const { data } = await httpClient.get('/verifications-reglementaires/alertes', { params: { days } })
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement alertes'
      throw error_
    }
  }

  async function fetchStats () {
    try {
      const { data } = await httpClient.get('/verifications-reglementaires/stats')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement stats'
      throw error_
    }
  }

  return {
    verifications,
    loading,
    error,
    fetchVerifications,
    createVerification,
    updateVerification,
    deleteVerification,
    fetchAlertes,
    fetchStats,
  }
})
