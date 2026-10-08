import { computed, ref } from 'vue'
import { apiClient } from '@/api/client'

export interface Norm {
  id: string | number
  code: string
  name: string
  full_name: string
}

export function useNorms () {
  const norms = ref<Norm[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const selectedNorms = ref<(string | number)[]>([])

  const normsOptions = computed(() =>
    norms.value.map(norm => ({
      title: norm.full_name,
      value: norm.id,
    })),
  )

  const filteredNorms = computed(() =>
    selectedNorms.value.length > 0
      ? norms.value.filter(norm => selectedNorms.value.includes(norm.id))
      : norms.value,
  )

  async function fetchActiveNorms () {
    loading.value = true
    error.value = null
    try {
      const response = await apiClient.get('/documents/active-norms')
      norms.value = response.data.data || []
    } catch (error_) {
      error.value = error_ instanceof Error ? error_.message : 'Failed to fetch norms'
      console.error('Error fetching norms:', error_)
    } finally {
      loading.value = false
    }
  }

  function toggleNorm (normId: string | number) {
    const index = selectedNorms.value.indexOf(normId)
    if (index === -1) {
      selectedNorms.value.push(normId)
    } else {
      selectedNorms.value.splice(index, 1)
    }
  }

  function clearNormFilters () {
    selectedNorms.value = []
  }

  function getNormName (normId: string | number): string {
    const norm = norms.value.find(n => n.id === normId)
    return norm?.full_name || String(normId)
  }

  return {
    norms,
    loading,
    error,
    selectedNorms,
    normsOptions,
    filteredNorms,
    fetchActiveNorms,
    toggleNorm,
    clearNormFilters,
    getNormName,
  }
}
