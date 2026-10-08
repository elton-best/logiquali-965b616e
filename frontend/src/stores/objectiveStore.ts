/**
 * Objective Store
 * Pinia store for managing QHSE objectives (SMART goals)
 */

import type {
  CreateObjectivePayload,
  Objective,
  ObjectiveFilters,
  ObjectiveStatistics,
  UpdateObjectivePayload,
} from '@/types/indicator'
import type { PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { indicatorService } from '@/services/indicatorService'

export const useObjectiveStore = defineStore('objective', () => {
  // ============================================
  // STATE
  // ============================================
  const objectives = ref<Objective[]>([])
  const currentObjective = ref<Objective | null>(null)

  const loading = ref(false)
  const error = ref<string | null>(null)

  const meta = ref<PaginationMeta>({
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 15,
    to: 0,
    total: 0,
  })

  const links = ref<PaginationLinks>({
    first: null,
    last: null,
    prev: null,
    next: null,
  })

  const statistics = ref<ObjectiveStatistics | null>(null)

  // ============================================
  // GETTERS
  // ============================================

  const activeObjectives = computed(() =>
    objectives.value.filter(o => o.status === 'active'),
  )

  const achievedObjectives = computed(() =>
    objectives.value.filter(o => o.status === 'achieved'),
  )

  const overdueObjectives = computed(() => {
    const now = new Date()
    return objectives.value.filter(o =>
      o.status === 'active'
      && new Date(o.target_date) < now,
    )
  })

  const objectivesByCategory = computed(() => {
    return (category: string) =>
      objectives.value.filter(o => o.category === category)
  })

  const objectivesByPriority = computed(() => {
    return (priority: string) =>
      objectives.value.filter(o => o.priority === priority)
  })

  const onTrackObjectives = computed(() =>
    objectives.value.filter(o =>
      o.status === 'active'
      && o.achievement_rate !== undefined
      && o.achievement_rate >= 75,
    ),
  )

  const atRiskObjectives = computed(() =>
    objectives.value.filter(o =>
      o.status === 'active'
      && o.achievement_rate !== undefined
      && o.achievement_rate < 75
      && o.achievement_rate >= 50,
    ),
  )

  // ============================================
  // ACTIONS
  // ============================================

  async function fetchObjectives (filters?: ObjectiveFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await indicatorService.getAllObjectives(filters)
      objectives.value = response.data
      meta.value = response.meta
      links.value = response.links
      if (response.statistics) {
        statistics.value = response.statistics
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des objectifs'
      console.error('[ObjectiveStore] Fetch error:', error_)
    } finally {
      loading.value = false
    }
  }

  async function fetchObjectiveById (id: number) {
    loading.value = true
    error.value = null

    try {
      currentObjective.value = await indicatorService.getObjectiveById(id)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'objectif'
      console.error('[ObjectiveStore] Fetch by ID error:', error_)
    } finally {
      loading.value = false
    }
  }

  async function createObjective (payload: CreateObjectivePayload) {
    loading.value = true
    error.value = null

    try {
      const newObjective = await indicatorService.createObjective(payload)
      objectives.value.unshift(newObjective)
      currentObjective.value = newObjective
      return newObjective
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'objectif'
      console.error('[ObjectiveStore] Create error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateObjective (id: number, payload: UpdateObjectivePayload) {
    loading.value = true
    error.value = null

    try {
      const updated = await indicatorService.updateObjective(id, payload)

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
      console.error('[ObjectiveStore] Update error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteObjective (id: number) {
    loading.value = true
    error.value = null

    try {
      await indicatorService.deleteObjective(id)
      objectives.value = objectives.value.filter(o => o.id !== id)

      if (currentObjective.value?.id === id) {
        currentObjective.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'objectif'
      console.error('[ObjectiveStore] Delete error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function achieveObjective (id: number, achievedDate?: string) {
    loading.value = true
    error.value = null

    try {
      const updated = await indicatorService.achieveObjective(id, achievedDate)

      const index = objectives.value.findIndex(o => o.id === id)
      if (index !== -1) {
        objectives.value[index] = updated
      }

      if (currentObjective.value?.id === id) {
        currentObjective.value = updated
      }

      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la clôture de l\'objectif'
      console.error('[ObjectiveStore] Achieve error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateMilestone (
    objectiveId: number,
    milestoneId: number,
    data: Partial<{ achieved: boolean, achieved_date: string, note: string }>,
  ) {
    try {
      const updated = await indicatorService.updateMilestone(objectiveId, milestoneId, data)

      const index = objectives.value.findIndex(o => o.id === objectiveId)
      if (index !== -1) {
        objectives.value[index] = updated
      }

      if (currentObjective.value?.id === objectiveId) {
        currentObjective.value = updated
      }

      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    }
  }

  async function fetchStatistics () {
    try {
      statistics.value = await indicatorService.getObjectiveStatistics()
    } catch (error_: any) {
      error.value = error_.message
      console.error('[ObjectiveStore] Fetch statistics error:', error_)
    }
  }

  // ============================================
  // UTILITIES
  // ============================================

  function clearError () {
    error.value = null
  }

  function resetState () {
    objectives.value = []
    currentObjective.value = null
    error.value = null
    statistics.value = null
  }

  return {
    // State
    objectives,
    currentObjective,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    activeObjectives,
    achievedObjectives,
    overdueObjectives,
    objectivesByCategory,
    objectivesByPriority,
    onTrackObjectives,
    atRiskObjectives,

    // Actions
    fetchObjectives,
    fetchObjectiveById,
    createObjective,
    updateObjective,
    deleteObjective,
    achieveObjective,
    updateMilestone,
    fetchStatistics,
    clearError,
    resetState,
  }
})
