/**
 * useRisks Composable
 * Manages risks state and operations
 */

import { computed, ref } from 'vue'
import {
  type AddControlDTO,
  type AssessRiskDTO,
  type CreateRiskDTO,
  type Risk,
  type RisksListParams,
  risksService,
  type UpdateRiskDTO,
  type UpdateTreatmentDTO,
} from '@/api/services/risks.service'

export function useRisks () {
  const risks = ref<Risk[]>([])
  const currentRisk = ref<Risk | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const highRisks = computed(() => risks.value.filter(r => r.risk_score >= 15))
  const mediumRisks = computed(() => risks.value.filter(r => r.risk_score >= 6 && r.risk_score <= 14))
  const lowRisks = computed(() => risks.value.filter(r => r.risk_score <= 5))

  const stats = computed(() => ({
    total: risks.value.length,
    high: highRisks.value.length,
    medium: mediumRisks.value.length,
    low: lowRisks.value.length,
    byCategory: {
      qualite: risks.value.filter(r => r.category === 'qualite').length,
      hygiene: risks.value.filter(r => r.category === 'hygiene').length,
      securite: risks.value.filter(r => r.category === 'securite').length,
      environnement: risks.value.filter(r => r.category === 'environnement').length,
    },
    byStatus: {
      identified: risks.value.filter(r => r.status === 'identified').length,
      analyzed: risks.value.filter(r => r.status === 'analyzed').length,
      treated: risks.value.filter(r => r.status === 'treated').length,
      monitored: risks.value.filter(r => r.status === 'monitored').length,
      closed: risks.value.filter(r => r.status === 'closed').length,
    },
  }))

  /**
   * Fetch risks list
   */
  async function fetchRisks (params: RisksListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await risksService.getRisks(params)
      risks.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch risks'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single risk
   */
  async function fetchRisk (id: number) {
    loading.value = true
    error.value = null
    try {
      currentRisk.value = await risksService.getRisk(id)
      return currentRisk.value
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch risk'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new risk
   */
  async function createRisk (data: CreateRiskDTO) {
    loading.value = true
    error.value = null
    try {
      const newRisk = await risksService.createRisk(data)
      risks.value.unshift(newRisk)
      return newRisk
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create risk'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update risk
   */
  async function updateRisk (id: number, data: UpdateRiskDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedRisk = await risksService.updateRisk(id, data)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = updatedRisk
      }
      if (currentRisk.value?.id === id) {
        currentRisk.value = updatedRisk
      }
      return updatedRisk
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update risk'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete risk
   */
  async function deleteRisk (id: number) {
    loading.value = true
    error.value = null
    try {
      await risksService.deleteRisk(id)
      risks.value = risks.value.filter(r => r.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete risk'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Assess risk
   */
  async function assessRisk (id: number, data: AssessRiskDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedRisk = await risksService.assessRisk(id, data)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = updatedRisk
      }
      if (currentRisk.value?.id === id) {
        currentRisk.value = updatedRisk
      }
      return updatedRisk
    } catch (error_: any) {
      error.value = error_.message || 'Failed to assess risk'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update treatment
   */
  async function updateTreatment (id: number, data: UpdateTreatmentDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedRisk = await risksService.updateTreatment(id, data)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = updatedRisk
      }
      if (currentRisk.value?.id === id) {
        currentRisk.value = updatedRisk
      }
      return updatedRisk
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update treatment'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Add control
   */
  async function addControl (id: number, data: AddControlDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedRisk = await risksService.addControl(id, data)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = updatedRisk
      }
      if (currentRisk.value?.id === id) {
        currentRisk.value = updatedRisk
      }
      return updatedRisk
    } catch (error_: any) {
      error.value = error_.message || 'Failed to add control'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    risks,
    currentRisk,
    loading,
    error,
    pagination,
    highRisks,
    mediumRisks,
    lowRisks,
    stats,
    fetchRisks,
    fetchRisk,
    createRisk,
    updateRisk,
    deleteRisk,
    assessRisk,
    updateTreatment,
    addControl,
  }
}
