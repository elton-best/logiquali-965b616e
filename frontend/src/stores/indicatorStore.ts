/**
 * Indicator Store
 * Pinia store for managing QHSE performance indicators
 */

import type {
  CreateIndicatorPayload,
  Indicator,
  IndicatorAlertLevel,
  IndicatorFilters,
  IndicatorStatistics,
  IndicatorValue,
  RecordIndicatorValuePayload,
  TrendData,
  UpdateIndicatorPayload,
} from '@/types/indicator'
import type { PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { indicatorService } from '@/services/indicatorService'

export const useIndicatorStore = defineStore('indicator', () => {
  // ============================================
  // STATE
  // ============================================
  const indicators = ref<Indicator[]>([])
  const currentIndicator = ref<Indicator | null>(null)
  const indicatorValues = ref<IndicatorValue[]>([])
  const trendData = ref<TrendData | null>(null)

  const loading = ref(false)
  const error = ref<string | null>(null)

  const meta = ref<PaginationMeta>({
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 15,
    to: 0,
    total: 0,
  })

  const links = ref<PaginationLinks>({
    first: null,
    last: null,
    prev: null,
    next: null,
  })

  const statistics = ref<IndicatorStatistics | null>(null)

  // ============================================
  // GETTERS
  // ============================================

  const activeIndicators = computed(() =>
    indicators.value.filter(i => i.status === 'active'),
  )

  const indicatorsByCategory = computed(() => {
    return (category: string) =>
      indicators.value.filter(i => i.category === category)
  })

  const indicatorsByAlertLevel = computed(() => {
    return (level: IndicatorAlertLevel) =>
      indicators.value.filter(i => i.alert_level === level)
  })

  const criticalIndicators = computed(() =>
    indicators.value.filter(i => i.alert_level === 'red'),
  )

  const greenIndicators = computed(() =>
    indicators.value.filter(i => i.alert_level === 'green'),
  )

  const yellowIndicators = computed(() =>
    indicators.value.filter(i => i.alert_level === 'yellow'),
  )

  const indicatorsWithTrend = computed(() => {
    return (direction: 'up' | 'down' | 'stable') =>
      indicators.value.filter(i => i.trend === direction)
  })

  const kpiIndicators = computed(() =>
    indicators.value.filter(i => i.type === 'kpi'),
  )

  // Alert counts for dashboard
  const alertCounts = computed(() => ({
    green: greenIndicators.value.length,
    yellow: yellowIndicators.value.length,
    red: criticalIndicators.value.length,
    total: indicators.value.length,
    active: activeIndicators.value.length,
  }))

  // ============================================
  // ACTIONS - Indicators CRUD
  // ============================================

  async function fetchIndicators (filters?: IndicatorFilters) {
    loading.value = true
    error.value = null

    try {
      const response = await indicatorService.getAll(filters)
      indicators.value = response.data
      meta.value = response.meta
      links.value = response.links
      if (response.statistics) {
        statistics.value = response.statistics
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des indicateurs'
      console.error('[IndicatorStore] Fetch error:', error_)
    } finally {
      loading.value = false
    }
  }

  async function fetchIndicatorById (id: number) {
    loading.value = true
    error.value = null

    try {
      currentIndicator.value = await indicatorService.getById(id)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de l\'indicateur'
      console.error('[IndicatorStore] Fetch by ID error:', error_)
    } finally {
      loading.value = false
    }
  }

  async function createIndicator (payload: CreateIndicatorPayload) {
    loading.value = true
    error.value = null

    try {
      const newIndicator = await indicatorService.create(payload)
      indicators.value.unshift(newIndicator)
      currentIndicator.value = newIndicator
      return newIndicator
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création de l\'indicateur'
      console.error('[IndicatorStore] Create error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateIndicator (id: number, payload: UpdateIndicatorPayload) {
    loading.value = true
    error.value = null

    try {
      const updated = await indicatorService.update(id, payload)

      const index = indicators.value.findIndex(i => i.id === id)
      if (index !== -1) {
        indicators.value[index] = updated
      }

      if (currentIndicator.value?.id === id) {
        currentIndicator.value = updated
      }

      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la mise à jour de l\'indicateur'
      console.error('[IndicatorStore] Update error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteIndicator (id: number) {
    loading.value = true
    error.value = null

    try {
      await indicatorService.delete(id)
      indicators.value = indicators.value.filter(i => i.id !== id)

      if (currentIndicator.value?.id === id) {
        currentIndicator.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression de l\'indicateur'
      console.error('[IndicatorStore] Delete error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function activateIndicator (id: number) {
    try {
      const updated = await indicatorService.activate(id)
      const index = indicators.value.findIndex(i => i.id === id)
      if (index !== -1) {
        indicators.value[index] = updated
      }
      if (currentIndicator.value?.id === id) {
        currentIndicator.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    }
  }

  async function deactivateIndicator (id: number) {
    try {
      const updated = await indicatorService.deactivate(id)
      const index = indicators.value.findIndex(i => i.id === id)
      if (index !== -1) {
        indicators.value[index] = updated
      }
      if (currentIndicator.value?.id === id) {
        currentIndicator.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    }
  }

  // ============================================
  // ACTIONS - Indicator Values
  // ============================================

  async function fetchIndicatorValues (indicatorId: number, periodStart?: string, periodEnd?: string) {
    loading.value = true
    error.value = null

    try {
      const response = await indicatorService.getValues({
        indicator_id: indicatorId,
        period_start: periodStart,
        period_end: periodEnd,
      })
      indicatorValues.value = response.data
      if (response.trend) {
        trendData.value = response.trend
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des valeurs'
      console.error('[IndicatorStore] Fetch values error:', error_)
    } finally {
      loading.value = false
    }
  }

  async function recordValue (payload: RecordIndicatorValuePayload) {
    loading.value = true
    error.value = null

    try {
      const newValue = await indicatorService.recordValue(payload)
      indicatorValues.value.unshift(newValue)

      // Update current indicator value
      const indicator = indicators.value.find(i => i.id === payload.indicator_id)
      if (indicator) {
        indicator.current_value = newValue.value
        indicator.last_measured_at = newValue.created_at
      }

      return newValue
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de l\'enregistrement de la valeur'
      console.error('[IndicatorStore] Record value error:', error_)
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function validateValue (id: number) {
    try {
      const validated = await indicatorService.validateValue(id)
      const index = indicatorValues.value.findIndex(v => v.id === id)
      if (index !== -1) {
        indicatorValues.value[index] = validated
      }
      return validated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    }
  }

  async function fetchTrend (indicatorId: number, periodStart: string, periodEnd: string) {
    loading.value = true
    error.value = null

    try {
      trendData.value = await indicatorService.getTrend(indicatorId, periodStart, periodEnd)
      return trendData.value
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de la tendance'
      console.error('[IndicatorStore] Fetch trend error:', error_)
      return null
    } finally {
      loading.value = false
    }
  }

  // ============================================
  // ACTIONS - Statistics
  // ============================================

  async function fetchStatistics () {
    try {
      statistics.value = await indicatorService.getStatistics()
    } catch (error_: any) {
      error.value = error_.message
      console.error('[IndicatorStore] Fetch statistics error:', error_)
    }
  }

  async function exportToExcel (filters?: IndicatorFilters) {
    try {
      const blob = await indicatorService.exportExcel(filters)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `indicateurs_${new Date().toISOString().split('T')[0]}.xlsx`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    }
  }

  // ============================================
  // UTILITIES
  // ============================================

  function clearError () {
    error.value = null
  }

  function resetState () {
    indicators.value = []
    currentIndicator.value = null
    indicatorValues.value = []
    trendData.value = null
    error.value = null
    statistics.value = null
  }

  return {
    // State
    indicators,
    currentIndicator,
    indicatorValues,
    trendData,
    loading,
    error,
    meta,
    links,
    statistics,

    // Getters
    activeIndicators,
    indicatorsByCategory,
    indicatorsByAlertLevel,
    criticalIndicators,
    greenIndicators,
    yellowIndicators,
    indicatorsWithTrend,
    kpiIndicators,
    alertCounts,

    // Actions
    fetchIndicators,
    fetchIndicatorById,
    createIndicator,
    updateIndicator,
    deleteIndicator,
    activateIndicator,
    deactivateIndicator,
    fetchIndicatorValues,
    recordValue,
    validateValue,
    fetchTrend,
    fetchStatistics,
    exportToExcel,
    clearError,
    resetState,
  }
})
