import { onMounted, ref } from 'vue'

export interface AsyncDataOptions<T> {
  immediate?: boolean
  onSuccess?: (data: T) => void
  onError?: (error: any) => void
  minDelay?: number
}

export function useAsyncData<T> (
  fetcher: () => Promise<T>,
  options: AsyncDataOptions<T> = {},
) {
  const data = ref<T | null>(null)
  const loading = ref(false)
  const error = ref<Error | null>(null)

  const load = async () => {
    loading.value = true
    error.value = null
    const startTime = Date.now()

    try {
      const result = await fetcher()

      const elapsed = Date.now() - startTime
      const minDelay = options.minDelay || 300
      if (elapsed < minDelay) {
        await new Promise(resolve => setTimeout(resolve, minDelay - elapsed))
      }

      data.value = result as any
      options.onSuccess?.(result)
    } catch (error_: any) {
      error.value = error_
      options.onError?.(error_)
    } finally {
      loading.value = false
    }
  }

  const refresh = () => load()

  if (options.immediate !== false) {
    onMounted(() => load())
  }

  return {
    data,
    loading,
    error,
    load,
    refresh,
  }
}
