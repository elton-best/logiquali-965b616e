/**
 * Store Pinia pour les Programmes d'Audits
 */

import type {
  AuditProgram,
  AuditProgramFilters,
  AuditProgramStatistics,
  CreateAuditProgramPayload,
} from '@/types/audit'
import type { PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditProgramService } from '@/services/auditProgramService'

export const useAuditProgramStore = defineStore('auditProgram', () => {
  // ==================== STATE ====================

  const programs = ref<AuditProgram[]>([])
  const currentProgram = ref<AuditProgram | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)
  const statistics = ref<AuditProgramStatistics | null>(null)

  // ==================== GETTERS ====================

  const programsByYear = computed(() => {
    return (year: number) => programs.value.filter(p => p.year === year)
  })

  const programsByStatus = computed(() => {
    return (status: string) => programs.value.filter(p => p.status === status)
  })

  const activePrograms = computed(() => {
    return programs.value.filter(p => p.status === 'in_progress' || p.status === 'approved')
  })

  const currentYearProgram = computed(() => {
    const currentYear = new Date().getFullYear()
    return programs.value.find(p => p.year === currentYear)
  })

  // ==================== ACTIONS ====================

  async function fetchPrograms (filters?: AuditProgramFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await auditProgramService.getAll(filters)
      programs.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des programmes'
      console.error('[auditProgramStore] fetchPrograms error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchProgramById (id: number) {
    loading.value = true
    error.value = null

    try {
      const program = await auditProgramService.getById(id)
      currentProgram.value = program

      const index = programs.value.findIndex(p => p.id === id)
      if (index !== -1) {
        programs.value[index] = program
      }

      return program
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement du programme'
      console.error('[auditProgramStore] fetchProgramById error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createProgram (data: CreateAuditProgramPayload) {
    loading.value = true
    error.value = null

    try {
      const newProgram = await auditProgramService.create(data)
      programs.value.unshift(newProgram)
      currentProgram.value = newProgram
      return newProgram
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du programme'
      console.error('[auditProgramStore] createProgram error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateProgram (id: number, data: Partial<CreateAuditProgramPayload>) {
    loading.value = true
    error.value = null

    try {
      const updatedProgram = await auditProgramService.update(id, data)

      const index = programs.value.findIndex(p => p.id === id)
      if (index !== -1) {
        programs.value[index] = updatedProgram
      }

      if (currentProgram.value?.id === id) {
        currentProgram.value = updatedProgram
      }

      return updatedProgram
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour du programme'
      console.error('[auditProgramStore] updateProgram error:', error_)
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
      console.error('[auditProgramStore] deleteProgram error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function approveProgram (id: number) {
    loading.value = true
    error.value = null

    try {
      const approvedProgram = await auditProgramService.approve(id)

      const index = programs.value.findIndex(p => p.id === id)
      if (index !== -1) {
        programs.value[index] = approvedProgram
      }

      if (currentProgram.value?.id === id) {
        currentProgram.value = approvedProgram
      }

      return approvedProgram
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'approbation du programme'
      console.error('[auditProgramStore] approveProgram error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateAudits (id: number) {
    loading.value = true
    error.value = null

    try {
      const updatedProgram = await auditProgramService.generateAudits(id)

      const index = programs.value.findIndex(p => p.id === id)
      if (index !== -1) {
        programs.value[index] = updatedProgram
      }

      if (currentProgram.value?.id === id) {
        currentProgram.value = updatedProgram
      }

      return updatedProgram
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la génération des audits'
      console.error('[auditProgramStore] generateAudits error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics (year?: number) {
    loading.value = true
    error.value = null

    try {
      const stats = await auditProgramService.getStatistics(year)
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      console.error('[auditProgramStore] fetchStatistics error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    programs.value = []
    currentProgram.value = null
    meta.value = null
    links.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    programs,
    currentProgram,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    programsByYear,
    programsByStatus,
    activePrograms,
    currentYearProgram,

    // Actions
    fetchPrograms,
    fetchProgramById,
    createProgram,
    updateProgram,
    deleteProgram,
    approveProgram,
    generateAudits,
    fetchStatistics,
    reset,
  }
})
