import type { CreateRiskDto, Risk, RiskFilters, RiskMatrix, RiskStatistics } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { riskService } from '@/services/improvement/riskService'

export const useRiskStore = defineStore('risk', () => {
  // State
  const risks = ref<Risk[]>([])
  const currentRisk = ref<Risk | null>(null)
  const riskMatrix = ref<RiskMatrix | null>(null)
  const statistics = ref<RiskStatistics | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const criticalRisks = computed(() =>
    risks.value.filter(r => (r.criticality ?? 0) >= 12),
  )

  const opportunities = computed(() =>
    risks.value.filter(r => r.type === 'opportunity'),
  )

  const activeRisks = computed(() =>
    risks.value.filter(r => r.type === 'risk'),
  )

  const risksByAxe = computed(() => {
    const grouped: Record<string, Risk[]> = {}
    for (const risk of risks.value) {
      if (risk.axes) {
        for (const axe of risk.axes) {
          const key = axe.code
          if (!grouped[key]) {
            grouped[key] = []
          }
          grouped[key].push(risk)
        }
      }
    }
    return grouped
  })

  // Actions
  async function fetchRisks (filters?: RiskFilters) {
    loading.value = true
    error.value = null
    try {
      const data = await riskService.getAll(filters)
      risks.value = data
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des risques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchRisk (id: number) {
    loading.value = true
    error.value = null
    try {
      const data = await riskService.getById(id)
      currentRisk.value = data
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createRisk (data: CreateRiskDto) {
    loading.value = true
    error.value = null
    try {
      const created = await riskService.create(data)
      risks.value.unshift(created)
      return created
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateRisk (id: number, data: Partial<Risk>) {
    loading.value = true
    error.value = null
    try {
      const response = await riskService.update(id, data)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = response
      }
      if (currentRisk.value?.id === id) {
        currentRisk.value = response
      }
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteRisk (id: number) {
    loading.value = true
    error.value = null
    try {
      await riskService.delete(id)
      risks.value = risks.value.filter(r => r.id !== id)
      if (currentRisk.value?.id === id) {
        currentRisk.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function assessRisk (id: number, assessment: any) {
    loading.value = true
    error.value = null
    try {
      const response = await riskService.assess(id, assessment)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = response
      }
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'évaluation du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function treatRisk (id: number, treatment: any) {
    loading.value = true
    error.value = null
    try {
      const response = await riskService.treat(id, treatment)
      const index = risks.value.findIndex(r => r.id === id)
      if (index !== -1) {
        risks.value[index] = response
      }
      return response
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du traitement du risque'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchMatrix (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const data = await riskService.getMatrix(filters)
      riskMatrix.value = data
      return data
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de la matrice'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    error.value = null
    try {
      const data = await riskService.getStatistics()
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
    risks.value = []
    currentRisk.value = null
    riskMatrix.value = null
    statistics.value = null
    loading.value = false
    error.value = null
  }

  return {
    // State
    risks,
    currentRisk,
    riskMatrix,
    statistics,
    loading,
    error,

    // Computed
    criticalRisks,
    opportunities,
    activeRisks,
    risksByAxe,

    // Actions
    fetchRisks,
    fetchRisk,
    createRisk,
    updateRisk,
    deleteRisk,
    assessRisk,
    treatRisk,
    fetchMatrix,
    fetchStatistics,
    reset,
  }
})
