/**
 * useProcesses Composable
 * Manages processes state and operations
 */

import { computed, ref } from 'vue'
import {
  type CreateProcessDTO,
  type Process,
  type ProcessesListParams,
  processesService,
  type UpdateInteractionsDTO,
  type UpdateProcessDTO,
} from '@/api/services/processes.service'

export function useProcesses () {
  const processes = ref<Process[]>([])
  const currentProcess = ref<Process | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const activeProcesses = computed(() => processes.value.filter(p => p.status === 'active'))

  const processesByCategory = computed(() => ({
    realisation: processes.value.filter(p => p.category === 'realisation'),
    support: processes.value.filter(p => p.category === 'support'),
    management: processes.value.filter(p => p.category === 'management'),
  }))

  const stats = computed(() => ({
    total: processes.value.length,
    active: activeProcesses.value.length,
    byCategory: {
      realisation: processesByCategory.value.realisation.length,
      support: processesByCategory.value.support.length,
      management: processesByCategory.value.management.length,
    },
    byStatus: {
      draft: processes.value.filter(p => p.status === 'draft').length,
      active: processes.value.filter(p => p.status === 'active').length,
      under_review: processes.value.filter(p => p.status === 'under_review').length,
      archived: processes.value.filter(p => p.status === 'archived').length,
    },
  }))

  /**
   * Fetch processes list
   */
  async function fetchProcesses (params: ProcessesListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await processesService.getProcesses(params)
      processes.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch processes'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single process
   */
  async function fetchProcess (id: number) {
    loading.value = true
    error.value = null
    try {
      currentProcess.value = await processesService.getProcess(id)
      return currentProcess.value
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch process'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new process
   */
  async function createProcess (data: CreateProcessDTO) {
    loading.value = true
    error.value = null
    try {
      const newProcess = await processesService.createProcess(data)
      processes.value.unshift(newProcess)
      return newProcess
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create process'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update process
   */
  async function updateProcess (id: number, data: UpdateProcessDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedProcess = await processesService.updateProcess(id, data)
      const index = processes.value.findIndex(p => p.id === id)
      if (index !== -1) {
        processes.value[index] = updatedProcess
      }
      if (currentProcess.value?.id === id) {
        currentProcess.value = updatedProcess
      }
      return updatedProcess
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update process'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete process
   */
  async function deleteProcess (id: number) {
    loading.value = true
    error.value = null
    try {
      await processesService.deleteProcess(id)
      processes.value = processes.value.filter(p => p.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete process'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create process map
   */
  async function createProcessMap () {
    loading.value = true
    error.value = null
    try {
      return await processesService.createProcessMap()
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create process map'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update interactions
   */
  async function updateInteractions (id: number, data: UpdateInteractionsDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedProcess = await processesService.updateInteractions(id, data)
      const index = processes.value.findIndex(p => p.id === id)
      if (index !== -1) {
        processes.value[index] = updatedProcess
      }
      if (currentProcess.value?.id === id) {
        currentProcess.value = updatedProcess
      }
      return updatedProcess
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update interactions'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Archive process
   */
  async function archiveProcess (id: number) {
    loading.value = true
    error.value = null
    try {
      const archivedProcess = await processesService.archiveProcess(id)
      const index = processes.value.findIndex(p => p.id === id)
      if (index !== -1) {
        processes.value[index] = archivedProcess
      }
      if (currentProcess.value?.id === id) {
        currentProcess.value = archivedProcess
      }
      return archivedProcess
    } catch (error_: any) {
      error.value = error_.message || 'Failed to archive process'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    processes,
    currentProcess,
    loading,
    error,
    pagination,
    activeProcesses,
    processesByCategory,
    stats,
    fetchProcesses,
    fetchProcess,
    createProcess,
    updateProcess,
    deleteProcess,
    createProcessMap,
    updateInteractions,
    archiveProcess,
  }
}
