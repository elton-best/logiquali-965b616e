/**
 * useIndicators Composable
 * Manages indicators state and operations
 */

import { computed, ref } from 'vue'
import {
  type ChartDataPoint,
  type CreateIndicatorDTO,
  type Indicator,
  type IndicatorsListParams,
  indicatorsService,
  type IndicatorValue,
  type RecordValueDTO,
  type UpdateIndicatorDTO,
} from '@/api/services/indicators.service'

export function useIndicators () {
  const indicators = ref<Indicator[]>([])
  const currentIndicator = ref<Indicator | null>(null)
  const values = ref<IndicatorValue[]>([])
  const chartData = ref<ChartDataPoint[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const activeIndicators = computed(() =>
    indicators.value.filter(i => i.status === 'active'),
  )

  const indicatorsByCategory = computed(() => ({
    qualite: indicators.value.filter(i => i.category === 'qualite'),
    hygiene: indicators.value.filter(i => i.category === 'hygiene'),
    securite: indicators.value.filter(i => i.category === 'securite'),
    environnement: indicators.value.filter(i => i.category === 'environnement'),
    performance: indicators.value.filter(i => i.category === 'performance'),
  }))

  const stats = computed(() => ({
    total: indicators.value.length,
    active: activeIndicators.value.length,
    inactive: indicators.value.filter(i => i.status === 'inactive').length,
    byCategory: {
      qualite: indicatorsByCategory.value.qualite.length,
      hygiene: indicatorsByCategory.value.hygiene.length,
      securite: indicatorsByCategory.value.securite.length,
      environnement: indicatorsByCategory.value.environnement.length,
      performance: indicatorsByCategory.value.performance.length,
    },
    byFrequency: {
      daily: indicators.value.filter(i => i.frequency === 'daily').length,
      weekly: indicators.value.filter(i => i.frequency === 'weekly').length,
      monthly: indicators.value.filter(i => i.frequency === 'monthly').length,
      quarterly: indicators.value.filter(i => i.frequency === 'quarterly').length,
      yearly: indicators.value.filter(i => i.frequency === 'yearly').length,
    },
    byType: {
      quantitative: indicators.value.filter(i => i.type === 'quantitative').length,
      qualitative: indicators.value.filter(i => i.type === 'qualitative').length,
    },
  }))

  async function fetchIndicators (params?: IndicatorsListParams) {
    loading.value = true
    error.value = null
    try {
      const response = await indicatorsService.getAll(params)
      indicators.value = response.data
      if (response.meta) {
        pagination.value = {
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
          per_page: response.meta.per_page,
          total: response.meta.total,
        }
      }
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch indicators'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchIndicator (id: number) {
    loading.value = true
    error.value = null
    try {
      currentIndicator.value = await indicatorsService.getById(id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch indicator'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createIndicator (data: CreateIndicatorDTO) {
    loading.value = true
    error.value = null
    try {
      const newIndicator = await indicatorsService.create(data)
      indicators.value.push(newIndicator)
      return newIndicator
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create indicator'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateIndicator (id: number, data: UpdateIndicatorDTO) {
    loading.value = true
    error.value = null
    try {
      const updated = await indicatorsService.update(id, data)
      const index = indicators.value.findIndex(i => i.id === id)
      if (index !== -1) {
        indicators.value[index] = updated
      }
      if (currentIndicator.value?.id === id) {
        currentIndicator.value = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update indicator'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteIndicator (id: number) {
    loading.value = true
    error.value = null
    try {
      await indicatorsService.delete(id)
      indicators.value = indicators.value.filter(i => i.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete indicator'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function recordValue (indicatorId: number, data: RecordValueDTO) {
    loading.value = true
    error.value = null
    try {
      const newValue = await indicatorsService.recordValue(indicatorId, data)
      values.value.unshift(newValue)
      return newValue
    } catch (error_: any) {
      error.value = error_.message || 'Failed to record value'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory (indicatorId: number, params?: { limit?: number, from?: string, to?: string }) {
    loading.value = true
    error.value = null
    try {
      values.value = await indicatorsService.getHistory(indicatorId, params)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch history'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchChart (indicatorId: number, params?: { periods?: number, from?: string, to?: string }) {
    loading.value = true
    error.value = null
    try {
      chartData.value = await indicatorsService.getChart(indicatorId, params)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch chart data'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    indicators,
    currentIndicator,
    values,
    chartData,
    loading,
    error,
    pagination,
    activeIndicators,
    indicatorsByCategory,
    stats,
    fetchIndicators,
    fetchIndicator,
    createIndicator,
    updateIndicator,
    deleteIndicator,
    recordValue,
    fetchHistory,
    fetchChart,
  }
}
