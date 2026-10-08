/**
 * Generic API Response Types
 */

export interface ApiResponse<T = any> {
  success: boolean
  data: T
  message?: string
  meta?: ApiMeta
}

export interface ApiError {
  success: false
  message: string
  errors?: Record<string, string[]>
  code?: string
  status?: number
}

export interface ApiMeta {
  current_page?: number
  from?: number
  last_page?: number
  per_page?: number
  to?: number
  total?: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: ApiMeta
  links?: {
    first?: string
    last?: string
    prev?: string | null
    next?: string | null
  }
}

export interface ValidationError {
  field: string
  message: string
}

export type ApiMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

export interface ApiRequestConfig {
  params?: Record<string, any>
  headers?: Record<string, string>
  skipAuth?: boolean
  skipTenant?: boolean
  timeout?: number
}
