/**
 * Store Pinia pour le module Non-Conformités
 * Gestion centralisée de l'état des NC
 */

import type {
  AnalyzeNCPayload,
  CreateNCPayload,
  NCFilters,
  NCStatistics,
  NCTimelineEvent,
  NonConformity,
  UpdateNCPayload,
  ValidateNCPayload,
  VerifyEffectivenessNCPayload,
} from '@/types/nonConformity'
import type { NCSeverity, NCStatus, PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { nonConformityService } from '@/services/nonConformityService'

export const useNonConformityStore = defineStore('nonConformity', () => {
  // ==================== STATE ====================

  const nonConformities = ref<NonConformity[]>([])
  const currentNC = ref<NonConformity | null>(null)
  const timeline = ref<NCTimelineEvent[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)

  // Statistics
  const statistics = ref<NCStatistics | null>(null)

  // ==================== GETTERS ====================

  const ncsByStatus = computed(() => {
    return (status: NCStatus) => nonConformities.value.filter(nc => nc.status === status)
  })

  const ncsBySeverity = computed(() => {
    return (severity: NCSeverity) => nonConformities.value.filter(nc => nc.severity === severity)
  })

  const overdueNCs = computed(() => {
    const today = new Date().toISOString().split('T')[0] ?? ''
    return nonConformities.value.filter(nc =>
      nc.status !== 'clos'
      && nc.status !== 'rejete'
      && nc.deadline
      && nc.deadline < today,
    )
  })

  const unresolvedNCs = computed(() => {
    return nonConformities.value.filter(nc =>
      nc.status !== 'clos' && nc.status !== 'rejete',
    )
  })

  const majorNCs = computed(() => {
    return nonConformities.value.filter(nc => nc.severity === 'majeur')
  })

  const ncsCount = computed(() => nonConformities.value.length)

  const resolutionRate = computed(() => {
    if (nonConformities.value.length === 0) {
      return 0
    }
    const resolved = nonConformities.value.filter(nc => nc.status === 'clos').length
    return Math.round((resolved / nonConformities.value.length) * 100)
  })

  // ==================== ACTIONS ====================

  /**
   * Récupérer la liste des NC avec filtres
   */
  async function fetchNonConformities (filters?: NCFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await nonConformityService.getAll(filters)
      nonConformities.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des non-conformités'
      console.error('[ncStore] fetchNonConformities error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer une NC par ID
   */
  async function fetchNCById (id: number) {
    loading.value = true
    error.value = null

    try {
      const nc = await nonConformityService.getById(id)
      currentNC.value = nc

      // Mettre à jour dans la liste si elle existe
      const index = nonConformities.value.findIndex(n => n.id === id)
      if (index !== -1) {
        nonConformities.value[index] = nc
      }

      return nc
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de la non-conformité'
      console.error('[ncStore] fetchNCById error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Créer une nouvelle NC
   */
  async function createNC (data: CreateNCPayload) {
    loading.value = true
    error.value = null

    try {
      const newNC = await nonConformityService.create(data)
      nonConformities.value.unshift(newNC)
      currentNC.value = newNC
      return newNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de la non-conformité'
      console.error('[ncStore] createNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Mettre à jour une NC
   */
  async function updateNC (id: number, data: UpdateNCPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.update(id, data)

      // Mettre à jour dans la liste
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      // Mettre à jour current si c'est la même
      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de la non-conformité'
      console.error('[ncStore] updateNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Supprimer une NC
   */
  async function deleteNC (id: number) {
    loading.value = true
    error.value = null

    try {
      await nonConformityService.delete(id)

      // Retirer de la liste
      nonConformities.value = nonConformities.value.filter(nc => nc.id !== id)

      // Clear current si c'est la même
      if (currentNC.value?.id === id) {
        currentNC.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de la non-conformité'
      console.error('[ncStore] deleteNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Analyser les causes d'une NC
   */
  async function analyzeCauses (id: number, data: AnalyzeNCPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.analyzeCauses(id, data)

      // Mettre à jour
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'analyse des causes'
      console.error('[ncStore] analyzeCauses error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Valider une NC
   */
  async function validateNC (id: number, data: ValidateNCPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.validate(id, data)

      // Mettre à jour
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la validation'
      console.error('[ncStore] validateNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Vérifier l'efficacité des actions
   */
  async function verifyEffectiveness (id: number, data: VerifyEffectivenessNCPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.verifyEffectiveness(id, data)

      // Mettre à jour
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la vérification'
      console.error('[ncStore] verifyEffectiveness error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Clôturer une NC
   */
  async function closeNC (id: number, notes?: string) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.close(id, notes)

      // Mettre à jour
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la clôture'
      console.error('[ncStore] closeNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Rouvrir une NC
   */
  async function reopenNC (id: number, reason: string) {
    loading.value = true
    error.value = null

    try {
      const updatedNC = await nonConformityService.reopen(id, reason)

      // Mettre à jour
      const index = nonConformities.value.findIndex(nc => nc.id === id)
      if (index !== -1) {
        nonConformities.value[index] = updatedNC
      }

      if (currentNC.value?.id === id) {
        currentNC.value = updatedNC
      }

      return updatedNC
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la réouverture'
      console.error('[ncStore] reopenNC error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer les statistiques
   */
  async function fetchStatistics (filters?: Partial<NCFilters>) {
    loading.value = true
    error.value = null

    try {
      const stats = await nonConformityService.getStatistics(filters)
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      console.error('[ncStore] fetchStatistics error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer la timeline
   */
  async function fetchTimeline (id: number) {
    loading.value = true
    error.value = null

    try {
      const events = await nonConformityService.getTimeline(id)
      timeline.value = events
      return events
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'historique'
      console.error('[ncStore] fetchTimeline error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Exporter en Excel
   */
  async function exportExcel (filters?: NCFilters) {
    loading.value = true
    error.value = null

    try {
      const blob = await nonConformityService.exportExcel(filters)

      // Télécharger le fichier
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `non-conformites_${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'export Excel'
      console.error('[ncStore] exportExcel error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Réinitialiser l'état
   */
  function reset () {
    nonConformities.value = []
    currentNC.value = null
    timeline.value = []
    meta.value = null
    links.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  /**
   * Clear current NC
   */
  function clearCurrent () {
    currentNC.value = null
    timeline.value = []
  }

  // ==================== RETURN ====================

  return {
    // State
    nonConformities,
    currentNC,
    timeline,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    ncsByStatus,
    ncsBySeverity,
    overdueNCs,
    unresolvedNCs,
    majorNCs,
    ncsCount,
    resolutionRate,

    // Actions
    fetchNonConformities,
    fetchNCById,
    createNC,
    updateNC,
    deleteNC,
    analyzeCauses,
    validateNC,
    verifyEffectiveness,
    closeNC,
    reopenNC,
    fetchStatistics,
    fetchTimeline,
    exportExcel,
    reset,
    clearCurrent,
  }
})
