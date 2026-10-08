/**
 * Store Pinia pour le module Actions
 * Gestion centralisée de l'état des actions
 */

import type {
  Action,
  ActionFilters,
  ActionStatistics,
  CreateActionPayload,
  UpdateActionPayload,
  UpdateProgressPayload,
  VerifyEffectivenessPayload,
} from '@/types/action'
import type { ActionStatus, PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { actionService } from '@/services/actionService'

export const useActionStore = defineStore('action', () => {
  // ==================== STATE ====================

  const actions = ref<Action[]>([])
  const currentAction = ref<Action | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)

  // Statistics
  const statistics = ref<ActionStatistics | null>(null)

  // ==================== GETTERS ====================

  const actionsByStatus = computed(() => {
    return (status: ActionStatus) => actions.value.filter(a => a.status === status)
  })

  const overdueActions = computed(() => {
    const today = new Date().toISOString().split('T')[0] || ''
    return actions.value.filter(a =>
      a.status !== 'completed'
      && a.status !== 'cancelled'
      && a.deadline < today,
    )
  })

  const actionsCount = computed(() => actions.value.length)

  const completionRate = computed(() => {
    if (actions.value.length === 0) {
      return 0
    }
    const completed = actions.value.filter(a => a.status === 'completed').length
    return Math.round((completed / actions.value.length) * 100)
  })

  // ==================== ACTIONS ====================

  /**
   * Récupérer la liste des actions avec filtres
   */
  async function fetchActions (filters?: ActionFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await actionService.getAll(filters)
      actions.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des actions'
      console.error('[actionStore] fetchActions error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer une action par ID
   */
  async function fetchActionById (id: number) {
    loading.value = true
    error.value = null

    try {
      const action = await actionService.getById(id)
      currentAction.value = action

      // Mettre à jour dans la liste si elle existe
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = action
      }

      return action
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'action'
      console.error('[actionStore] fetchActionById error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Créer une nouvelle action
   */
  async function createAction (data: CreateActionPayload) {
    loading.value = true
    error.value = null

    try {
      const newAction = await actionService.create(data)
      actions.value.unshift(newAction)
      currentAction.value = newAction
      return newAction
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'action'
      console.error('[actionStore] createAction error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Mettre à jour une action
   */
  async function updateAction (id: number, data: UpdateActionPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAction = await actionService.update(id, data)

      // Mettre à jour dans la liste
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }

      // Mettre à jour current si c'est la même
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }

      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'action'
      console.error('[actionStore] updateAction error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Supprimer une action
   */
  async function deleteAction (id: number) {
    loading.value = true
    error.value = null

    try {
      await actionService.delete(id)

      // Retirer de la liste
      actions.value = actions.value.filter(a => a.id !== id)

      // Clear current si c'est la même
      if (currentAction.value?.id === id) {
        currentAction.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'action'
      console.error('[actionStore] deleteAction error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Mettre à jour la progression d'une action
   */
  async function updateProgress (id: number, data: UpdateProgressPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAction = await actionService.updateProgress(id, data)

      // Mettre à jour dans la liste
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }

      // Mettre à jour current si c'est la même
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }

      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de la progression'
      console.error('[actionStore] updateProgress error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Vérifier l'efficacité d'une action
   */
  async function verifyEffectiveness (id: number, data: VerifyEffectivenessPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAction = await actionService.verifyEffectiveness(id, data)

      // Mettre à jour dans la liste
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }

      // Mettre à jour current si c'est la même
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }

      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la vérification de l\'efficacité'
      console.error('[actionStore] verifyEffectiveness error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer les statistiques
   */
  async function fetchStatistics (filters?: Partial<ActionFilters>) {
    loading.value = true
    error.value = null

    try {
      const stats = await actionService.getStatistics(filters)
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      console.error('[actionStore] fetchStatistics error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Exporter les actions en Excel
   */
  async function exportExcel (filters?: ActionFilters) {
    loading.value = true
    error.value = null

    try {
      const blob = await actionService.exportExcel(filters)

      // Télécharger le fichier
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `actions_${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'export Excel'
      console.error('[actionStore] exportExcel error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Réinitialiser l'état
   */
  function reset () {
    actions.value = []
    currentAction.value = null
    meta.value = null
    links.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  /**
   * Clear current action
   */
  function clearCurrent () {
    currentAction.value = null
  }

  // ==================== RETURN ====================

  return {
    // State
    actions,
    currentAction,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    actionsByStatus,
    overdueActions,
    actionsCount,
    completionRate,

    // Actions
    fetchActions,
    fetchActionById,
    createAction,
    updateAction,
    deleteAction,
    updateProgress,
    verifyEffectiveness,
    fetchStatistics,
    exportExcel,
    reset,
    clearCurrent,
  }
})
