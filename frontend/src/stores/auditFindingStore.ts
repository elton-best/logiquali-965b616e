/**
 * Store Pinia pour les Constats d'Audits (Findings)
 */

import type {
  AuditFinding,
  AuditFindingFilters,
  AuditFindingStatistics,
  CreateAuditFindingPayload,
  UpdateAuditFindingPayload,
} from '@/types/audit'
import type { FindingSeverity, PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditFindingService } from '@/services/auditFindingService'

export const useAuditFindingStore = defineStore('auditFinding', () => {
  // ==================== STATE ====================

  const findings = ref<AuditFinding[]>([])
  const currentFinding = ref<AuditFinding | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)
  const statistics = ref<AuditFindingStatistics | null>(null)

  // ==================== GETTERS ====================

  const findingsBySeverity = computed(() => {
    return (severity: FindingSeverity) => findings.value.filter(f => f.severity === severity)
  })

  const findingsByStatus = computed(() => {
    return (status: string) => findings.value.filter(f => f.status === status)
  })

  const findingsByAudit = computed(() => {
    return (auditId: number) => findings.value.filter(f => f.audit_id === auditId)
  })

  const majorFindings = computed(() => {
    return findings.value.filter(f => f.severity === 'majeur')
  })

  const minorFindings = computed(() => {
    return findings.value.filter(f => f.severity === 'mineur')
  })

  const openFindings = computed(() => {
    return findings.value.filter(f => f.status === 'open' || f.status === 'action_planned' || f.status === 'in_progress')
  })

  const findingsRequiringNC = computed(() => {
    return findings.value.filter(f =>
      (f.severity === 'majeur' || f.severity === 'mineur')
      && !f.nc_generated
      && f.category === 'non_conformite',
    )
  })

  // ==================== ACTIONS ====================

  async function fetchFindings (filters?: AuditFindingFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await auditFindingService.getAll(filters)
      findings.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des constats'
      console.error('[auditFindingStore] fetchFindings error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchFindingById (id: number) {
    loading.value = true
    error.value = null

    try {
      const finding = await auditFindingService.getById(id)
      currentFinding.value = finding

      const index = findings.value.findIndex(f => f.id === id)
      if (index !== -1) {
        findings.value[index] = finding
      }

      return finding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement du constat'
      console.error('[auditFindingStore] fetchFindingById error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createFinding (data: CreateAuditFindingPayload) {
    loading.value = true
    error.value = null

    try {
      const newFinding = await auditFindingService.create(data)
      findings.value.unshift(newFinding)
      currentFinding.value = newFinding
      return newFinding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du constat'
      console.error('[auditFindingStore] createFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateFinding (id: number, data: UpdateAuditFindingPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedFinding = await auditFindingService.update(id, data)

      const index = findings.value.findIndex(f => f.id === id)
      if (index !== -1) {
        findings.value[index] = updatedFinding
      }

      if (currentFinding.value?.id === id) {
        currentFinding.value = updatedFinding
      }

      return updatedFinding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour du constat'
      console.error('[auditFindingStore] updateFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteFinding (id: number) {
    loading.value = true
    error.value = null

    try {
      await auditFindingService.delete(id)
      findings.value = findings.value.filter(f => f.id !== id)

      if (currentFinding.value?.id === id) {
        currentFinding.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression du constat'
      console.error('[auditFindingStore] deleteFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateNC (id: number) {
    loading.value = true
    error.value = null

    try {
      await auditFindingService.generateNC(id)
      // Reload finding to get updated nc_generated flag
      await fetchFindingById(id)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la génération de la NC'
      console.error('[auditFindingStore] generateNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function resolveFinding (id: number, notes?: string) {
    loading.value = true
    error.value = null

    try {
      const resolvedFinding = await auditFindingService.resolve(id, notes)

      const index = findings.value.findIndex(f => f.id === id)
      if (index !== -1) {
        findings.value[index] = resolvedFinding
      }

      if (currentFinding.value?.id === id) {
        currentFinding.value = resolvedFinding
      }

      return resolvedFinding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la résolution du constat'
      console.error('[auditFindingStore] resolveFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function verifyFinding (id: number, notes?: string) {
    loading.value = true
    error.value = null

    try {
      const verifiedFinding = await auditFindingService.verify(id, notes)

      const index = findings.value.findIndex(f => f.id === id)
      if (index !== -1) {
        findings.value[index] = verifiedFinding
      }

      if (currentFinding.value?.id === id) {
        currentFinding.value = verifiedFinding
      }

      return verifiedFinding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la vérification du constat'
      console.error('[auditFindingStore] verifyFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function closeFinding (id: number) {
    loading.value = true
    error.value = null

    try {
      const closedFinding = await auditFindingService.close(id)

      const index = findings.value.findIndex(f => f.id === id)
      if (index !== -1) {
        findings.value[index] = closedFinding
      }

      if (currentFinding.value?.id === id) {
        currentFinding.value = closedFinding
      }

      return closedFinding
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la clôture du constat'
      console.error('[auditFindingStore] closeFinding error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function uploadAttachment (id: number, file: File) {
    loading.value = true
    error.value = null

    try {
      const response = await auditFindingService.uploadAttachment(id, file)
      // Reload finding to get updated attachments
      await fetchFindingById(id)
      return response.data.url
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'upload de la pièce jointe'
      console.error('[auditFindingStore] uploadAttachment error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics (filters?: Partial<AuditFindingFilters>) {
    loading.value = true
    error.value = null

    try {
      const stats = await auditFindingService.getStatistics(filters)
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      console.error('[auditFindingStore] fetchStatistics error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    findings.value = []
    currentFinding.value = null
    meta.value = null
    links.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  function clearCurrent () {
    currentFinding.value = null
  }

  return {
    // State
    findings,
    currentFinding,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    findingsBySeverity,
    findingsByStatus,
    findingsByAudit,
    majorFindings,
    minorFindings,
    openFindings,
    findingsRequiringNC,

    // Actions
    fetchFindings,
    fetchFindingById,
    createFinding,
    updateFinding,
    deleteFinding,
    generateNC,
    resolveFinding,
    verifyFinding,
    closeFinding,
    uploadAttachment,
    fetchStatistics,
    reset,
    clearCurrent,
  }
})
