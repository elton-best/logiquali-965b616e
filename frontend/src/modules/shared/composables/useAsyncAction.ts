import { ref } from 'vue'

export interface AsyncActionOptions {
  onSuccess?: (result: any) => void
  onError?: (error: any) => void
  minDelay?: number
}

export function useAsyncAction () {
  const loading = ref(false)
  const error = ref<Error | null>(null)

  const execute = async <T>(
    action: () => Promise<T>,
    options: AsyncActionOptions = {},
  ): Promise<T | null> => {
    loading.value = true
    error.value = null
    const startTime = Date.now()

    try {
      const result = await action()

      const elapsed = Date.now() - startTime
      const minDelay = options.minDelay || 0
      if (elapsed < minDelay) {
        await new Promise(resolve => setTimeout(resolve, minDelay - elapsed))
      }

      options.onSuccess?.(result)
      return result
    } catch (error_: any) {
      error.value = error_
      options.onError?.(error_)
      return null
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    execute,
  }
}
