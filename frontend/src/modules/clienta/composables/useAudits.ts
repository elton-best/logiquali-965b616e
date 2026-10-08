/**
 * useAudits Composable
 * Manages audits state and operations
 */

import { computed, ref } from 'vue'
import {
  type Audit,
  type AuditFinding,
  type AuditsListParams,
  auditsService,
  type AuditStatus,
  type CreateAuditDTO,
  type CreateFindingDTO,
  type UpdateAuditDTO,
} from '@/api/services/audits.service'
import { getErrorMessage } from '@/utils/errorMessage'

export function useAudits () {
  const audits = ref<Audit[]>([])
  const currentAudit = ref<Audit | null>(null)
  const findings = ref<AuditFinding[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })

  // Computed filters
  const plannedAudits = computed(() => audits.value.filter(a => a.status === 'planned'))
  const inProgressAudits = computed(() => audits.value.filter(a => a.status === 'in_progress'))
  const completedAudits = computed(() => audits.value.filter(a =>
    a.status === 'report_approved' || a.status === 'closed',
  ))

  // Computed stats
  const stats = computed(() => ({
    total: pagination.value?.total || (Math.max(audits.value.length, 0)),
    planned: plannedAudits.value.length,
    in_progress: inProgressAudits.value.length,
    completed: completedAudits.value.length,
  }))

  /**
   * Fetch audits list
   */
  async function fetchAudits (params: AuditsListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await auditsService.getAudits(params)
      console.log('[useAudits] Raw response:', response)

      // Transformer depuis JSON:API si nécessaire
      audits.value = response.data && Array.isArray(response.data)
        ? response.data.map((item: any) => (item.attributes
            ? {
                id: item.id,
                reference: item.attributes.ref,
                ...item.attributes,
                lead_auditor: item.relationships?.lead_auditor?.attributes,
                site: item.relationships?.site?.attributes,
              }
            : item))
        : []

      pagination.value = response.meta || {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: audits.value.length,
      }

      console.log('[useAudits] Audits loaded:', audits.value.length)
    } catch (error_: any) {
      console.error('[useAudits] Error fetching audits:', error_)
      error.value = getErrorMessage(error_, 'Impossible de charger les audits.')
      audits.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single audit
   */
  async function fetchAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      currentAudit.value = await auditsService.getAudit(id)
      return currentAudit.value
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de charger cet audit.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new audit
   */
  async function createAudit (data: CreateAuditDTO) {
    loading.value = true
    error.value = null
    try {
      const newAudit = await auditsService.createAudit(data)
      audits.value.unshift(newAudit)
      return newAudit
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de créer l’audit.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update audit
   */
  async function updateAudit (id: number, data: UpdateAuditDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedAudit = await auditsService.updateAudit(id, data)
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }
      return updatedAudit
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de mettre à jour l’audit.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete audit
   */
  async function deleteAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      await auditsService.deleteAudit(id)
      audits.value = audits.value.filter(a => a.id !== id)
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de supprimer l’audit.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch audit findings
   */
  async function fetchFindings (auditId: number) {
    loading.value = true
    error.value = null
    try {
      findings.value = await auditsService.getFindings(auditId)
      return findings.value
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de charger les constats.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Add finding to audit
   */
  async function addFinding (auditId: number, data: CreateFindingDTO) {
    loading.value = true
    error.value = null
    try {
      const newFinding = await auditsService.addFinding(auditId, data)
      findings.value.push(newFinding)
      // Update findings count in current audit
      if (currentAudit.value?.id === auditId) {
        currentAudit.value.findings_count++
        if (data.type === 'non_conformity') {
          currentAudit.value.ncs_count++
        }
      }
      return newFinding
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible d’ajouter le constat.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Generate audit report
   */
  async function generateReport (id: number, format: 'pdf' | 'docx' = 'pdf') {
    loading.value = true
    error.value = null
    try {
      const result = await auditsService.generateReport(id, format)
      const updatedAudit = result.audit
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }
      return result
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de générer le rapport.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Approve audit report
   */
  async function approveReport (id: number) {
    loading.value = true
    error.value = null
    try {
      const updatedAudit = await auditsService.approveReport(id)
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }
      return updatedAudit
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible d’approuver le rapport.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update audit status
   */
  async function updateStatus (id: number, status: AuditStatus) {
    loading.value = true
    error.value = null
    try {
      const updatedAudit = await auditsService.updateStatus(id, status)
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }
      return updatedAudit
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de mettre à jour le statut.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Download audit report
   */
  async function downloadReport (id: number, filename?: string) {
    loading.value = true
    error.value = null
    try {
      await auditsService.downloadReport(id, filename)
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de télécharger le rapport.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Upload external report
   */
  async function uploadExternalReport (id: number, file: File, version?: number) {
    loading.value = true
    error.value = null
    try {
      const payload = await auditsService.uploadExternalReport(id, file, version)
      await fetchAudit(id)
      return payload
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible d’importer le rapport externe.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Complete audit and trigger automatic report generation.
   */
  async function completeAudit (id: number) {
    loading.value = true
    error.value = null
    try {
      const updatedAudit = await auditsService.completeAudit(id)
      const index = audits.value.findIndex(a => a.id === id)
      if (index !== -1) {
        audits.value[index] = updatedAudit
      }
      if (currentAudit.value?.id === id) {
        currentAudit.value = updatedAudit
      }
      return updatedAudit
    } catch (error_: any) {
      error.value = getErrorMessage(error_, 'Impossible de clôturer l’audit.')
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    audits,
    currentAudit,
    findings,
    loading,
    error,
    pagination,
    plannedAudits,
    inProgressAudits,
    completedAudits,
    stats,
    fetchAudits,
    fetchAudit,
    createAudit,
    updateAudit,
    deleteAudit,
    fetchFindings,
    addFinding,
    generateReport,
    approveReport,
    updateStatus,
    downloadReport,
    uploadExternalReport,
    completeAudit,
  }
}
