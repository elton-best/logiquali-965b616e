import type { AuditProgram, AuditProgramFilters, AuditProgramStats } from '@/types/models/auditPrograms'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import auditProgramService from '@/services/improvement/auditProgramService'

export const useAuditProgramStore = defineStore('auditProgram', () => {
  // State
  const programs = ref<AuditProgram[]>([])
  const currentProgram = ref<AuditProgram | null>(null)
  const statistics = ref<AuditProgramStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const activePrograms = computed(() =>
    programs.value.filter(p => ['validated', 'in_progress'].includes(p.status)),
  )

  const draftPrograms = computed(() =>
    programs.value.filter(p => p.status === 'draft'),
  )

  const completedPrograms = computed(() =>
    programs.value.filter(p => p.status === 'completed'),
  )

  const currentYearProgram = computed(() => {
    const currentYear = new Date().getFullYear()
    return programs.value.find(p => p.year === currentYear)
  })

  function normalizeProgramsPayload (payload: any): AuditProgram[] {
    if (Array.isArray(payload)) {
      return payload
    }

    if (Array.isArray(payload?.data)) {
      return payload.data
    }

    if (Array.isArray(payload?.data?.data)) {
      return payload.data.data
    }

    return []
  }

  // Actions
  async function fetchPrograms (filters?: AuditProgramFilters) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.getAll(filters)
      programs.value = normalizeProgramsPayload(response)
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des programmes'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchProgram (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.getById(id)
      currentProgram.value = response
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement du programme'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createProgram (data: Partial<AuditProgram>) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.create(data)
      programs.value = [response, ...programs.value.filter(p => p.id !== response.id)]
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du programme'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateProgram (id: number, data: Partial<AuditProgram>) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.update(id, data)
      const index = programs.value.findIndex(p => p.id === id)
      if (index === -1) {
        programs.value.unshift(response)
      } else {
        programs.value[index] = response
      }
      if (currentProgram.value?.id === id) {
        currentProgram.value = response
      }
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour du programme'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteProgram (id: number) {
    loading.value = true
    error.value = null
    try {
      await auditProgramService.delete(id)
      programs.value = programs.value.filter(p => p.id !== id)
      if (currentProgram.value?.id === id) {
        currentProgram.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression du programme'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function validateProgram (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.validate(id)
      const index = programs.value.findIndex(p => p.id === id)
      if (index === -1) {
        programs.value.unshift(response)
      } else {
        programs.value[index] = response
      }
      if (currentProgram.value?.id === id) {
        currentProgram.value = response
      }
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la validation du programme'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateFromRisks (id: number, minCriticality = 12, includeAll = false) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.generateFromRisks(id, minCriticality, includeAll)
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la génération des suggestions'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function exportCalendar (id: number) {
    loading.value = true
    error.value = null
    try {
      const blob = await auditProgramService.exportCalendar(id)

      // Téléchargement automatique
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `programme_audits_${id}_${new Date().getFullYear()}.xlsx`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      return blob
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'export du calendrier'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditProgramService.getStatistics(id)
      statistics.value = response
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    programs.value = []
    currentProgram.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    programs,
    currentProgram,
    statistics,
    loading,
    error,

    // Computed
    activePrograms,
    draftPrograms,
    completedPrograms,
    currentYearProgram,

    // Actions
    fetchPrograms,
    fetchProgram,
    createProgram,
    updateProgram,
    deleteProgram,
    validateProgram,
    generateFromRisks,
    exportCalendar,
    fetchStatistics,
    reset,
  }
})
