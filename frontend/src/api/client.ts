import type {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios'
import type { ApiError } from '@/types/api'
import { http } from '@/api/http-client'
import { useToast } from '@/modules/shared/composables/useToast'
import { getBlockingState } from '@/utils/blockingAccess'
import { clearBrowserSessionData } from '@/utils/sessionSecurity'

type ApiClientInstance = AxiosInstance & {
  upload: <T = any>(
    url: string,
    formData: FormData,
    onProgress?: (progress: number) => void,
  ) => Promise<{ data: T }>
  download: (url: string, filename?: string) => Promise<void>
}

type RetryableRequestConfig = InternalAxiosRequestConfig & {
  __retryCount?: number
}

const api = http.getInstance() as ApiClientInstance
let pendingMfaRequest: InternalAxiosRequestConfig | null = null
const USE_HTTPONLY_COOKIE_AUTH = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false') === 'true'

function setPendingMfaRequest (config?: InternalAxiosRequestConfig) {
  if (!config) {
    return
  }
  pendingMfaRequest = { ...config }
}

export function clearPendingMfaRequest (): void {
  pendingMfaRequest = null
}

export async function retryPendingMfaRequest (): Promise<void> {
  if (!pendingMfaRequest) {
    return
  }
  const config = pendingMfaRequest
  pendingMfaRequest = null
  await api.request({
    ...config,
    __retryCount: 0,
    headers: config.headers,
  } as RetryableRequestConfig)
}

const DEFAULT_MAX_RETRIES = 1
const LOGIN_MAX_RETRIES = 2

function getMaxRetriesForRequest (
  config: InternalAxiosRequestConfig | undefined,
): number {
  const url = String(config?.url || '')
  if (url.includes('/auth/login')) {
    return LOGIN_MAX_RETRIES
  }
  return DEFAULT_MAX_RETRIES
}

function computeRetryDelayMs (retryCount: number): number {
  // Linear backoff to smooth transient network spikes
  return 800 * retryCount
}
const MAX_RETRIES = 1

function getRetryAfterDelayMs (error: AxiosError<ApiError>): number {
  const rawHeader = error.response?.headers?.['retry-after']
  if (!rawHeader) {
    return 0
  }

  const asNumber = Number(rawHeader)
  if (Number.isFinite(asNumber) && asNumber > 0) {
    return asNumber * 1000
  }

  const asDate = Date.parse(String(rawHeader))
  if (Number.isFinite(asDate)) {
    return Math.max(0, asDate - Date.now())
  }

  return 0
}

function clearAuthAndRedirectLogin (): void {
  clearBrowserSessionData()
  if (!window.location.pathname.includes('/auth/login')) {
    window.location.href = '/auth/login'
  }
}

function handleLockedResponse (
  data: any,
  toast: ReturnType<typeof useToast>,
  config?: InternalAxiosRequestConfig,
): void {
  const code = data?.code as string | undefined
  if (code === 'MFA_REQUIRED') {
    setPendingMfaRequest(config)
    if (typeof window !== 'undefined') {
      window.dispatchEvent(
        new CustomEvent('mfa-required', {
          detail: {
            token: data?.mfa_token,
            expires_at: data?.mfa_expires_at,
            message: data?.message,
          },
        }),
      )
    }
    toast.info(data?.message || 'Verification requise')
    return
  }
  const backendRedirect
    = typeof data?.redirect === 'string' ? data.redirect : null
  let currentUser: any = null

  try {
    currentUser = JSON.parse(localStorage.getItem('user') || 'null')
  } catch {
    currentUser = null
  }

  const blockingState = getBlockingState(code, currentUser)
  const fallbackRedirect = blockingState?.redirectPath || null
  const redirectPath = backendRedirect || fallbackRedirect
  const message = data?.message || blockingState?.message || 'Action requise'

  if (!redirectPath && !blockingState) {
    toast.error(data?.message || 'Action requise')
    return
  }

  toast.error(message)
  const currentPathWithQuery = `${window.location.pathname}${window.location.search}`
  if (redirectPath && currentPathWithQuery !== redirectPath) {
    window.location.href = redirectPath
  }
}

function notifyApiError (
  status: number | undefined,
  data: any,
  retryAction?: (() => void) | null,
  config?: InternalAxiosRequestConfig,
): void {
  const toast = useToast()
  const formatMessage = (fallbackMessage: string) => {
    const backendMessage
      = typeof data?.message === 'string' && data.message.trim().length > 0
        ? data.message.trim()
        : fallbackMessage

    const correlationId
      = typeof data?.correlation_id === 'string' && data.correlation_id.trim().length > 0
        ? data.correlation_id.trim()
        : ''

    return correlationId
      ? `${backendMessage} (Ref: ${correlationId})`
      : backendMessage
  }
  const errorToast = (message: string, withRetry = false) => {
    toast.error(
      formatMessage(message),
      'Erreur',
      undefined,
      withRetry && retryAction
        ? { actionLabel: 'Réessayer', action: retryAction }
        : undefined,
    )
  }

  if (status) {
    switch (status) {
      case 403: {
        errorToast('Action non autorisée. Vous n\'avez pas les permissions nécessaires.')
        break
      }
      case 404: {
        errorToast('Ressource non trouvée')
        break
      }
      case 423: {
        handleLockedResponse(data, toast, config)
        break
      }
      case 429: {
        errorToast('Trop de requêtes. Veuillez patienter.')
        break
      }
      case 422: {
        errorToast('Veuillez corriger les champs en erreur.')
        break
      }
      default: {
        if (status >= 500) {
          errorToast('Erreur serveur. Veuillez réessayer plus tard.', true)
        } else if (status !== 422) {
          errorToast('Une erreur est survenue')
        }
      }
    }
  } else {
    errorToast(
      'Impossible de se connecter au serveur. Vérifiez votre connexion.',
      true,
    )
  }
}

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = USE_HTTPONLY_COOKIE_AUTH
      ? null
      : localStorage.getItem('access_token')
    if (!USE_HTTPONLY_COOKIE_AUTH && token && config.headers && !config.headers.Authorization) {
      config.headers.Authorization = `Bearer ${token}`
    }

    const currentSiteId = localStorage.getItem('current_site_id')
    if (currentSiteId && config.headers && !config.headers['X-Site-ID']) {
      config.headers['X-Site-ID'] = currentSiteId
    }

    return config
  },
  error => Promise.reject(error),
)

api.interceptors.response.use(
  response => response,
  async (error: AxiosError<ApiError>) => {
    const config = error.config as
      | (InternalAxiosRequestConfig & { __retryCount?: number })
      | undefined
    const shouldSkipToast = config?.headers?.['X-Skip-Error-Toast'] === 'true'
    const status = error.response?.status
    const method = String(config?.method || '').toLowerCase()
    const requestUrl = String(config?.url || '')

    const maxRetries = getMaxRetriesForRequest(config)

    if (
      config
      && (error.code === 'ECONNABORTED'
        || error.code === 'ERR_NETWORK'
        || !error.response)
      && (config.__retryCount || 0) < MAX_RETRIES
    ) {
      config.__retryCount = (config.__retryCount || 0) + 1
      await new Promise(resolve => setTimeout(resolve, 1000))
      return api.request(config)
    }

    if (status === 429) {
      throw error
    }

    if (status === 401) {
      const skipAuthRedirect
        = config?.headers?.['X-Skip-Auth-Redirect'] === 'true'
          || requestUrl.startsWith('/geo/')
      if (!skipAuthRedirect) {
        clearAuthAndRedirectLogin()
      }
      throw error
    }

    if (!shouldSkipToast) {
      const data = error.response?.data as any
      const method = String(config?.method || '').toLowerCase()
      const isIdempotentMethod = [
        'get',
        'head',
        'options',
        'put',
        'delete',
      ].includes(method)
      const canOfferRetry
        = Boolean(config)
          && isIdempotentMethod
          && (status === undefined || (status ?? 0) >= 500)
      const retryAction
        = canOfferRetry && config
          ? () => {
              const retryConfig = {
                ...config,
                __retryCount: 0,
                headers: config.headers,
              }
              api.request(retryConfig as RetryableRequestConfig)
            }
          : null

      if (error.response) {
        notifyApiError(status, data, retryAction, config)
      } else {
        notifyApiError(undefined, null, retryAction, config)
      }
    }

    throw error
  },
)

api.upload = async function upload<T = any> (
  url: string,
  formData: FormData,
  onProgress?: (progress: number) => void,
): Promise<{ data: T }> {
  const response = await api.post<T>(url, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    onUploadProgress: progressEvent => {
      if (!onProgress || !progressEvent.total) {
        return
      }
      const progress = Math.round(
        (progressEvent.loaded * 100) / progressEvent.total,
      )
      onProgress(progress)
    },
  })

  return response as unknown as { data: T }
}

api.download = async function download (
  url: string,
  filename?: string,
): Promise<void> {
  const response = await api.get(url, {
    responseType: 'blob',
  })

  const blobData = (response as any)?.data ?? response
  const blob = new Blob([blobData])
  const objectUrl = window.URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = objectUrl
  link.download = filename || 'download'
  document.body.append(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(objectUrl)
}

export function getValidationErrors (error: any): Record<string, string[]> {
  if (error.response?.status === 422 && error.response?.data?.errors) {
    return error.response.data.errors
  }
  return {}
}

export function getErrorMessage (error: any): string {
  if (error.response?.data?.message) {
    const message = String(error.response.data.message)
    const correlationId = error.response?.data?.correlation_id
    if (typeof correlationId === 'string' && correlationId.trim().length > 0) {
      return `${message} (Ref: ${correlationId.trim()})`
    }
    return message
  }
  return error.message || 'Une erreur est survenue'
}

export function getAxiosApiClient (): AxiosInstance {
  return api
}

export { api as apiClient }
export default api
