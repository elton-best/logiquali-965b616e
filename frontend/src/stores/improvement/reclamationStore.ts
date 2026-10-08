import type { Reclamation, ReclamationFilters, ReclamationStatistics } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import reclamationService from '@/services/improvement/reclamationService'

export const useReclamationStore = defineStore('reclamation', () => {
  // State
  const reclamations = ref<Reclamation[]>([])
  const currentReclamation = ref<Reclamation | null>(null)
  const statistics = ref<ReclamationStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const pendingReclamations = computed(() =>
    reclamations.value.filter(r => r.status === 'pending' || r.status === 'in_progress'),
  )

  const closedReclamations = computed(() =>
    reclamations.value.filter(r => r.status === 'closed'),
  )

  const criticalReclamations = computed(() =>
    reclamations.value.filter(r => r.severity === 'critical' && r.status !== 'closed'),
  )

  const reclamationsBySource = computed(() => {
    const grouped: Record<string, Reclamation[]> = {}
    for (const rec of reclamations.value) {
      const source = rec.source || 'other'
      if (!grouped[source]) {
        grouped[source] = []
      }
      grouped[source].push(rec)
    }
    return grouped
  })

  // Actions
  async function fetchReclamations (filters?: ReclamationFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.getAll(filters)
      reclamations.value = response.data.data
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des réclamations'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchReclamation (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.getById(id)
      currentReclamation.value = response.data.data
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createReclamation (data: Partial<Reclamation>) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.create(data)
      reclamations.value.unshift(response.data.data)
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateReclamation (id: number, data: Partial<Reclamation>) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.update(id, data)
      const index = reclamations.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reclamations.value[index] = response.data.data
      }
      if (currentReclamation.value?.id === id) {
        currentReclamation.value = response.data.data
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteReclamation (id: number) {
    loading.value = true
    error.value = null
    try {
      await reclamationService.delete(id)
      reclamations.value = reclamations.value.filter(r => r.id !== id)
      if (currentReclamation.value?.id === id) {
        currentReclamation.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function assignReclamation (id: number, userId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.assign(id, userId)
      const index = reclamations.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reclamations.value[index] = response.data.data
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'assignation de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function respondReclamation (id: number, response: string) {
    loading.value = true
    error.value = null
    try {
      const result = await reclamationService.respond(id, response)
      const index = reclamations.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reclamations.value[index] = result.data.data
      }
      return result.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la réponse à la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function closeReclamation (id: number, satisfaction?: number) {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.close(id, satisfaction)
      const index = reclamations.value.findIndex(r => r.id === id)
      if (index !== -1) {
        reclamations.value[index] = response.data.data
      }
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la clôture de la réclamation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    error.value = null
    try {
      const response = await reclamationService.getStatistics()
      statistics.value = response.data.data
      return response.data.data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    reclamations.value = []
    currentReclamation.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    reclamations,
    currentReclamation,
    statistics,
    loading,
    error,

    // Computed
    pendingReclamations,
    closedReclamations,
    criticalReclamations,
    reclamationsBySource,

    // Actions
    fetchReclamations,
    fetchReclamation,
    createReclamation,
    updateReclamation,
    deleteReclamation,
    assignReclamation,
    respondReclamation,
    closeReclamation,
    fetchStatistics,
    reset,
  }
})
