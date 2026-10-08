import type {
  Process,
  ProcessFilters,
  ProcessIndicator,
  ProcessRiskOpportunity,
} from '@/services/processService'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import processService from '@/services/processService'
import processWorkflowService from '@/services/processWorkflowService'

const toast = useToast()

export const useProcessStore = defineStore('process', () => {
  // State
  const processes = ref<Process[]>([])
  const currentProcess = ref<Process | null>(null)
  const loading = ref(false)
  const statistics = ref<any>(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })
  const filters = ref<ProcessFilters>({})
  const systemSettings = ref({
    is_smi: false,
    enabled_norms: ['9001'],
  })
  let fetchProcessesPromise: Promise<void> | null = null

  // Getters
  const processesByCategory = computed(() => {
    return {
      pilotage: processes.value.filter(p => p.category === 'pilotage'),
      operationnel: processes.value.filter(
        p => p.category === 'operationnel',
      ),
      support: processes.value.filter(p => p.category === 'support'),
      mesure_amelioration: processes.value.filter(
        p => p.category === 'mesure_amelioration',
      ),
    }
  })

  const activeProcesses = computed(() => {
    return processes.value.filter(p => p.status === 'active')
  })

  const drafts = computed(() => {
    return processes.value.filter(p => p.status === 'draft')
  })

  const inReview = computed(() => {
    return processes.value.filter(p => p.status === 'in_review')
  })

  const validated = computed(() => {
    return processes.value.filter(p => p.status === 'validated')
  })

  // Actions
  async function fetchProcesses (page = 1) {
    if (fetchProcessesPromise) {
      return fetchProcessesPromise
    }

    loading.value = true
    fetchProcessesPromise = (async () => {
      try {
        const response = await processService.getProcesses(
          filters.value,
          page,
          pagination.value.per_page,
        )
        processes.value = response.data
        pagination.value = {
          current_page: response.meta?.current_page || 1,
          last_page: response.meta?.last_page || 1,
          per_page: response.meta?.per_page || 20,
          total: response.meta?.total || 0,
        }
      } catch (error: any) {
        toast.error(
          error.response?.data?.message
          || 'Erreur lors du chargement des processus',
        )
      } finally {
        loading.value = false
        fetchProcessesPromise = null
      }
    })()

    return fetchProcessesPromise
  }

  async function fetchProcess (id: number) {
    loading.value = true
    try {
      const response = await processService.getProcess(id)
      currentProcess.value = response.data
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message
        || 'Erreur lors du chargement du processus',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createProcess (data: Partial<Process>) {
    loading.value = true
    try {
      const response = await processService.createProcess(data)
      processes.value.unshift(response.data)
      toast.success('Processus créé avec succès')
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message
        || 'Erreur lors de la création du processus',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateProcess (id: number, data: Partial<Process>) {
    loading.value = true
    try {
      const response = await processService.updateProcess(id, data)
      const index = processes.value.findIndex(p => p.id === id)
      if (index !== -1) {
        processes.value[index] = response.data
      }
      if (currentProcess.value?.id === id) {
        currentProcess.value = response.data
      }
      toast.success('Processus mis à jour')
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur lors de la mise à jour',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteProcess (id: number) {
    loading.value = true
    try {
      await processService.deleteProcess(id)
      processes.value = processes.value.filter(p => p.id !== id)
      toast.success('Processus supprimé')
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur lors de la suppression',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function addIndicator (
    processId: number,
    data: Partial<ProcessIndicator>,
  ) {
    try {
      const response = await processService.addIndicator(processId, data)
      toast.success('Indicateur ajouté')
      await fetchProcess(processId) // Refresh
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message
        || 'Erreur lors de l\'ajout de l\'indicateur',
      )
      throw error
    }
  }

  async function addRiskOpportunity (
    processId: number,
    data: Partial<ProcessRiskOpportunity>,
  ) {
    try {
      const response = await processService.addRiskOpportunity(processId, data)
      toast.success(
        data.type === 'risque' ? 'Risque ajouté' : 'Opportunité ajoutée',
      )
      await fetchProcess(processId) // Refresh
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout')
      throw error
    }
  }

  async function fetchSystemSettings (siteId?: number) {
    try {
      const response = await processService.getSystemSettings(siteId)
      systemSettings.value = response.data
    } catch (error: any) {
      console.error('Erreur chargement settings:', error)
    }
  }

  // === SEQUENCES ===
  async function addSequence (processId: number, data: any) {
    try {
      const response = await processService.addSequence(processId, data)
      toast.success('Séquence ajoutée')
      await fetchProcess(processId)
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur ajout séquence')
      throw error
    }
  }

  async function updateSequence (
    processId: number,
    sequenceId: number,
    data: any,
  ) {
    try {
      const response = await processService.updateSequence(
        processId,
        sequenceId,
        data,
      )
      toast.success('Séquence modifiée')
      await fetchProcess(processId)
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur modification séquence',
      )
      throw error
    }
  }

  async function deleteSequence (processId: number, sequenceId: number) {
    try {
      await processService.deleteSequence(processId, sequenceId)
      toast.success('Séquence supprimée')
      await fetchProcess(processId)
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur suppression séquence',
      )
      throw error
    }
  }

  // === VERSIONS ===
  async function fetchVersions (processId: number) {
    try {
      const response = await processService.getVersions(processId)
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur chargement versions',
      )
      throw error
    }
  }

  async function createVersion (
    processId: number,
    data: { changes_description: string },
  ) {
    try {
      const response = await processService.createVersion(processId, data)
      toast.success('Version créée')
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur création version')
      throw error
    }
  }

  async function verifyVersion (processId: number, versionId: number) {
    try {
      const response = await processService.verifyVersion(processId, versionId)
      toast.success('Version vérifiée')
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur vérification')
      throw error
    }
  }

  async function approveVersion (processId: number, versionId: number) {
    try {
      const response = await processService.approveVersion(
        processId,
        versionId,
      )
      toast.success('Version approuvée')
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur approbation')
      throw error
    }
  }

  // === OBJECTIVES ===
  async function addObjective (processId: number, data: any) {
    try {
      const response = await processService.addObjective(processId, data)
      toast.success('Objectif ajouté')
      await fetchProcess(processId)
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur ajout objectif')
      throw error
    }
  }

  async function updateObjective (
    processId: number,
    objectiveId: number,
    data: any,
  ) {
    try {
      const response = await processService.updateObjective(
        processId,
        objectiveId,
        data,
      )
      toast.success('Objectif modifié')
      await fetchProcess(processId)
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur modification objectif',
      )
      throw error
    }
  }

  async function deleteObjective (processId: number, objectiveId: number) {
    try {
      await processService.deleteObjective(processId, objectiveId)
      toast.success('Objectif supprimé')
      await fetchProcess(processId)
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur suppression objectif',
      )
      throw error
    }
  }

  function setFilters (newFilters: ProcessFilters) {
    filters.value = { ...newFilters }
  }

  function resetFilters () {
    filters.value = {}
  }

  function clearCurrentProcess () {
    currentProcess.value = null
  }

  // Workflow Actions
  async function verifyProcess (processId: number, comment?: string) {
    loading.value = true
    try {
      const response = await processWorkflowService.verify(processId, {
        comment,
      })

      // Update process in list
      const index = processes.value.findIndex(p => p.id === processId)
      if (index !== -1) {
        processes.value[index] = response.data
      }

      // Update current process
      if (currentProcess.value?.id === processId) {
        currentProcess.value = response.data
      }

      toast.success(response.message || 'Processus vérifié avec succès')
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur lors de la vérification',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function validateProcess (processId: number, comment?: string) {
    loading.value = true
    try {
      const response = await processWorkflowService.validate(processId, {
        comment,
      })

      // Update process in list
      const index = processes.value.findIndex(p => p.id === processId)
      if (index !== -1) {
        processes.value[index] = response.data
      }

      // Update current process
      if (currentProcess.value?.id === processId) {
        currentProcess.value = response.data
      }

      toast.success(response.message || 'Processus validé et activé')
      return response.data
    } catch (error: any) {
      toast.error(
        error.response?.data?.message || 'Erreur lors de la validation',
      )
      throw error
    } finally {
      loading.value = false
    }
  }

  async function rejectProcess (processId: number, reason: string) {
    loading.value = true
    try {
      const response = await processWorkflowService.reject(processId, {
        reason,
      })

      // Update process in list
      const index = processes.value.findIndex(p => p.id === processId)
      if (index !== -1) {
        processes.value[index] = response.data
      }

      // Update current process
      if (currentProcess.value?.id === processId) {
        currentProcess.value = response.data
      }

      toast.success(response.message || 'Processus rejeté')
      return response.data
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors du rejet')
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics (siteId?: number) {
    try {
      const stats = await processWorkflowService.getStatistics(siteId)
      statistics.value = stats
      return stats
    } catch (error: any) {
      toast.error(
        error.response?.data?.message
        || 'Erreur lors du chargement des statistiques',
      )
      throw error
    }
  }

  return {
    // State
    processes,
    currentProcess,
    loading,
    statistics,
    pagination,
    filters,
    systemSettings,
    // Getters
    processesByCategory,
    activeProcesses,
    drafts,
    // Actions
    fetchProcesses,
    fetchProcess,
    createProcess,
    updateProcess,
    deleteProcess,
    addIndicator,
    addRiskOpportunity,
    fetchSystemSettings,
    addSequence,
    updateSequence,
    deleteSequence,
    fetchVersions,
    createVersion,
    verifyVersion,
    approveVersion,
    addObjective,
    updateObjective,
    deleteObjective,
    setFilters,
    resetFilters,
    clearCurrentProcess,

    // Workflow Actions
    verifyProcess,
    validateProcess,
    rejectProcess,
    fetchStatistics,

    // Workflow getters
    inReview,
    validated,
  }
})
