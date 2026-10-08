/**
 * Composable pour gérer les filtres de recherche
 * Réutilisable dans tous les modules avec debounce
 */

import { debounce } from 'lodash'
import { computed, ref, watch } from 'vue'

export interface UseFiltersOptions<T> {
  initialFilters?: Partial<T>
  debounceMs?: number
  onFilterChange?: (filters: T) => void
}

export function useFilters<T extends Record<string, any>> (
  defaultFilters: T,
  options: UseFiltersOptions<T> = {},
) {
  const {
    initialFilters = {},
    debounceMs = 300,
    onFilterChange,
  } = options

  // State
  const filters = ref<T>({
    ...defaultFilters,
    ...initialFilters,
  } as T)

  const activeFiltersCount = computed(() => {
    let count = 0
    for (const [key, value] of Object.entries(filters.value)) {
      // Ne pas compter les filtres par défaut (pagination, etc.)
      if (key === 'page' || key === 'per_page') {
        continue
      }

      if (value !== null && value !== undefined && value !== '') {
        if (Array.isArray(value) && value.length > 0) {
          count++
        } else if (!Array.isArray(value)) {
          count++
        }
      }
    }
    return count
  })

  const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

  // Debounced callback
  const debouncedOnChange = debounce((newFilters: T) => {
    onFilterChange?.(newFilters)
  }, debounceMs)

  // Actions
  function setFilter<K extends keyof T> (key: K, value: T[K]) {
    filters.value[key] = value

    // Reset page to 1 when filter changes
    if (key !== 'page' && 'page' in filters.value) {
      filters.value.page = 1 as T[Extract<keyof T, 'page'>]
    }
  }

  function setFilters (newFilters: Partial<T>) {
    filters.value = {
      ...filters.value,
      ...newFilters,
    }
  }

  function resetFilters () {
    filters.value = { ...defaultFilters } as T
    debouncedOnChange(filters.value)
  }

  function resetFilter<K extends keyof T> (key: K) {
    if (key in defaultFilters) {
      filters.value[key] = defaultFilters[key]
    }
  }

  function removeFilter<K extends keyof T> (key: K) {
    if (key === 'page' || key === 'per_page') {
      return
    } // Don't remove pagination

    filters.value[key] = Array.isArray(filters.value[key]) ? [] as T[K] : null as T[K]
  }

  // Génère les query params pour l'URL
  const queryParams = computed(() => {
    const params: Record<string, any> = {}

    for (const [key, value] of Object.entries(filters.value)) {
      if (value !== null && value !== undefined && value !== '') {
        if (Array.isArray(value)) {
          if (value.length > 0) {
            params[key] = value
          }
        } else {
          params[key] = value
        }
      }
    }

    return params
  })

  // Watch pour trigger callback
  watch(
    filters,
    newFilters => {
      debouncedOnChange(newFilters)
    },
    { deep: true },
  )

  return {
    // State
    filters,

    // Computed
    activeFiltersCount,
    hasActiveFilters,
    queryParams,

    // Actions
    setFilter,
    setFilters,
    resetFilters,
    resetFilter,
    removeFilter,
  }
}
