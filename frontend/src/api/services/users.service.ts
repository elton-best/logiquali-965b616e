/**
 * Users Service
 * API calls for managing collaborateurs (users within a tenant)
 */

import type { User } from '@/types/models/users'
import api from '@/api/client'
import { parseJsonApiCollection, parseJsonApiResource } from '@/utils/json-api-parser'

export interface CreateUserDTO {
  name: string
  email: string
  password: string
  password_confirmation: string
  position: string
  site_id?: number
  permissions?: string[]
}

export interface UpdateUserDTO {
  name?: string
  email?: string
  position?: string
  site_id?: number
  permissions?: string[]
  is_active?: boolean
}

export interface UsersListParams {
  page?: number
  per_page?: number
  search?: string
  site_id?: number
  is_active?: boolean
}

export interface UsersListResponse {
  data: User[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

class UsersService {
  private readonly basePath = '/users'

  private normalizeUsersListResponse (payload: any): UsersListResponse {
    const rows = Array.isArray(payload?.data)
      ? parseJsonApiCollection(payload)
      : []

    return {
      data: rows as User[],
      meta: payload?.meta || {
        current_page: 1,
        last_page: 1,
        per_page: rows.length,
        total: rows.length,
      },
    }
  }

  /**
   * Get list of users (collaborateurs)
   */
  async getUsers (params: UsersListParams = {}): Promise<UsersListResponse> {
    const response = await api.get<UsersListResponse>(this.basePath, { params })
    return this.normalizeUsersListResponse(response.data)
  }

  /**
   * Export users list as DOCX
   */
  async exportUsersDocx (params: UsersListParams = {}): Promise<Blob> {
    const response = await api.get(`${this.basePath}/export-docx`, {
      params,
      responseType: 'blob',
    })
    return response.data
  }

  /**
   * Get single user by ID
   */
  async getUser (id: number): Promise<User> {
    const response = await api.get<{ data: User }>(`${this.basePath}/${id}`)
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Create new user (collaborateur)
   */
  async createUser (data: CreateUserDTO): Promise<User> {
    const response = await api.post<{ data: User }>(this.basePath, data)
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Update user
   */
  async updateUser (id: number, data: UpdateUserDTO): Promise<User> {
    const response = await api.put<{ data: User }>(`${this.basePath}/${id}`, data)
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Delete user
   */
  async deleteUser (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Toggle user active status
   */
  async toggleUserStatus (id: number): Promise<User> {
    const response = await api.post<{ data: User }>(`${this.basePath}/${id}/toggle-status`)
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Update user permissions
   */
  async updatePermissions (id: number, permissions: string[]): Promise<User> {
    const response = await api.put<{ data: User }>(`${this.basePath}/${id}/permissions`, {
      permissions,
    })
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Assign user to site
   */
  async assignToSite (id: number, site_id: number | null): Promise<User> {
    const response = await api.put<{ data: User }>(`${this.basePath}/${id}/site`, {
      site_id,
    })
    return parseJsonApiResource(response.data?.data) as User
  }

  /**
   * Import users from Excel file
   */
  async importUsers (file: File, options: {
    permissions?: string[]
    default_role?: string
    send_welcome_email?: boolean
    site_id?: number
  } = {}): Promise<{
    success: boolean
    message: string
    job_reference: string
    notifications?: {
      approval_required?: boolean
      activation_email_sent?: boolean
    }
  }> {
    const formData = new FormData()
    formData.append('file', file)

    if (options.permissions?.length) {
      for (const [index, perm] of options.permissions.entries()) {
        formData.append(`permissions[${index}]`, perm)
      }
    }

    if (options.default_role) {
      formData.append('default_role', options.default_role)
    }

    if (options.site_id) {
      formData.append('site_id', String(options.site_id))
    }

    if (options.send_welcome_email !== undefined) {
      formData.append('send_welcome_email', options.send_welcome_email ? '1' : '0')
    }

    const response = await api.post<{
      success: boolean
      message: string
      job_reference: string
      notifications?: {
        approval_required?: boolean
        activation_email_sent?: boolean
      }
    }>(
      `${this.basePath}/import`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      },
    )
    return response.data
  }
}

export const usersService = new UsersService()
