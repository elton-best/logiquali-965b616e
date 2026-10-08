import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useContextLoading, useDataLoading } from '@/composables/useContextLoading'

// Mock useLoading
vi.mock('@/composables/useLoading', () => ({
  useLoading: vi.fn(() => ({
    loading: { value: false },
    error: { value: null },
    start: vi.fn(),
    stop: vi.fn(),
    retry: vi.fn(),
    cancel: vi.fn(),
  })),
}))

describe('useContextLoading', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should initialize with correct context config for dashboard', () => {
    const { config, LoadingComponent } = useContextLoading('dashboard')

    expect(config.type).toBe('skeleton')
    expect(config.timeout).toBe(10_000)
    expect(config.message).toBe('Chargement du tableau de bord...')
    expect(LoadingComponent.value).toBe('UniversalSkeleton')
  })

  it('should initialize with correct context config for form', () => {
    const { config, LoadingComponent } = useContextLoading('form')

    expect(config.type).toBe('local')
    expect(config.timeout).toBe(15_000)
    expect(config.message).toBe('Enregistrement en cours...')
    expect(LoadingComponent.value).toBe('LoadingStates')
  })

  it('should initialize with correct context config for export', () => {
    const { config, LoadingComponent } = useContextLoading('export')

    expect(config.type).toBe('progress')
    expect(config.timeout).toBe(60_000)
    expect(config.showProgress).toBe(true)
    expect(config.cancellable).toBe(true)
    expect(LoadingComponent.value).toBe('LoadingStates')
  })

  it('should handle progress updates correctly', () => {
    const { progress, stage, updateProgress } = useContextLoading('export')

    expect(progress.value).toBe(0)
    expect(stage.value).toBe('initial')

    updateProgress(50, 'processing')
    expect(progress.value).toBe(50)
    expect(stage.value).toBe('processing')

    // Test bounds
    updateProgress(-10)
    expect(progress.value).toBe(0)

    updateProgress(150)
    expect(progress.value).toBe(100)
  })

  it('should generate contextual messages based on stage', () => {
    const { contextMessage, _stage, updateProgress } = useContextLoading('export')

    // Initial stage
    expect(contextMessage.value).toBe('Export en cours...')

    // Processing stage
    updateProgress(50, 'processing')
    expect(contextMessage.value).toBe('Génération du fichier...')

    // Finalizing stage
    updateProgress(90, 'finalizing')
    expect(contextMessage.value).toBe('Finalisation...')
  })

  it('should handle completion correctly', () => {
    const { progress, stage, complete, stop } = useContextLoading('export')

    complete()

    expect(progress.value).toBe(100)
    expect(stage.value).toBe('initial')
    expect(stop).toHaveBeenCalled()
  })

  it('should handle different context types correctly', () => {
    const contexts = ['dashboard', 'form', 'table', 'export', 'import', 'auth', 'navigation', 'search', 'upload']

    for (const context of contexts) {
      const { config } = useContextLoading(context as any)

      expect(config.type).toBeDefined()
      expect(config.timeout).toBeGreaterThan(0)
      expect(config.message).toBeTruthy()
      expect(typeof config.cancellable).toBe('boolean')
    }
  })
})

describe('useDataLoading', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('should handle successful data fetching', async () => {
    const mockData = { id: 1, name: 'Test' }
    const mockFetcher = vi.fn().mockResolvedValue(mockData)

    const { data, execute, retryCount } = useDataLoading('dashboard', mockFetcher)

    const result = await execute()

    expect(mockFetcher).toHaveBeenCalledTimes(1)
    expect(data.value).toEqual(mockData)
    expect(result).toEqual(mockData)
    expect(retryCount.value).toBe(0)
  })

  it('should handle retry logic on failure', async () => {
    const mockError = new Error('Network error')
    const mockFetcher = vi.fn()
      .mockRejectedValueOnce(mockError)
      .mockRejectedValueOnce(mockError)
      .mockResolvedValueOnce({ success: true })

    const { data, execute, retryCount } = useDataLoading('dashboard', mockFetcher, {
      retries: 3,
      retryDelay: 100,
    })

    const executePromise = execute()

    // Avancer le temps pour les retries
    await vi.advanceTimersByTimeAsync(300)

    const result = await executePromise

    expect(mockFetcher).toHaveBeenCalledTimes(3)
    expect(data.value).toEqual({ success: true })
    expect(result).toEqual({ success: true })
    expect(retryCount.value).toBe(0) // Reset après succès
  })

  it('should fail after max retries', async () => {
    const mockError = new Error('Persistent error')
    const mockFetcher = vi.fn().mockRejectedValue(mockError)
    const mockOnError = vi.fn()

    const { execute, retryCount, error } = useDataLoading('dashboard', mockFetcher, {
      retries: 2,
      retryDelay: 100,
      onError: mockOnError,
    })

    const executePromise = execute()
    const rejectionAssertion = expect(executePromise).rejects.toThrow('Persistent error')
    await vi.advanceTimersByTimeAsync(500)
    await rejectionAssertion

    expect(mockFetcher).toHaveBeenCalledTimes(3) // Initial + 2 retries
    expect(retryCount.value).toBe(2)
    expect(mockOnError).toHaveBeenCalledWith(mockError)
    expect(error.value).toBe('Persistent error')
  })

  it('should handle refresh functionality', async () => {
    const mockData1 = { version: 1 }
    const mockData2 = { version: 2 }
    const mockFetcher = vi.fn()
      .mockResolvedValueOnce(mockData1)
      .mockResolvedValueOnce(mockData2)

    const { data, execute, refresh, retryCount } = useDataLoading('dashboard', mockFetcher)

    // Premier fetch
    await execute()
    expect(data.value).toEqual(mockData1)

    // Simuler des retries précédents
    retryCount.value = 2

    // Refresh devrait reset le retry count
    await refresh()
    expect(data.value).toEqual(mockData2)
    expect(retryCount.value).toBe(0)
    expect(mockFetcher).toHaveBeenCalledTimes(2)
  })

  it('should handle different retry delays', async () => {
    const mockError = new Error('Retry test')
    const mockFetcher = vi.fn()
      .mockRejectedValueOnce(mockError)
      .mockResolvedValueOnce({ success: true })

    const { execute } = useDataLoading('dashboard', mockFetcher, {
      retries: 1,
      retryDelay: 500,
    })

    const _startTime = Date.now()
    const executePromise = execute()

    // Avancer le temps pour le retry delay
    await vi.advanceTimersByTimeAsync(500)

    await executePromise

    expect(mockFetcher).toHaveBeenCalledTimes(2)
  })
})
