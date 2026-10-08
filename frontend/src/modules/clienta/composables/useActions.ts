/**
 * useActions Composable
 * Manages actions (corrective/preventive) state and operations
 */

import { computed, ref } from 'vue'
import {
  type Action,
  type ActionListParams,
  actionsService,
  type CreateActionDTO,
  type UpdateActionDTO,
  type UpdateProgressDTO,
  type UpdateStatusDTO,
  type VerifyEffectivenessDTO,
} from '@/api/services/actions.service'

export function useActions () {
  const actions = ref<Action[]>([])
  const currentAction = ref<Action | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed filters
  const overdueActions = computed(() => {
    const now = new Date()
    return actions.value.filter(action => {
      if (action.status === 'completed' || action.status === 'verified'
        || action.status === 'closed' || action.status === 'cancelled' || !action.due_date) {
        return false
      }
      return new Date(action.due_date) < now
    })
  })

  const inProgressActions = computed(() =>
    actions.value.filter(action => action.status === 'in_progress'),
  )

  const completedActions = computed(() =>
    actions.value.filter(action =>
      action.status === 'completed'
      || action.status === 'verified'
      || action.status === 'closed',
    ),
  )

  const assignedActions = computed(() =>
    actions.value.filter(action => action.status === 'assigned'),
  )

  const draftActions = computed(() =>
    actions.value.filter(action => action.status === 'draft'),
  )

  // Priority-based filters
  const urgentActions = computed(() =>
    actions.value.filter(action => action.priority === 'urgent'),
  )

  const highPriorityActions = computed(() =>
    actions.value.filter(action => action.priority === 'high'),
  )

  const mediumPriorityActions = computed(() =>
    actions.value.filter(action => action.priority === 'medium'),
  )

  const lowPriorityActions = computed(() =>
    actions.value.filter(action => action.priority === 'low'),
  )

  // Type-based filters
  const correctiveActions = computed(() =>
    actions.value.filter(action => action.type === 'corrective'),
  )

  const preventiveActions = computed(() =>
    actions.value.filter(action => action.type === 'preventive'),
  )

  // Computed stats
  const stats = computed(() => {
    return {
      total: pagination.value.total,
      draft: draftActions.value.length,
      assigned: assignedActions.value.length,
      inProgress: inProgressActions.value.length,
      completed: completedActions.value.length,
      overdue: overdueActions.value.length,
      urgent: urgentActions.value.length,
      highPriority: highPriorityActions.value.length,
      corrective: correctiveActions.value.length,
      preventive: preventiveActions.value.length,
    }
  })

  /**
   * Fetch actions list
   */
  async function fetchActions (params: ActionListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await actionsService.getActions(params)
      actions.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch actions'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single action
   */
  async function fetchAction (id: number) {
    loading.value = true
    error.value = null
    try {
      currentAction.value = await actionsService.getAction(id)
      return currentAction.value
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new action
   */
  async function createAction (data: CreateActionDTO) {
    loading.value = true
    error.value = null
    try {
      const newAction = await actionsService.createAction(data)
      actions.value.unshift(newAction)
      return newAction
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update action
   */
  async function updateAction (id: number, data: UpdateActionDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedAction = await actionsService.updateAction(id, data)
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }
      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete action
   */
  async function deleteAction (id: number) {
    loading.value = true
    error.value = null
    try {
      await actionsService.deleteAction(id)
      actions.value = actions.value.filter(a => a.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete action'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update progress
   */
  async function updateProgress (id: number, data: UpdateProgressDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedAction = await actionsService.updateProgress(id, data)
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }
      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update progress'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update status
   */
  async function updateStatus (id: number, data: UpdateStatusDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedAction = await actionsService.updateStatus(id, data)
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }
      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update status'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Verify effectiveness
   */
  async function verifyEffectiveness (id: number, data: VerifyEffectivenessDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedAction = await actionsService.verifyEffectiveness(id, data)
      const index = actions.value.findIndex(a => a.id === id)
      if (index !== -1) {
        actions.value[index] = updatedAction
      }
      if (currentAction.value?.id === id) {
        currentAction.value = updatedAction
      }
      return updatedAction
    } catch (error_: any) {
      error.value = error_.message || 'Failed to verify effectiveness'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    actions,
    currentAction,
    loading,
    error,
    pagination,
    overdueActions,
    inProgressActions,
    completedActions,
    assignedActions,
    draftActions,
    urgentActions,
    highPriorityActions,
    mediumPriorityActions,
    lowPriorityActions,
    correctiveActions,
    preventiveActions,
    stats,
    fetchActions,
    fetchAction,
    createAction,
    updateAction,
    deleteAction,
    updateProgress,
    updateStatus,
    verifyEffectiveness,
  }
}
