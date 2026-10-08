/**
 * useNonConformities Composable
 * Manages non-conformities state and operations
 */

import { computed, ref } from 'vue'
import {
  type AssignResponsibleDTO,
  type CreateNCDTO,
  type NCListParams,
  nonConformitiesService,
  type NonConformity,
  type UpdateNCDTO,
  type UpdateStatusDTO,
} from '@/api/services/nonconformities.service'
import { getErrorMessage } from '@/utils/errorMessage'

export function useNonConformities () {
  const ncs = ref<NonConformity[]>([])
  const currentNC = ref<NonConformity | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed filters
  const openNCs = computed(() => ncs.value.filter(nc => nc.status === 'open'))
  const closedNCs = computed(() => ncs.value.filter(nc => nc.status === 'closed'))
  const inProgressNCs = computed(() => ncs.value.filter(nc =>
    nc.status === 'analysis' || nc.status === 'corrective_action' || nc.status === 'verification',
  ))

  // Computed stats
  const stats = computed(() => {
    const now = new Date()
    const overdueNCs = ncs.value.filter(nc => {
      if (nc.status === 'closed' || !nc.due_date) {
        return false
      }
      return new Date(nc.due_date) < now
    })

    return {
      total: pagination.value.total,
      open: openNCs.value.length,
      inProgress: inProgressNCs.value.length,
      closed: closedNCs.value.length,
      overdue: overdueNCs.length,
    }
  })

  /**
   * Fetch non-conformities list
   */
  async function fetchNonConformities (params: NCListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await nonConformitiesService.getNonConformities(params)
      ncs.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de charger les non-conformités.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single non-conformity
   */
  async function fetchNonConformity (id: number) {
    loading.value = true
    error.value = null
    try {
      currentNC.value = await nonConformitiesService.getNonConformity(id)
      return currentNC.value
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de charger cette non-conformité.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new non-conformity
   */
  async function createNonConformity (data: CreateNCDTO) {
    loading.value = true
    error.value = null
    try {
      const newNC = await nonConformitiesService.createNonConformity(data)
      ncs.value.unshift(newNC)
      return newNC
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de créer la non-conformité.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update non-conformity
   */
  async function updateNonConformity (id: number, data: UpdateNCDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedNC = await nonConformitiesService.updateNonConformity(id, data)
      const index = ncs.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        ncs.value[index] = updatedNC
      }
      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }
      return updatedNC
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de mettre à jour la non-conformité.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete non-conformity
   */
  async function deleteNonConformity (id: number) {
    loading.value = true
    error.value = null
    try {
      await nonConformitiesService.deleteNonConformity(id)
      ncs.value = ncs.value.filter(nc => nc.id !== id)
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de supprimer la non-conformité.')
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
      const updatedNC = await nonConformitiesService.updateStatus(id, data)
      const index = ncs.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        ncs.value[index] = updatedNC
      }
      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }
      return updatedNC
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de mettre à jour le statut.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign responsible
   */
  async function assignResponsible (id: number, data: AssignResponsibleDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedNC = await nonConformitiesService.assignResponsible(id, data)
      const index = ncs.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        ncs.value[index] = updatedNC
      }
      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }
      return updatedNC
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible d’assigner le responsable.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    ncs,
    currentNC,
    loading,
    error,
    pagination,
    openNCs,
    closedNCs,
    inProgressNCs,
    stats,
    fetchNonConformities,
    fetchNonConformity,
    createNonConformity,
    updateNonConformity,
    deleteNonConformity,
    updateStatus,
    assignResponsible,
  }
}
