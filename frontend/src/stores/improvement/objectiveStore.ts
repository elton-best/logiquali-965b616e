import type { Objective } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import objectiveService from '@/services/improvement/objectiveService'

type ObjectiveFilters = Record<string, any>
type ObjectiveStatistics = Record<string, any>

export const useObjectiveStore = defineStore('objective', () => {
  // State
  const objectives = ref<Objective[]>([])
  const currentObjective = ref<Objective | null>(null)
  const statistics = ref<ObjectiveStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const activeObjectives = computed(() =>
    objectives.value.filter(o => o.status === 'active'),
  )

  const completedObjectives = computed(() =>
    objectives.value.filter(o => o.status === 'achieved'),
  )

  const atRiskObjectives = computed(() =>
    objectives.value.filter(o =>
      o.status === 'active'
      && o.progress < 50
      && o.deadline
      && new Date(o.deadline) < new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
    ),
  )

  const objectivesByAxe = computed(() => {
    const grouped: Record<string, Objective[]> = {}
    for (const obj of objectives.value) {
      if (obj.axes) {
        for (const axe of obj.axes) {
          const key = axe.code
          if (!grouped[key]) {
            grouped[key] = []
          }
          grouped[key].push(obj)
        }
      }
    }
    return grouped
  })

  // Actions
  async function fetchObjectives (filters?: ObjectiveFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.getAll(filters)
      const data = (response as any)?.data ?? response
      objectives.value = Array.isArray(data) ? data : []
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des objectifs'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchObjective (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.getById(id)
      const data = (response as any)?.data ?? response
      currentObjective.value = data as Objective
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'objectif'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createObjective (data: Partial<Objective>) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.create(data)
      const created = ((response as any)?.data ?? response) as Objective
      objectives.value.unshift(created)
      return created
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'objectif'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateObjective (id: number, data: Partial<Objective>) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.update(id, data)
      const updated = ((response as any)?.data ?? response) as Objective
      const index = objectives.value.findIndex(o => o.id === id)
      if (index !== -1) {
        objectives.value[index] = updated
      }
      if (currentObjective.value?.id === id) {
        currentObjective.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'objectif'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteObjective (id: number) {
    loading.value = true
    error.value = null
    try {
      await objectiveService.delete(id)
      objectives.value = objectives.value.filter(o => o.id !== id)
      if (currentObjective.value?.id === id) {
        currentObjective.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'objectif'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateProgress (id: number, progress: number, comment?: string) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.updateProgress(id, progress, comment)
      const updated = ((response as any)?.data ?? response) as Objective
      const index = objectives.value.findIndex(o => o.id === id)
      if (index !== -1) {
        objectives.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de la progression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function linkIndicator (objectiveId: number, indicatorId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.linkIndicator(objectiveId, indicatorId)
      const updated = ((response as any)?.data ?? response) as Objective
      const index = objectives.value.findIndex(o => o.id === objectiveId)
      if (index !== -1) {
        objectives.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la liaison de l\'indicateur'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    error.value = null
    try {
      const response = await objectiveService.getStatistics()
      const data = (response as any)?.data ?? response
      statistics.value = data as ObjectiveStatistics
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    objectives.value = []
    currentObjective.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    objectives,
    currentObjective,
    statistics,
    loading,
    error,

    // Computed
    activeObjectives,
    completedObjectives,
    atRiskObjectives,
    objectivesByAxe,

    // Actions
    fetchObjectives,
    fetchObjective,
    createObjective,
    updateObjective,
    deleteObjective,
    updateProgress,
    linkIndicator,
    fetchStatistics,
    reset,
  }
})
