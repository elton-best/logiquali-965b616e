import type { AnalyzeNonConformityDto, CreateNonConformityDto, NonConformity } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { nonConformityService } from '@/services/improvement/nonConformityService'

export const useNonConformityStore = defineStore('nonConformity', () => {
  // State
  const nonConformities = ref<NonConformity[]>([])
  const currentNc = ref<NonConformity | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Filters state
  const filters = ref({
    site_id: null as number | null,
    severity: null as string | null,
    status: null as string | null,
    axes: [] as number[],
    search: '',
  })

  // Getters
  const byStatus = computed(() => {
    return (status: string) => nonConformities.value.filter(nc =>
      nc.workflow_state?.slug === status,
    )
  })

  const bySeverity = computed(() => {
    return (severity: 'minor' | 'major' | 'critical') =>
      nonConformities.value.filter(nc => nc.severity === severity)
  })

  const byAxe = computed(() => {
    return (axeId: number) => nonConformities.value.filter(nc =>
      nc.axes?.some(a => a.id === axeId),
    )
  })

  const pendingValidation = computed(() =>
    nonConformities.value.filter(nc =>
      nc.workflow_state?.slug === 'pending_validation' && !nc.is_validated,
    ),
  )

  const awaitingVerification = computed(() =>
    nonConformities.value.filter(nc =>
      nc.is_validated && !nc.is_effective && nc.workflow_state?.slug === 'pending_verification',
    ),
  )

  const statistics = computed(() => ({
    total: nonConformities.value.length,
    minor: nonConformities.value.filter(nc => nc.severity === 'minor').length,
    major: nonConformities.value.filter(nc => nc.severity === 'major').length,
    critical: nonConformities.value.filter(nc => nc.severity === 'critical').length,
    validated: nonConformities.value.filter(nc => nc.is_validated).length,
    closed: nonConformities.value.filter(nc => nc.workflow_state?.is_final).length,
  }))

  // Actions
  async function fetchAll (customFilters?: any) {
    loading.value = true
    error.value = null

    try {
      const params = customFilters || filters.value
      const data = await nonConformityService.getAll(params)
      nonConformities.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchById (id: number) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.getById(id)
      currentNc.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function create (payload: CreateNonConformityDto) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.create(payload)
      nonConformities.value.unshift(data)
      currentNc.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function update (id: number, payload: Partial<NonConformity>) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.update(id, payload)

      // Update in list
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = data
      }

      // Update current
      if (currentNc.value?.id === id) {
        currentNc.value = data
      }

      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function analyze (id: number, payload: AnalyzeNonConformityDto) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.analyze(id, payload)

      // Update in list and current
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = data
      }
      if (currentNc.value?.id === id) {
        currentNc.value = data
      }

      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de l\'analyse'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function validate (id: number, notes?: string) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.validate(id, {
        is_validated: true,
        validation_notes: notes,
      })

      // Update
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = data
      }
      if (currentNc.value?.id === id) {
        currentNc.value = data
      }

      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la validation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function verify (id: number, isEffective: boolean, notes?: string) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.verify(id, {
        is_effective: isEffective,
        verification_notes: notes,
      })

      // Update
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = data
      }
      if (currentNc.value?.id === id) {
        currentNc.value = data
      }

      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la vérification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function close (id: number) {
    loading.value = true
    error.value = null

    try {
      const data = await nonConformityService.close(id)

      // Update
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = data
      }
      if (currentNc.value?.id === id) {
        currentNc.value = data
      }

      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la clôture'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteNc (id: number) {
    loading.value = true
    error.value = null

    try {
      await nonConformityService.delete(id)

      // Remove from list
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value.splice(index, 1)
      }

      // Clear current if same
      if (currentNc.value?.id === id) {
        currentNc.value = null
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics (siteId?: number) {
    try {
      return await nonConformityService.getStatistics(siteId)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement des statistiques'
      throw error_
    }
  }

  function updateFilters (newFilters: Partial<typeof filters.value>) {
    filters.value = { ...filters.value, ...newFilters }
  }

  function clearFilters () {
    filters.value = {
      site_id: null,
      severity: null,
      status: null,
      axes: [],
      search: '',
    }
  }

  return {
    // State
    nonConformities,
    currentNc,
    loading,
    error,
    filters,

    // Getters
    byStatus,
    bySeverity,
    byAxe,
    pendingValidation,
    awaitingVerification,
    statistics,

    // Actions
    fetchAll,
    fetchById,
    create,
    update,
    analyze,
    validate,
    verify,
    close,
    deleteNc,
    fetchStatistics,
    updateFilters,
    clearFilters,
  }
})
