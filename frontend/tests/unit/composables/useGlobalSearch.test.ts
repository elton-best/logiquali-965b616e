import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { useGlobalSearch } from '@/composables/useGlobalSearch'

// Mock fetch
global.fetch = vi.fn()

describe('useGlobalSearch', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('should initialize with correct default state', () => {
    const {
      searchQuery,
      filters,
      isSearching,
      searchResults,
      hasActiveFilters,
    } = useGlobalSearch()

    expect(searchQuery.value).toBe('')
    expect(filters.value.query).toBe('')
    expect(filters.value.dateRange).toBeNull()
    expect(filters.value.status).toEqual([])
    expect(filters.value.type).toEqual([])
    expect(filters.value.site).toBeNull()
    expect(isSearching.value).toBe(false)
    expect(searchResults.value).toEqual([])
    expect(hasActiveFilters.value).toBe(false)
  })

  it('should detect active filters correctly', () => {
    const { _filters, hasActiveFilters, applyFilters } = useGlobalSearch()

    expect(hasActiveFilters.value).toBe(false)

    // Ajouter un filtre
    applyFilters({ query: 'test' })
    expect(hasActiveFilters.value).toBe(true)

    // Ajouter des filtres multiples
    applyFilters({
      status: ['active'],
      dateRange: ['2024-01-01', '2024-12-31'],
    })
    expect(hasActiveFilters.value).toBe(true)
  })

  it('should handle search with debounce', async () => {
    const mockResults = [
      { id: 1, title: 'Test Result', type: 'document' },
    ]

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ results: mockResults }),
    } as Response)

    const { search, searchResults, isSearching } = useGlobalSearch()

    search('test query')

    expect(isSearching.value).toBe(false) // Pas encore démarré (debounce)

    // Avancer le temps pour déclencher le debounce
    await vi.advanceTimersByTimeAsync(300)

    expect(fetch).toHaveBeenCalledWith('/api/v1/search?q=test%20query')

    // Attendre la résolution de la promesse
    await vi.waitFor(() => {
      expect(searchResults.value).toEqual(mockResults)
    })
  })

  it('should not search for queries less than 2 characters', async () => {
    const { search, searchResults } = useGlobalSearch()

    search('a')
    await vi.advanceTimersByTimeAsync(300)

    expect(fetch).not.toHaveBeenCalled()
    expect(searchResults.value).toEqual([])
  })

  it('should handle search errors gracefully', async () => {
    vi.mocked(fetch).mockRejectedValueOnce(new Error('Network error'))

    const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {})

    const { search, searchResults, isSearching } = useGlobalSearch()

    search('test query')
    await vi.advanceTimersByTimeAsync(300)

    await vi.waitFor(() => {
      expect(isSearching.value).toBe(false)
      expect(searchResults.value).toEqual([])
      expect(consoleSpy).toHaveBeenCalledWith('Search error:', expect.any(Error))
    })

    consoleSpy.mockRestore()
  })

  it('should clear search correctly', () => {
    const { search, clearSearch, searchQuery, searchResults } = useGlobalSearch()

    search('test')
    expect(searchQuery.value).toBe('test')

    clearSearch()
    expect(searchQuery.value).toBe('')
    expect(searchResults.value).toEqual([])
  })

  it('should clear all filters correctly', () => {
    const { applyFilters, clearFilters, filters, hasActiveFilters } = useGlobalSearch()

    // Ajouter des filtres
    applyFilters({
      query: 'test',
      status: ['active'],
      type: ['document'],
      site: 'site1',
      dateRange: ['2024-01-01', '2024-12-31'],
    })

    expect(hasActiveFilters.value).toBe(true)

    clearFilters()

    expect(filters.value.query).toBe('')
    expect(filters.value.status).toEqual([])
    expect(filters.value.type).toEqual([])
    expect(filters.value.site).toBeNull()
    expect(filters.value.dateRange).toBeNull()
    expect(hasActiveFilters.value).toBe(false)
  })

  it('should handle advanced search with filters', async () => {
    const mockResults = [
      { id: 1, title: 'Filtered Result', type: 'process' },
    ]

    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ results: mockResults }),
    } as Response)

    const { applyFilters, searchResults } = useGlobalSearch()

    const filterData = {
      query: 'process',
      status: ['active'],
      type: ['process'],
      dateRange: ['2024-01-01', '2024-12-31'],
    }

    await applyFilters(filterData)

    expect(fetch).toHaveBeenCalledTimes(1)
    const [, request] = vi.mocked(fetch).mock.calls[0]
    expect(request?.method).toBe('POST')
    expect(request?.headers).toEqual({ 'Content-Type': 'application/json' })
    expect(JSON.parse(String(request?.body))).toEqual(expect.objectContaining(filterData))

    await vi.waitFor(() => {
      expect(searchResults.value).toEqual(mockResults)
    })
  })

  it('should handle multiple rapid searches correctly', async () => {
    const { search } = useGlobalSearch()

    // Faire plusieurs recherches rapidement
    search('first')
    search('second')
    search('third')

    // Seule la dernière devrait être exécutée après le debounce
    await vi.advanceTimersByTimeAsync(300)

    expect(fetch).toHaveBeenCalledTimes(1)
    expect(fetch).toHaveBeenCalledWith('/api/v1/search?q=third')
  })

  it('should update filters correctly with partial updates', () => {
    const { filters, applyFilters } = useGlobalSearch()

    // Premier update
    applyFilters({ query: 'test' })
    expect(filters.value.query).toBe('test')
    expect(filters.value.status).toEqual([])

    // Update partiel - ne devrait pas écraser les autres valeurs
    applyFilters({ status: ['active'] })
    expect(filters.value.query).toBe('test')
    expect(filters.value.status).toEqual(['active'])
  })

  it('should handle empty search results', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ results: [] }),
    } as Response)

    const { search, searchResults } = useGlobalSearch()

    search('nonexistent')
    await vi.advanceTimersByTimeAsync(300)

    await vi.waitFor(() => {
      expect(searchResults.value).toEqual([])
    })
  })

  it('should handle malformed API responses', async () => {
    vi.mocked(fetch).mockResolvedValueOnce({
      ok: true,
      json: async () => ({ /* pas de results */ }),
    } as Response)

    const { search, searchResults } = useGlobalSearch()

    search('test')
    await vi.advanceTimersByTimeAsync(300)

    await vi.waitFor(() => {
      expect(searchResults.value).toEqual([])
    })
  })
})
