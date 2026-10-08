/**
 * useSites Composable
 * Manages sites state and operations
 */

import { computed, ref } from 'vue'
import { type CreateSiteDTO, type Site, type SitesListParams, sitesService, type UpdateSiteDTO } from '@/api/services/sites.service'

export function useSites () {
  const sites = ref<Site[]>([])
  const currentSite = ref<Site | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  })

  const activeSites = computed(() => sites.value.filter(s => s.is_active))
  const inactiveSites = computed(() => sites.value.filter(s => !s.is_active))

  /**
   * Fetch sites list
   */
  async function fetchSites (params: SitesListParams = {}) {
    loading.value = true
    error.value = null
    try {
      const response = await sitesService.getSites(params)
      sites.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch sites'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch single site
   */
  async function fetchSite (id: number) {
    loading.value = true
    error.value = null
    try {
      currentSite.value = await sitesService.getSite(id)
      return currentSite.value
    } catch (error_: any) {
      error.value = error_.message || 'Failed to fetch site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Create new site
   */
  async function createSite (data: CreateSiteDTO) {
    loading.value = true
    error.value = null
    try {
      const newSite = await sitesService.createSite(data)
      sites.value.unshift(newSite)
      return newSite
    } catch (error_: any) {
      error.value = error_.message || 'Failed to create site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Update site
   */
  async function updateSite (id: number, data: UpdateSiteDTO) {
    loading.value = true
    error.value = null
    try {
      const updatedSite = await sitesService.updateSite(id, data)
      const index = sites.value.findIndex(s => s.id === id)
      if (index !== -1) {
        sites.value[index] = updatedSite
      }
      if (currentSite.value?.id === id) {
        currentSite.value = updatedSite
      }
      return updatedSite
    } catch (error_: any) {
      error.value = error_.message || 'Failed to update site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete site
   */
  async function deleteSite (id: number) {
    loading.value = true
    error.value = null
    try {
      await sitesService.deleteSite(id)
      sites.value = sites.value.filter(s => s.id !== id)
    } catch (error_: any) {
      error.value = error_.message || 'Failed to delete site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Toggle site status
   */
  async function toggleSiteStatus (id: number) {
    loading.value = true
    error.value = null
    try {
      const updatedSite = await sitesService.toggleSiteStatus(id)
      const index = sites.value.findIndex(s => s.id === id)
      if (index !== -1) {
        sites.value[index] = updatedSite
      }
      return updatedSite
    } catch (error_: any) {
      error.value = error_.message || 'Failed to toggle site status'
      throw error_
    } finally {
      loading.value = false
    }
  }

  /**
   * Assign manager to site
   */
  async function assignManager (id: number, manager_id: number | null) {
    loading.value = true
    error.value = null
    try {
      const updatedSite = await sitesService.assignManager(id, manager_id)
      const index = sites.value.findIndex(s => s.id === id)
      if (index !== -1) {
        sites.value[index] = updatedSite
      }
      return updatedSite
    } catch (error_: any) {
      error.value = error_.message || 'Failed to assign manager'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    sites,
    currentSite,
    loading,
    error,
    pagination,
    activeSites,
    inactiveSites,
    fetchSites,
    fetchSite,
    createSite,
    updateSite,
    deleteSite,
    toggleSiteStatus,
    assignManager,
  }
}
