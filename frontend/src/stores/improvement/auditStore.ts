import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { auditService } from '@/services/improvement/auditService'

export const useAuditStore = defineStore('audit', () => {
  // State
  const audits = ref<any[]>([])
  const currentAudit = ref<any | null>(null)
  const statistics = ref<any | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const upcomingAudits = computed(() =>
    audits.value.filter(a => a.status === 'planned' && new Date(a.planned_date) > new Date()),
  )

  const overdueAudits = computed(() =>
    audits.value.filter(a => a.status === 'planned' && new Date(a.planned_date) < new Date()),
  )

  const completedAudits = computed(() =>
    audits.value.filter(a => a.status === 'completed'),
  )

  // Actions
  async function fetchAudits (filters?: Record<string, any>) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.getAll(filters)
      const data = (response as any)?.data ?? response
      audits.value = Array.isArray(data) ? data : []
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des audits'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.getById(id)
      const data = (response as any)?.data ?? response
      currentAudit.value = data
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createAudit (data: Record<string, any>) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.create(data as any)
      const created = (response as any)?.data ?? response
      audits.value.unshift(created)
      return created
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateAudit (id: number, data: Record<string, any>) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.update(id, data)
      const updated = (response as any)?.data ?? response
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updated
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

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
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function conductAudit (id: number, findings: any[]) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.conduct(id, findings)
      const updated = (response as any)?.data ?? response
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la conduite de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function completeAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.complete(id)
      const updated = (response as any)?.data ?? response
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la finalisation de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function startAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.start(id)
      const updated = (response as any)?.data ?? response
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updated
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du démarrage de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function generateChecklist (id: number, isoClauses: string[] = [], includeRisks = true) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.generateChecklist(id, isoClauses, includeRisks)
      const data = (response as any)?.data ?? response
      if (currentAudit.value?.id === id) {
        currentAudit.value.checklist = data?.checklist ?? []
      }
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la génération de la checklist'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function addFinding (id: number, finding: any) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.addFinding(id, finding)
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'ajout du constat'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function finalizeAudit (id: number, conclusion: string, format = 'pdf') {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.finalize(id, conclusion, format)
      const data = (response as any)?.data ?? response
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = data?.audit ?? data
      }
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la finalisation de l\'audit'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function sendInvitations (id: number) {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.sendInvitations(id)
      return (response as any)?.data ?? response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'envoi des invitations'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function downloadReport (id: number, format = 'pdf') {
    loading.value = true
    error.value = null
    try {
      const blob = await auditService.downloadReport(id, format)

      // Téléchargement automatique
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `rapport_audit_${id}.${format}`
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      return blob
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du téléchargement du rapport'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    error.value = null
    try {
      const response = await auditService.getStatistics()
      const data = (response as any)?.data ?? response
      statistics.value = data
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function reset () {
    audits.value = []
    currentAudit.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    audits,
    currentAudit,
    statistics,
    loading,
    error,

    // Computed
    upcomingAudits,
    overdueAudits,
    completedAudits,

    // Actions
    fetchAudits,
    fetchAudit,
    createAudit,
    updateAudit,
    deleteAudit,
    conductAudit,
    completeAudit,
    startAudit,
    generateChecklist,
    addFinding,
    finalizeAudit,
    sendInvitations,
    downloadReport,
    fetchStatistics,
    reset,
  }
})
