/**
 * Composable for HTTP requests with error handling
 */

import type { AppError } from '@/utils/error-handler'
import { ref } from 'vue'
import { useNotification } from '@/plugins/notification'
import { getErrorMessage } from '@/utils/error-handler'

export interface UseApiOptions {
  showSuccessMessage?: boolean
  showErrorMessage?: boolean
  successMessage?: string
}

export function useApi<T = any> (
  apiCall: (...args: any[]) => Promise<T>,
  options: UseApiOptions = {},
) {
  const loading = ref(false)
  const error = ref<AppError | null>(null)
  const data = ref<T | null>(null)

  const notification = useNotification()

  const execute = async (...args: any[]): Promise<T | null> => {
    loading.value = true
    error.value = null

    try {
      const result = await apiCall(...args)
      data.value = result

      if (options.showSuccessMessage && options.successMessage) {
        notification.success(options.successMessage)
      }

      return result
    } catch (error_: any) {
      error.value = error_

      if (options.showErrorMessage !== false) {
        notification.error(getErrorMessage(error_))
      }

      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    loading: readonly(loading),
    error: readonly(error),
    data: readonly(data),
    execute,
  }
}
