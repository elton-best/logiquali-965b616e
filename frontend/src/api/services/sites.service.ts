/**
 * Sites Service
 * API calls for managing sites/locations
 */

import api from '@/api/client'
import { parseJsonApiCollection, parseJsonApiResource } from '@/utils/json-api-parser'

export interface Site {
  id: number
  name: string
  address: string
  city: string
  country: string
  postal_code?: string
  phone?: string
  manager_id?: number
  manager?: {
    id: number
    name: string
    email: string
  }
  users_count?: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface CreateSiteDTO {
  name: string
  address: string
  city: string
  country: string
  postal_code?: string
  phone?: string
  manager_id?: number
}

export interface UpdateSiteDTO {
  name?: string
  address?: string
  city?: string
  country?: string
  postal_code?: string
  phone?: string
  manager_id?: number
  is_active?: boolean
}

export interface SitesListParams {
  page?: number
  per_page?: number
  search?: string
  is_active?: boolean
}

export interface SitesListResponse {
  data: Site[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

class SitesService {
  private readonly basePath = '/sites'

  private normalizeSitesListResponse (payload: any): SitesListResponse {
    const rows = Array.isArray(payload?.data)
      ? parseJsonApiCollection(payload)
      : []

    return {
      data: rows as Site[],
      meta: payload?.meta || {
        current_page: 1,
        last_page: 1,
        per_page: rows.length,
        total: rows.length,
      },
    }
  }

  /**
   * Get list of sites
   */
  async getSites (params: SitesListParams = {}): Promise<SitesListResponse> {
    const response = await api.get<SitesListResponse>(this.basePath, { params })
    return this.normalizeSitesListResponse(response.data)
  }

  /**
   * Get single site by ID
   */
  async getSite (id: number): Promise<Site> {
    const response = await api.get<{ data: Site }>(`${this.basePath}/${id}`)
    return parseJsonApiResource(response.data?.data) as Site
  }

  /**
   * Create new site
   */
  async createSite (data: CreateSiteDTO): Promise<Site> {
    const response = await api.post<{ data: Site }>(this.basePath, data)
    return parseJsonApiResource(response.data?.data) as Site
  }

  /**
   * Update site
   */
  async updateSite (id: number, data: UpdateSiteDTO): Promise<Site> {
    const response = await api.put<{ data: Site }>(`${this.basePath}/${id}`, data)
    return parseJsonApiResource(response.data?.data) as Site
  }

  /**
   * Delete site
   */
  async deleteSite (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Toggle site active status
   */
  async toggleSiteStatus (id: number): Promise<Site> {
    const response = await api.post<{ data: Site }>(`${this.basePath}/${id}/toggle-status`)
    return parseJsonApiResource(response.data?.data) as Site
  }

  /**
   * Assign manager to site
   */
  async assignManager (id: number, manager_id: number | null): Promise<Site> {
    const response = await api.put<{ data: Site }>(`${this.basePath}/${id}/manager`, {
      manager_id,
    })
    return parseJsonApiResource(response.data?.data) as Site
  }
}

export const sitesService = new SitesService()
