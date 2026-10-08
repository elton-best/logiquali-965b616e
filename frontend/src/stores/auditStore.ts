/**
 * Store Pinia pour le module Audits
 * Gestion centralisée des audits
 */

import type {
  Audit,
  AuditCalendarEvent,
  AuditFilters,
  AuditStatistics,
  AuditTimelineEvent,
  CompleteAuditPayload,
  ConductAuditPayload,
  CreateAuditPayload,
  UpdateAuditPayload,
} from '@/types/audit'
import type { AuditStatus, AuditType, PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditService } from '@/services/auditService'

export const useAuditStore = defineStore('audit', () => {
  // ==================== STATE ====================

  const audits = ref<Audit[]>([])
  const currentAudit = ref<Audit | null>(null)
  const timeline = ref<AuditTimelineEvent[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Pagination
  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)

  // Statistics
  const statistics = ref<AuditStatistics | null>(null)

  // ==================== GETTERS ====================

  const auditsByStatus = computed(() => {
    return (status: AuditStatus) => audits.value.filter(audit => audit.status === status)
  })

  const auditsByType = computed(() => {
    return (type: AuditType) => audits.value.filter(audit => audit.audit_type === type)
  })

  const overdueAudits = computed(() => {
    const today = new Date().toISOString().split('T')[0] || ''
    return audits.value.filter(audit =>
      audit.status !== 'completed'
      && audit.status !== 'cancelled'
      && audit.planned_end_date < today,
    )
  })

  const upcomingAudits = computed(() => {
    const today = new Date()
    const nextMonth = new Date()
    nextMonth.setMonth(nextMonth.getMonth() + 1)

    return audits.value.filter(audit => {
      const planned = new Date(audit.planned_start_date)
      return planned >= today && planned <= nextMonth && audit.status === 'planned'
    })
  })

  const inProgressAudits = computed(() => {
    return audits.value.filter(audit => audit.status === 'in_progress')
  })

  const auditsCount = computed(() => audits.value.length)

  const averageConformityRate = computed(() => {
    const completed = audits.value.filter(a => a.conformity_rate !== undefined && a.conformity_rate !== null)
    if (completed.length === 0) {
      return 0
    }
    const sum = completed.reduce((acc, audit) => acc + (audit.conformity_rate || 0), 0)
    return Math.round(sum / completed.length)
  })

  /**
   * Convertir audits en événements calendrier
   */
  const calendarEvents = computed((): AuditCalendarEvent[] => {
    return audits.value.map(audit => ({
      id: audit.id,
      title: `${audit.code} - ${audit.title}`,
      start: audit.planned_start_date,
      end: audit.planned_end_date,
      allDay: true,

      audit,
      status: audit.status,
      type: audit.audit_type,
      leadAuditor: audit.lead_auditor!,

      backgroundColor: getStatusColor(audit.status),
      borderColor: getStatusColor(audit.status),
      textColor: '#ffffff',
      classNames: ['audit-event', `audit-${audit.status}`],
    }))
  })

  // ==================== ACTIONS ====================

  /**
   * Récupérer la liste des audits
   */
  async function fetchAudits (filters?: AuditFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await auditService.getAll(filters)
      audits.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des audits'
      console.error('[auditStore] fetchAudits error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer un audit par ID
   */
  async function fetchAuditById (id: number) {
    loading.value = true
    error.value = null

    try {
      const audit = await auditService.getById(id)
      currentAudit.value = audit

      // Update in list if exists
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = audit
      }

      return audit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'audit'
      console.error('[auditStore] fetchAuditById error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Créer un audit
   */
  async function createAudit (data: CreateAuditPayload) {
    loading.value = true
    error.value = null

    try {
      const newAudit = await auditService.create(data)
      audits.value.unshift(newAudit)
      currentAudit.value = newAudit
      return newAudit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'audit'
      console.error('[auditStore] createAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Mettre à jour un audit
   */
  async function updateAudit (id: number, data: UpdateAuditPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAudit = await auditService.update(id, data)

      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }

      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }

      return updatedAudit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'audit'
      console.error('[auditStore] updateAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Supprimer un audit
   */
  async function deleteAudit (id: number) {
    loading.value = true
    error.value = null

    try {
      await auditService.delete(id)
      audits.value = audits.value.filter(a => a.id !== id)

      if (currentAudit.value?.id === id) {
        currentAudit.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'audit'
      console.error('[auditStore] deleteAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Démarrer un audit
   */
  async function startAudit (id: number, actual_start_date?: string) {
    loading.value = true
    error.value = null

    try {
      const updatedAudit = await auditService.start(id, actual_start_date)

      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }

      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }

      return updatedAudit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du démarrage de l\'audit'
      console.error('[auditStore] startAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Conduire audit (saisie)
   */
  async function conductAudit (id: number, data: ConductAuditPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAudit = await auditService.conduct(id, data)

      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }

      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }

      return updatedAudit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la conduite de l\'audit'
      console.error('[auditStore] conductAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Compléter un audit
   */
  async function completeAudit (id: number, data: CompleteAuditPayload) {
    loading.value = true
    error.value = null

    try {
      const updatedAudit = await auditService.complete(id, data)

      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }

      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }

      return updatedAudit
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la complétion de l\'audit'
      console.error('[auditStore] completeAudit error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Générer checklist
   */
  async function generateChecklist (id: number, clauses?: string[]) {
    loading.value = true
    error.value = null

    try {
      await auditService.generateChecklist(id, clauses)
      // Reload audit to get checklist
      await fetchAuditById(id)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la génération de la checklist'
      console.error('[auditStore] generateChecklist error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer statistiques
   */
  async function fetchStatistics (filters?: Partial<AuditFilters>) {
    loading.value = true
    error.value = null

    try {
      const stats = await auditService.getStatistics(filters)
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      console.error('[auditStore] fetchStatistics error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Récupérer timeline
   */
  async function fetchTimeline (id: number) {
    loading.value = true
    error.value = null

    try {
      const events = await auditService.getTimeline(id)
      timeline.value = events
      return events
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'historique'
      console.error('[auditStore] fetchTimeline error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Exporter Excel
   */
  async function exportExcel (filters?: AuditFilters) {
    loading.value = true
    error.value = null

    try {
      const blob = await auditService.exportExcel(filters)

      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `audits_${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'export Excel'
      console.error('[auditStore] exportExcel error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Reset
   */
  function reset () {
    audits.value = []
    currentAudit.value = null
    timeline.value = []
    meta.value = null
    links.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  function clearCurrent () {
    currentAudit.value = null
    timeline.value = []
  }

  // ==================== HELPERS ====================

  function getStatusColor (status: AuditStatus): string {
    const colors: Record<string, string> = {
      planned: '#3B82F6', // blue
      in_progress: '#F59E0B', // amber
      completed: '#10B981', // green
      verified: '#8B5CF6', // purple
      approved: '#059669', // emerald
      cancelled: '#EF4444', // red
    }
    return colors[status] || '#6B7280'
  }

  // ==================== RETURN ====================

  return {
    // State
    audits,
    currentAudit,
    timeline,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    auditsByStatus,
    auditsByType,
    overdueAudits,
    upcomingAudits,
    inProgressAudits,
    auditsCount,
    averageConformityRate,
    calendarEvents,

    // Actions
    fetchAudits,
    fetchAuditById,
    createAudit,
    updateAudit,
    deleteAudit,
    startAudit,
    conductAudit,
    completeAudit,
    generateChecklist,
    fetchStatistics,
    fetchTimeline,
    exportExcel,
    reset,
    clearCurrent,
  }
})
