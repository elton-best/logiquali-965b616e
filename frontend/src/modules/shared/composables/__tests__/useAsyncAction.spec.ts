import { describe, expect, it, vi } from 'vitest'
import { useAsyncAction } from '../useAsyncAction'

describe('useAsyncAction', () => {
  it('sets loading to true during execution', async () => {
    const { loading, execute } = useAsyncAction()

    const promise = execute(async () => {
      expect(loading.value).toBe(true)
      return 'result'
    })

    await promise
    expect(loading.value).toBe(false)
  })

  it('calls onSuccess callback', async () => {
    const { execute } = useAsyncAction()
    const onSuccess = vi.fn()

    await execute(async () => 'result', { onSuccess })

    expect(onSuccess).toHaveBeenCalledWith('result')
  })

  it('calls onError callback on failure', async () => {
    const { execute } = useAsyncAction()
    const onError = vi.fn()
    const error = new Error('Test error')

    await execute(async () => {
      throw error
    }, { onError })

    expect(onError).toHaveBeenCalledWith(error)
  })

  it('respects minDelay', async () => {
    const { execute } = useAsyncAction()
    const start = Date.now()
    const minDelay = 300
    const toleranceMs = 10

    await execute(async () => 'fast', { minDelay })

    const elapsed = Date.now() - start
    expect(elapsed).toBeGreaterThanOrEqual(minDelay - toleranceMs)
  })

  it('returns null on error', async () => {
    const { execute } = useAsyncAction()

    const result = await execute(async () => {
      throw new Error('Forced error for test')
    })

    expect(result).toBeNull()
  })
})
