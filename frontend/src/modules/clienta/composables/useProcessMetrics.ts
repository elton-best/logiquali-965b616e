import type { AxiosError } from 'axios'
import axios from 'axios'
import { ref } from 'vue'

interface ProcessMetric {
  process_id: number
  process_name: string
  metrics: {
    pip_total: number
    pip_completed: number
    risks_count: number
    opportunities_count: number
    objectives_count: number
    objective_rate: number
    non_conformities_count: number
    satisfaction_rate: number
    duerp_versions_count: number
    duerp_dangers_count: number
    duerp_unacceptable_count: number
    aes_total_count: number
    aes_significant_count: number
    [key: string]: any
  }
}

export function useProcessMetrics () {
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchMetrics = async (processId: number): Promise<ProcessMetric | null> => {
    loading.value = true
    error.value = null

    try {
      const response = await axios.get<{ success: boolean, data: ProcessMetric }>(`/api/v1/processes/${processId}/metrics`)
      return response.data.data || null
    } catch (error_) {
      const axiosError = error_ as AxiosError
      error.value = axiosError.message || 'Erreur lors du chargement des métriques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchMetrics,
  }
}
