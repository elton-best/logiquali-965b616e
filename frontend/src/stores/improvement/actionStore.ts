import type { Action, ActionFilters, ActionStatistics } from '@/types/action'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import actionService from '@/services/improvement/actionService'

export const useActionStore = defineStore('action', () => {
  // State
  const actions = ref<Action[]>([])
  const currentAction = ref<Action | null>(null)
  const statistics = ref<ActionStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const overdueActions = computed(() =>
    actions.value.filter(a =>
      a.status !== 'completed'
      && a.deadline
      && new Date(a.deadline) < new Date(),
    ),
  )

  const pendingActions = computed(() =>
    actions.value.filter(a => a.status === 'pending' || a.status === 'in_progress'),
  )

  const completedActions = computed(() =>
    actions.value.filter(a => a.status === 'completed'),
  )

  const actionsByType = computed(() => {
    const grouped: Record<string, Action[]> = {
      corrective: [],
      preventive: [],
      improvement: [],
    }
    for (const action of actions.value) {
      if (action.type && grouped[action.type]) {
        const bucket = grouped[action.type]
        if (bucket) {
          bucket.push(action)
        }
      }
    }
    return grouped
  })

  // Actions
  async function fetchActions (filters?: ActionFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.getAll(filters)
      const data = (response as any)?.data ?? response
      actions.value = Array.isArray(data) ? data : []
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des actions'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAction (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.getById(id)
      const data = (response as any)?.data ?? response
      currentAction.value = data as Action
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createAction (data: Partial<Action>) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.create(data)
      const created = ((response as any)?.data ?? response) as Action
      actions.value.unshift(created)
      return created
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateAction (id: number, data: Partial<Action>) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.update(id, data)
      const updated = ((response as any)?.data ?? response) as Action
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updated
      }
      if (currentAction.value?.id === id) {
        currentAction.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteAction (id: number) {
    loading.value = true
    error.value = null
    try {
      await actionService.delete(id)
      actions.value = actions.value.filter(a => a.id !== id)
      if (currentAction.value?.id === id) {
        currentAction.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateProgress (id: number, progress: number, comment?: string) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.updateProgress(id, progress, comment)
      const updated = ((response as any)?.data ?? response) as Action
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de la progression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function assignAction (id: number, userId: number) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.assign(id, userId)
      const updated = ((response as any)?.data ?? response) as Action
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'assignation de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function completeAction (id: number, verification?: any) {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.complete(id, verification)
      const updated = ((response as any)?.data ?? response) as Action
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la finalisation de l\'action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    error.value = null
    try {
      const response = await actionService.getStatistics()
      const data = (response as any)?.data ?? response
      statistics.value = data as ActionStatistics
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    actions.value = []
    currentAction.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    actions,
    currentAction,
    statistics,
    loading,
    error,

    // Computed
    overdueActions,
    pendingActions,
    completedActions,
    actionsByType,

    // Actions
    fetchActions,
    fetchAction,
    createAction,
    updateAction,
    deleteAction,
    updateProgress,
    assignAction,
    completeAction,
    fetchStatistics,
    reset,
  }
})
