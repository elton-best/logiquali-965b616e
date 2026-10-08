import type { PaginatedResponse, QueryParams } from '@/types/api'
import api from '@/api/client'

export interface Site {
  id: number
  enterprise_id: number
  name: string
  ref?: string
  location: string
  city?: string
  phone?: string
  email?: string
  is_headquarter: boolean
  is_active: boolean
  manager_id?: number
  manager_name?: string
  active_norms?: string[]
  enterprise?: {
    id: number
    name: string
  }
  manager?: {
    id: number
    name: string
    email: string
    role?: string
    position?: string
    job_title?: string
  }
  subscription?: {
    id: number
    ref?: string
    start_date: string
    expiration_date: string
    is_active: boolean
    offer?: {
      id: number
      name: string
      price: number
      description?: string
      norms?: Array<{
        id: number
        code?: string
        name?: string
      }>
    }
  }
  subscriptions?: Array<{
    id: number
    ref?: string
    start_date: string
    expiration_date: string
    is_active: boolean
    offer?: {
      id: number
      name: string
      price: number
      description?: string
      norms?: Array<{
        id: number
        code?: string
        name?: string
      }>
    }
  }>
  processes_count?: number
  users_count?: number
  created_at: string
  updated_at: string
}

export interface CreateSiteRequest {
  name: string
  location: string
  city: string
  phone?: string
  email?: string
  is_headquarter?: boolean
  is_active?: boolean
  manager_id?: number
  role?: string
  new_manager?: {
    first_name: string
    last_name: string
    email: string
    phone?: string
    position: string
  }
  permissions?: string[]
}

export interface UpdateSiteRequest {
  name?: string
  location?: string
  city?: string
  phone?: string
  email?: string
  is_headquarter?: boolean
  is_active?: boolean
  manager_id?: number
  role?: string
  permissions?: string[]
}

function normalizeOfferNorms (offerData: any) {
  const norms = offerData?.relationships?.norms
  if (!Array.isArray(norms)) {
    return []
  }

  return norms.map((norm: any) => ({
    id: norm.id,
    code: norm.attributes?.code,
    name: norm.attributes?.name,
  }))
}

function parseOfferRelation (offerData: any) {
  if (!offerData || typeof offerData !== 'object' || !offerData.id) {
    return undefined
  }

  return {
    id: offerData.id,
    name: offerData.attributes?.name || '',
    price: offerData.attributes?.price || 0,
    description: offerData.attributes?.description,
    norms: normalizeOfferNorms(offerData),
  }
}

function parseSubscriptionData (subData: any) {
  return {
    id: subData.id,
    ref: subData.attributes?.ref,
    start_date: subData.attributes?.start_date || '',
    expiration_date: subData.attributes?.expiration_date || '',
    is_active: subData.attributes?.is_active || false,
    offer: parseOfferRelation(subData?.relationships?.offer),
  }
}

function normalizeSubscriptionsRelation (rawSubscriptions: any): any[] {
  if (Array.isArray(rawSubscriptions)) {
    return rawSubscriptions
  }
  if (Array.isArray(rawSubscriptions?.data)) {
    return rawSubscriptions.data
  }
  if (rawSubscriptions && typeof rawSubscriptions === 'object' && rawSubscriptions.id) {
    return [rawSubscriptions]
  }
  return []
}

function parseManagerRelation (managerData: any) {
  if (typeof managerData !== 'object' || !managerData?.id) {
    return undefined
  }

  return {
    id: managerData.id,
    name: managerData.attributes?.name || '',
    email: managerData.attributes?.email || '',
    role: managerData.attributes?.role,
    position: managerData.attributes?.position || managerData.attributes?.job_title,
    job_title: managerData.attributes?.job_title,
  }
}

function parseEnterpriseRelation (enterpriseData: any) {
  if (typeof enterpriseData !== 'object' || !enterpriseData?.id) {
    return undefined
  }

  return {
    id: enterpriseData.id,
    name: enterpriseData.attributes?.name || '',
  }
}

function parseLegacySubscriptionRelation (subData: any) {
  if (typeof subData !== 'object' || !subData?.id) {
    return undefined
  }

  return {
    id: subData.id,
    ref: subData.attributes?.ref,
    start_date: subData.attributes?.start_date || '',
    expiration_date: subData.attributes?.expiration_date || '',
    is_active: subData.attributes?.is_active || false,
    offer: parseOfferRelation(subData?.relationships?.offer),
  }
}

function applySiteRelationships (site: Site, relationships: any) {
  site.manager = parseManagerRelation(relationships?.manager)
  site.enterprise = parseEnterpriseRelation(relationships?.enterprise)
  site.subscription = parseLegacySubscriptionRelation(relationships?.subscription)

  const normalizedSubscriptions = normalizeSubscriptionsRelation(relationships?.subscriptions)
  if (normalizedSubscriptions.length > 0) {
    site.subscriptions = normalizedSubscriptions
      .filter((subData: any) => typeof subData === 'object' && subData?.id)
      .map((subData: any) => parseSubscriptionData(subData))
  }
}

// Transform JSON:API format to simple format
function transformJsonApiSite (jsonApiData: any): Site {
  const site: Site = {
    id: jsonApiData.id,
    enterprise_id: jsonApiData.attributes.enterprise_id || 0,
    name: jsonApiData.attributes.name,
    ref: jsonApiData.attributes.ref,
    location: jsonApiData.attributes.location,
    city: jsonApiData.attributes.city,
    phone: jsonApiData.attributes.phone,
    email: jsonApiData.attributes.email,
    is_headquarter: jsonApiData.attributes.is_headquarter,
    is_active: jsonApiData.attributes.is_active,
    manager_id: jsonApiData.attributes.manager_id,
    manager_name: jsonApiData.attributes.manager_name,
    active_norms: Array.isArray(jsonApiData.attributes.active_norms)
      ? jsonApiData.attributes.active_norms
      : [],
    processes_count: jsonApiData.attributes.processes_count,
    users_count: jsonApiData.attributes.users_count,
    created_at: jsonApiData.attributes.created_at,
    updated_at: jsonApiData.attributes.updated_at,
  }

  if (jsonApiData.relationships) {
    applySiteRelationships(site, jsonApiData.relationships)
  }

  return site
}

export const siteService = {
  /**
   * Get all sites with pagination
   */
  async getAll (params?: QueryParams): Promise<PaginatedResponse<Site>> {
    const { data: response } = await api.get('/sites', { params })

    // Transform JSON:API format
    const sites = response.data?.map((item: any) => transformJsonApiSite(item)) || []

    return {
      data: sites,
      links: response.links || {
        first: null,
        last: null,
        prev: null,
        next: null,
      },
      meta: response.meta || {
        current_page: 1,
        last_page: 1,
        per_page: sites.length,
        total: sites.length,
      },
    }
  },

  /**
   * Get single site by ID
   */
  async getById (id: number): Promise<Site> {
    const { data: response } = await api.get(`/sites/${id}`)
    return transformJsonApiSite(response.data)
  },

  /**
   * Create new site
   */
  async create (siteData: CreateSiteRequest): Promise<Site> {
    const { data: response } = await api.post('/sites', siteData)
    return transformJsonApiSite(response.data)
  },

  /**
   * Update existing site
   */
  async update (id: number, siteData: UpdateSiteRequest): Promise<Site> {
    const { data: response } = await api.put(`/sites/${id}`, siteData)
    return transformJsonApiSite(response.data)
  },

  /**
   * Delete site
   */
  async delete (id: number): Promise<void> {
    await api.delete(`/sites/${id}`)
  },

  /**
   * Toggle site active status
   */
  async toggleActive (id: number): Promise<Site> {
    const { data: response } = await api.post(`/sites/${id}/toggle-active`)
    return transformJsonApiSite(response.data)
  },
}

export default siteService
