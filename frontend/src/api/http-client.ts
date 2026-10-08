/**
 * HTTP Client with Axios
 * Configured for multi-tenant SaaS with JWT authentication
 */

import type { ApiResponse } from '@/types/api'
import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import { config } from '@/config'
import { STORAGE_KEYS } from '@/constants'
import { useGlobalLoaderStore } from '@/stores/globalLoader'
import { clearBrowserSessionData } from '@/utils/sessionSecurity'
import { storage } from '@/utils/storage'

interface LoadingRequestConfig extends AxiosRequestConfig {
  skipGlobalLoading?: boolean
  metadata?: { requestId: string }
}

const pendingRequests = new Map<string, boolean>()
const USE_HTTPONLY_COOKIE_AUTH = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false') === 'true'

function generateRequestId (config: LoadingRequestConfig): string {
  const { method, url, params, data } = config
  return `${method}-${url}-${JSON.stringify(params)}-${JSON.stringify(data)}`
}

function isSilentRequest (config: AxiosRequestConfig): boolean {
  if ((config as any).skipGlobalLoading) return true
  const url = String(config.url || '')
  return (
    url.includes('/notifications') ||
    url.includes('/workflow-counts') ||
    url.includes('/unread-count') ||
    url.includes('/ping') ||
    url.includes('/health') ||
    url.includes('/metrics') ||
    url.includes('/session') ||
    url.includes('polling=1')
  )
}

class HttpClient {
  private client: AxiosInstance
  private inFlightGetRequests = new Map<string, Promise<ApiResponse<any>>>()

  constructor () {
    this.client = axios.create({
      baseURL: config.api.baseURL,
      timeout: config.api.timeout,
      withCredentials: true,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })

    this.setupInterceptors()
  }

  async get<T = any>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<ApiResponse<T>> {
    // In-flight deduplication: share promise for identical concurrent GET calls
    const key = `${url}:${JSON.stringify(config?.params || {})}`
    if (this.inFlightGetRequests.has(key)) {
      return this.inFlightGetRequests.get(key) as Promise<ApiResponse<T>>
    }

    const promise = (async () => {
      try {
        const response = await this.client.get<ApiResponse<T>>(url, config)
        return response.data
      } finally {
        this.inFlightGetRequests.delete(key)
      }
    })()

    this.inFlightGetRequests.set(key, promise)
    return promise
  }

  async post<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<ApiResponse<T>> {
    const response = await this.client.post<ApiResponse<T>>(url, data, config)
    return response.data
  }

  async put<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<ApiResponse<T>> {
    const response = await this.client.put<ApiResponse<T>>(url, data, config)
    return response.data
  }

  async patch<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig,
  ): Promise<ApiResponse<T>> {
    const response = await this.client.patch<ApiResponse<T>>(url, data, config)
    return response.data
  }

  async delete<T = any>(
    url: string,
    config?: AxiosRequestConfig,
  ): Promise<ApiResponse<T>> {
    const response = await this.client.delete<ApiResponse<T>>(url, config)
    return response.data
  }

  // Upload file with progress
  async upload<T = any>(
    url: string,
    formData: FormData,
    onProgress?: (progress: number) => void,
  ): Promise<ApiResponse<T>> {
    const response = await this.client.post<ApiResponse<T>>(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: progressEvent => {
        if (onProgress && progressEvent.total) {
          const progress = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total,
          )
          onProgress(progress)
        }
      },
    })
    return response.data
  }

  // Download file
  async download (url: string, filename?: string): Promise<void> {
    const response = await this.client.get(url, {
      responseType: 'blob',
    })

    const blob = new Blob([response.data])
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = filename || 'download'
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
  }

  // Get raw axios instance for advanced usage
  getInstance (): AxiosInstance {
    return this.client
  }

  private setupInterceptors (): void {
    // Request interceptor
    this.client.interceptors.request.use(
      config => {
        const loadingConfig = config as LoadingRequestConfig
        const globalLoader = useGlobalLoaderStore()

        const isSilent = isSilentRequest(loadingConfig)
        if (!loadingConfig.skipGlobalLoading && !isSilent) {
          const requestId = generateRequestId(loadingConfig)
          if (!pendingRequests.has(requestId)) {
            pendingRequests.set(requestId, true)
            globalLoader.startLoading()
          }
          loadingConfig.metadata = { requestId }
        }

        // Add access token - lecture directe depuis localStorage (compatible avec auth.ts)
        const token = USE_HTTPONLY_COOKIE_AUTH
          ? null
          : localStorage.getItem('access_token')

        if (!USE_HTTPONLY_COOKIE_AUTH && token && !config.headers.Authorization) {
          config.headers.Authorization = `Bearer ${token}`
        }

        // Add tenant ID header (important for multi-tenancy)
        const tenantId = storage.get<number>(STORAGE_KEYS.TENANT_ID)
        if (tenantId) {
          config.headers['X-Tenant-ID'] = tenantId.toString()
        }

        // Add current selected site context (multi-site access control)
        // Priorité: 1) active_site_id (store global), 2) current_site_id (auth store)
        const activeSiteId
          = localStorage.getItem('active_site_id')
            || localStorage.getItem('current_site_id')
        if (activeSiteId && !config.headers['X-Site-ID']) {
          config.headers['X-Site-ID'] = activeSiteId
        }

        return config
      },
      error => {
        return Promise.reject(error)
      },
    )

    // Response interceptor
    this.client.interceptors.response.use(
      response => {
        const loadingConfig = response.config as LoadingRequestConfig
        const globalLoader = useGlobalLoaderStore()
        const requestId = loadingConfig.metadata?.requestId
        if (requestId && pendingRequests.has(requestId)) {
          pendingRequests.delete(requestId)
          globalLoader.stopLoading()
        }
        return response
      },
      async error => {
        const loadingConfig = error.config as LoadingRequestConfig | undefined
        if (loadingConfig?.metadata?.requestId && pendingRequests.has(loadingConfig.metadata.requestId)) {
          pendingRequests.delete(loadingConfig.metadata.requestId)
          useGlobalLoaderStore().stopLoading()
        }
        const originalRequest = error.config

        // Handle 401 - Token expired, try to refresh
        if (error.response?.status === 401 && !originalRequest._retry) {
          originalRequest._retry = true

          try {
            const refreshToken = storage.get<string>(
              STORAGE_KEYS.REFRESH_TOKEN,
            )

            if (refreshToken) {
              const response = await this.client.post('/auth/refresh-token', {
                refresh_token: refreshToken,
              })

              const accessToken = response?.data?.token
              if (!accessToken) {
                throw new Error('Token de rafraîchissement invalide')
              }
              if (!USE_HTTPONLY_COOKIE_AUTH) {
                storage.set(STORAGE_KEYS.ACCESS_TOKEN, accessToken)
                localStorage.setItem('access_token', accessToken)
              }

              // Retry original request with new token
              if (!USE_HTTPONLY_COOKIE_AUTH) {
                originalRequest.headers.Authorization = `Bearer ${accessToken}`
              }
              return this.client(originalRequest)
            } else {
              this.clearAuth()
              window.location.href = '/auth/login' //  CORRIGÉ: était /login
            }
          } catch (refreshError) {
            // Refresh failed, logout user
            this.clearAuth()
            window.location.href = '/auth/login' //  CORRIGÉ: était /login
            throw refreshError
          }
        }

        throw error
      },
    )
  }

  private clearAuth (): void {
    clearBrowserSessionData()
    storage.remove(STORAGE_KEYS.REFRESH_TOKEN)
    storage.remove(STORAGE_KEYS.TENANT_ID)
  }
}

export const http = new HttpClient()
export default http
