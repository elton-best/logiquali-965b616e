import api from '@/api/client'

export interface Permission {
  name: string
  action: string
  resource?: string
}

export interface PermissionModule {
  module: string
  permissions: Permission[]
}

export interface Role {
  id: number
  name: string
  label?: string
  permissions_count: number
  permissions: string[]
}

export interface UserPermissions {
  user_id: number
  user_type: string
  roles: Array<{ id: number, name: string }>
  direct_permissions: string[]
  role_permissions?: string[]
  effective_permissions?: string[]
  active_scoped_permissions?: string[]
  direct_permissions_count?: number
  role_permissions_count?: number
  effective_permissions_count?: number
  active_scoped_permissions_count?: number
  // Legacy fallback kept for backward compatibility only.
  all_permissions?: string[]
}

const CATALOG_TTL_MS = 60_000
const RETRY_DELAY_MS = 350
const rolesCacheBySite = new Map<string, { data: Role[], expiresAt: number }>()
const rolesRequestsBySite = new Map<string, Promise<Role[]>>()
let permissionsCache: { data: PermissionModule[], expiresAt: number } | null
  = null
let permissionsRequest: Promise<PermissionModule[]> | null = null

async function sleep (ms: number): Promise<void> {
  await new Promise(resolve => setTimeout(resolve, ms))
}

function isTooManyRequests (error: any): boolean {
  return Number(error?.response?.status) === 429
}

/**
 * Service pour gérer les permissions des utilisateurs
 */
const permissionService = {
  /**
   * Obtenir tous les rôles disponibles
   */
  async getAvailableRoles (siteId?: number | null): Promise<Role[]> {
    const cacheKey = String(siteId || 'default')
    const cached = rolesCacheBySite.get(cacheKey)
    if (cached && cached.expiresAt > Date.now()) {
      return cached.data
    }

    const inFlight = rolesRequestsBySite.get(cacheKey)
    if (inFlight) {
      return inFlight
    }

    const request = (async () => {
      try {
        const response = await api.get('/available-roles', {
          params: siteId ? { site_id: siteId } : undefined,
          headers: { 'X-Skip-Error-Toast': 'true' },
        })
        const roles = response.data.roles || []
        rolesCacheBySite.set(cacheKey, {
          data: roles,
          expiresAt: Date.now() + CATALOG_TTL_MS,
        })
        return roles
      } catch (error) {
        if (isTooManyRequests(error)) {
          await sleep(RETRY_DELAY_MS)
          const retryResponse = await api.get('/available-roles', {
            params: siteId ? { site_id: siteId } : undefined,
            headers: { 'X-Skip-Error-Toast': 'true' },
          })
          const roles = retryResponse.data.roles || []
          rolesCacheBySite.set(cacheKey, {
            data: roles,
            expiresAt: Date.now() + CATALOG_TTL_MS,
          })
          return roles
        }

        if (cached?.data?.length) {
          return cached.data
        }

        throw error
      } finally {
        rolesRequestsBySite.delete(cacheKey)
      }
    })()

    rolesRequestsBySite.set(cacheKey, request)
    return request
  },

  /**
   * Obtenir toutes les permissions disponibles
   */
  async getAvailablePermissions (): Promise<PermissionModule[]> {
    if (permissionsCache && permissionsCache.expiresAt > Date.now()) {
      return permissionsCache.data
    }

    if (permissionsRequest) {
      return permissionsRequest
    }

    permissionsRequest = (async () => {
      try {
        const response = await api.get('/available-permissions', {
          headers: { 'X-Skip-Error-Toast': 'true' },
        })
        const permissions = response.data.permissions || []
        permissionsCache = {
          data: permissions,
          expiresAt: Date.now() + CATALOG_TTL_MS,
        }
        return permissions
      } catch (error) {
        if (isTooManyRequests(error)) {
          await sleep(RETRY_DELAY_MS)
          const retryResponse = await api.get('/available-permissions', {
            headers: { 'X-Skip-Error-Toast': 'true' },
          })
          const permissions = retryResponse.data.permissions || []
          permissionsCache = {
            data: permissions,
            expiresAt: Date.now() + CATALOG_TTL_MS,
          }
          return permissions
        }

        if (permissionsCache?.data?.length) {
          return permissionsCache.data
        }

        throw error
      } finally {
        permissionsRequest = null
      }
    })()

    return permissionsRequest
  },

  /**
   * Obtenir les permissions ACTIVES filtrées par subscriptions actives.
   *
   * Retourne uniquement les permissions liées aux modules/sub-modules souscris.
   * À utiliser dans le formulaire de création/édition de collaborateurs.
   *
   * @param siteId - ID du site (optionnel, sinon contexte du site actuel)
   * @returns Liste des permissions actives groupées par module
   */
  async getActivePermissions (siteId?: number): Promise<PermissionModule[]> {
    try {
      const response = await api.get('/available-permissions/active', {
        params: siteId ? { site_id: siteId } : undefined,
        headers: { 'X-Skip-Error-Toast': 'true' },
      })
      const permissions = response.data.permissions || []
      return permissions
    } catch (error) {
      console.error('Error fetching active permissions:', error)

      // Fallback vers getAvailablePermissions si /active n'existe pas
      if ((error as any).response?.status === 404) {
        console.warn(
          'Endpoint /available-permissions/active not available, falling back to /available-permissions',
        )
        return this.getAvailablePermissions()
      }

      throw error
    }
  },

  /**
   * Obtenir les permissions d'un utilisateur
   */
  async getUserPermissions (userId: number): Promise<UserPermissions> {
    const response = await api.get(`/users/${userId}/permissions`)
    return response.data
  },

  /**
   * Assigner un rôle prédéfini à un utilisateur
   */
  async assignRole (userId: number, roleName: string): Promise<any> {
    const response = await api.post(`/users/${userId}/assign-role`, {
      role_name: roleName,
    })
    return response.data
  },

  /**
   * Assigner des permissions personnalisées
   */
  async assignCustomPermissions (
    userId: number,
    permissions: string[],
  ): Promise<any> {
    const response = await api.post(
      `/users/${userId}/assign-custom-permissions`,
      {
        permissions,
      },
    )
    return response.data
  },

  /**
   * Révoquer toutes les permissions d'un utilisateur
   */
  async revokeAllPermissions (userId: number): Promise<any> {
    const response = await api.delete(`/users/${userId}/permissions`)
    return response.data
  },
}

export default permissionService
