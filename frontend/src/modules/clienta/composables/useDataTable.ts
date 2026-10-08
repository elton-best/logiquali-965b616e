import type { Ref } from 'vue'
import { computed, ref, watch } from 'vue'

export interface DataTableOptions {
  search?: string
  filters?: Record<string, any>
  sortBy?: { key: string, order: 'asc' | 'desc' }[]
  itemsPerPage?: number
}

export function useDataTable<T = any> (
  items: Ref<T[]> | T[],
  options: DataTableOptions = {},
) {
  const search = ref(options.search || '')
  const filters = ref(options.filters || {})
  const sortBy = ref(options.sortBy || [])
  const itemsPerPage = ref(options.itemsPerPage || 10)
  const page = ref(1)

  // Filtered items based on search and filters
  const filteredItems = computed(() => {
    let result = Array.isArray(items) ? items : items.value

    // Apply search
    if (search.value) {
      const searchLower = search.value.toLowerCase()
      result = result.filter((item: any) => {
        return Object.values(item).some(val =>
          String(val).toLowerCase().includes(searchLower),
        )
      })
    }

    // Apply filters
    for (const [key, value] of Object.entries(filters.value)) {
      if (value !== null && value !== undefined && value !== '') {
        result = result.filter((item: any) => {
          if (Array.isArray(value)) {
            return value.includes(item[key])
          }
          return item[key] === value
        })
      }
    }

    return result
  })

  // Paginated items
  const paginatedItems = computed(() => {
    const start = (page.value - 1) * itemsPerPage.value
    const end = start + itemsPerPage.value
    return filteredItems.value.slice(start, end)
  })

  // Total pages
  const totalPages = computed(() =>
    Math.ceil(filteredItems.value.length / itemsPerPage.value),
  )

  // Update filter
  function updateFilter (key: string, value: any) {
    filters.value = { ...filters.value, [key]: value }
    page.value = 1 // Reset to first page
  }

  // Reset all filters
  function resetFilters () {
    filters.value = {}
    search.value = ''
    page.value = 1
  }

  // Update search
  function updateSearch (value: string) {
    search.value = value
    page.value = 1
  }

  // Reset page when filters change
  watch(() => filters.value, () => {
    page.value = 1
  }, { deep: true })

  return {
    search,
    filters,
    sortBy,
    itemsPerPage,
    page,
    filteredItems,
    paginatedItems,
    totalPages,
    updateFilter,
    resetFilters,
    updateSearch,
  }
}
