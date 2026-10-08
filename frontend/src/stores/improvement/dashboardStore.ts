import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { dashboardService } from '@/services/improvement/dashboardService'

export const useDashboardStore = defineStore('improvementDashboard', () => {
  const statistics = ref<any>(null)
  const trends = ref<any>(null)
  const riskMatrix = ref<any>(null)
  const alerts = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const stats = computed(() => statistics.value)

  // Getters
  const criticalAlerts = computed(() =>
    alerts.value.filter(a => a.severity === 'critical'),
  )

  const kpiSummary = computed(() => ({
    total_kpis: statistics.value?.indicators?.total || 0,
    alerts: statistics.value?.indicators?.with_alerts || 0,
    above_target: statistics.value?.indicators?.above_target || 0,
    below_target: statistics.value?.indicators?.below_target || 0,
  }))

  const riskSummary = computed(() => ({
    total: statistics.value?.risks?.total || 0,
    high: statistics.value?.risks?.high_criticality || 0,
    treated: statistics.value?.risks?.treated || 0,
    percentage_high: statistics.value?.risks?.total
      ? Math.round((statistics.value.risks.high_criticality / statistics.value.risks.total) * 100)
      : 0,
  }))

  // Actions
  async function fetchGlobal (filters?: any) {
    loading.value = true
    error.value = null

    try {
      const data = await dashboardService.getGlobal(filters)
      statistics.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement dashboard'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchTrends (filters?: any) {
    loading.value = true
    error.value = null

    try {
      const data = await dashboardService.getTrends(filters)
      trends.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement tendances'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchRiskMatrix (filters?: any) {
    loading.value = true
    error.value = null

    try {
      const data = await dashboardService.getRiskMatrix(filters)
      riskMatrix.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement matrice'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAlerts (filters?: any) {
    loading.value = true
    error.value = null

    try {
      const data = await dashboardService.getAlerts(filters)
      alerts.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement alertes'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function exportPdf (filters?: any) {
    try {
      return await dashboardService.exportPdf(filters)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur export PDF'
      throw error_
    }
  }

  return {
    statistics,
    stats,
    trends,
    riskMatrix,
    alerts,
    loading,
    error,

    // Getters
    criticalAlerts,
    kpiSummary,
    riskSummary,

    // Actions
    fetchGlobal,
    fetchTrends,
    fetchRiskMatrix,
    fetchAlerts,
    exportPdf,
  }
})
