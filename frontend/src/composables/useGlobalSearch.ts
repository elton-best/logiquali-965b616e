import { computed, ref } from 'vue'

export interface SearchFilters {
  [key: string]: any
  query: string
  dateRange: [string, string] | null
  status: string[]
  type: string[]
  site: string | null
}

export function useGlobalSearch () {
  const readResults = async (response: unknown) => {
    if (!response || typeof (response as any).json !== 'function') {
      return []
    }

    const payload = await (response as Response).json().catch(() => null)
    if (!payload || typeof payload !== 'object') {
      return []
    }

    return Array.isArray((payload as any).results) ? (payload as any).results : []
  }

  const debounce = <T extends (...args: any[]) => void>(fn: T, wait = 300) => {
    let timeoutId: ReturnType<typeof setTimeout> | null = null
    return (...args: Parameters<T>) => {
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
      timeoutId = setTimeout(() => fn(...args), wait)
    }
  }

  const searchQuery = ref('')
  const filters = ref<SearchFilters>({
    query: '',
    dateRange: null,
    status: [],
    type: [],
    site: null,
  })
  const isSearching = ref(false)
  const searchResults = ref([])

  const hasActiveFilters = computed(() => {
    return filters.value.query.length > 0
      || filters.value.dateRange !== null
      || filters.value.status.length > 0
      || filters.value.type.length > 0
      || filters.value.site !== null
  })

  const debouncedSearch = debounce(async (query: string) => {
    if (query.length < 2) {
      searchResults.value = []
      return
    }

    isSearching.value = true
    try {
      const response = await fetch(`/api/v1/search?q=${encodeURIComponent(query)}`)
      searchResults.value = await readResults(response)
    } catch (error) {
      console.error('Search error:', error)
      searchResults.value = []
    } finally {
      isSearching.value = false
    }
  }, 300)

  const search = (query: string) => {
    searchQuery.value = query
    filters.value.query = query
    debouncedSearch(query)
  }

  const clearSearch = () => {
    searchQuery.value = ''
    filters.value.query = ''
    searchResults.value = []
  }

  const clearFilters = () => {
    filters.value = {
      query: '',
      dateRange: null,
      status: [],
      type: [],
      site: null,
    }
    searchResults.value = []
  }

  const applyFilters = async (newFilters: Partial<SearchFilters>) => {
    filters.value = { ...filters.value, ...newFilters }

    if (hasActiveFilters.value) {
      isSearching.value = true
      try {
        const response = await fetch('/api/v1/search/advanced', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(filters.value),
        })
        searchResults.value = await readResults(response)
      } catch (error) {
        console.error('Advanced search error:', error)
        searchResults.value = []
      } finally {
        isSearching.value = false
      }
    }
  }

  return {
    searchQuery,
    filters,
    isSearching,
    searchResults,
    hasActiveFilters,
    search,
    clearSearch,
    clearFilters,
    applyFilters,
  }
}
