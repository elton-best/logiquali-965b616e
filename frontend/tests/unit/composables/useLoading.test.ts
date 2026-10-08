import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { LoadingUtils, useApiCall, useLoading } from '@/composables/useLoading'
import { useGlobalLoaderStore } from '@/stores/globalLoader'

// Mock du store global
const mockStore = {
  startLoading: vi.fn(),
  stopLoading: vi.fn(),
  resetLoading: vi.fn(),
  isLoading: false,
}

vi.mock('@/stores/globalLoader', () => ({
  useGlobalLoaderStore: vi.fn(() => mockStore),
}))

describe('useLoading', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    vi.useFakeTimers()
    mockStore.startLoading.mockClear()
    mockStore.stopLoading.mockClear()
    mockStore.resetLoading.mockClear()
  })

  afterEach(() => {
    vi.useRealTimers()
    LoadingUtils.stopAll()
  })

  it('should initialize with correct default state', () => {
    const { loading, error } = useLoading()

    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('should start and stop loading correctly', () => {
    const { loading, start, stop } = useLoading()

    start()
    expect(loading.value).toBe(true)

    stop()
    expect(loading.value).toBe(false)
  })

  it('should handle global loading type', () => {
    useGlobalLoaderStore()
    const { start, stop } = useLoading()

    start({ type: 'global' })
    expect(mockStore.startLoading).toHaveBeenCalled()

    stop()
    expect(mockStore.stopLoading).toHaveBeenCalled()
  })

  it('should handle timeout correctly', () => {
    const { loading, error, start } = useLoading()

    start({ timeout: 1000 })
    expect(loading.value).toBe(true)
    expect(error.value).toBeNull()

    // Avancer le temps
    vi.advanceTimersByTime(1000)

    expect(loading.value).toBe(false)
    expect(error.value).toBe('Timeout: Opération trop longue')
  })

  it('should handle retry functionality', () => {
    const { loading, error, start, stop, retry } = useLoading()

    // Simuler une erreur
    start()
    stop()
    error.value = 'Test error'

    // Retry devrait réinitialiser l'erreur et redémarrer
    retry()
    expect(error.value).toBeNull()
    expect(loading.value).toBe(true)
  })

  it('should handle cancel functionality', () => {
    const { loading, error, start, cancel } = useLoading()

    start()
    cancel()

    expect(loading.value).toBe(false)
    expect(error.value).toBe('Opération annulée')
  })

  it('should track multiple loaders correctly', () => {
    const loader1 = useLoading({ context: 'test1' })
    const loader2 = useLoading({ context: 'test2' })

    loader1.start()
    loader2.start()

    const activeLoaders = LoadingUtils.getActive()
    expect(activeLoaders).toHaveLength(2)
    expect(activeLoaders.some(l => l.config.context === 'test1')).toBe(true)
    expect(activeLoaders.some(l => l.config.context === 'test2')).toBe(true)
  })
})

describe('useApiCall', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should handle successful API call', async () => {
    const mockApiCall = vi.fn().mockResolvedValue({ data: 'test' })
    const { loading, error, data, execute } = useApiCall(mockApiCall)

    expect(loading.value).toBe(false)

    const result = await execute()

    expect(mockApiCall).toHaveBeenCalled()
    expect(data.value).toEqual({ data: 'test' })
    expect(result).toEqual({ data: 'test' })
    expect(error.value).toBeNull()
    expect(loading.value).toBe(false)
  })

  it('should handle API call error', async () => {
    const mockError = new Error('API Error')
    const mockApiCall = vi.fn().mockRejectedValue(mockError)
    const { loading, error, data, execute } = useApiCall(mockApiCall)

    await expect(execute()).rejects.toThrow('API Error')

    expect(error.value).toBe('API Error')
    expect(data.value).toBeNull()
    expect(loading.value).toBe(false)
  })

  it('should handle retry on API call', async () => {
    const mockApiCall = vi.fn()
      .mockRejectedValueOnce(new Error('First error'))
      .mockResolvedValueOnce({ data: 'success' })

    const { retry } = useApiCall(mockApiCall)

    // Premier appel échoue
    await expect(retry()).rejects.toThrow('First error')

    // Retry réussit
    const result = await retry()
    expect(result).toEqual({ data: 'success' })
    expect(mockApiCall).toHaveBeenCalledTimes(2)
  })
})

describe('LoadingUtils', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should stop all loaders', () => {
    const loader1 = useLoading()
    const loader2 = useLoading()

    loader1.start()
    loader2.start()

    expect(LoadingUtils.getActive()).toHaveLength(2)

    LoadingUtils.stopAll()

    expect(LoadingUtils.getActive()).toHaveLength(0)
  })

  it('should check context loading status', () => {
    const loader = useLoading({ context: 'dashboard' })

    expect(LoadingUtils.isContextLoading('dashboard')).toBe(false)

    loader.start()
    expect(LoadingUtils.isContextLoading('dashboard')).toBe(true)

    loader.stop()
    expect(LoadingUtils.isContextLoading('dashboard')).toBe(false)
  })

  it('should get pending count correctly', () => {
    expect(LoadingUtils.getPendingCount()).toBe(0)

    const loader1 = useLoading()
    const loader2 = useLoading()

    loader1.start()
    expect(LoadingUtils.getPendingCount()).toBe(1)

    loader2.start()
    expect(LoadingUtils.getPendingCount()).toBe(2)

    loader1.stop()
    expect(LoadingUtils.getPendingCount()).toBe(1)

    LoadingUtils.stopAll()
    expect(LoadingUtils.getPendingCount()).toBe(0)
  })
})
