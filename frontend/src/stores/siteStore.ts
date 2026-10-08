/**
 * Site Store (Pinia)
 * Gestion des sites/établissements
 */

import type { PaginatedResponse } from '@/types/api'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import siteService, { type Site } from '@/services/siteService'

export const useSiteStore = defineStore('site', () => {
  // State
  const sites = ref<Site[]>([])
  const currentSite = ref<Site | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const pagination = ref<PaginatedResponse<Site>['meta'] | null>(null)

  // Getters
  const activeSites = computed(() => sites.value.filter(s => s.is_active))
  const headquarter = computed(() => sites.value.find(s => s.is_headquarter))
  const sitesCount = computed(() => sites.value.length)

  // Actions
  async function fetchAll (params?: any) {
    loading.value = true
    error.value = null
    try {
      const response = await siteService.getAll(params)
      sites.value = response.data
      pagination.value = response.meta
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement des sites'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchById (id: number) {
    loading.value = true
    error.value = null
    try {
      const site = await siteService.getById(id)
      currentSite.value = site
      return site
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement du site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function create (siteData: any) {
    loading.value = true
    error.value = null
    try {
      const newSite = await siteService.create(siteData)
      sites.value.unshift(newSite)
      return newSite
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la création du site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function update (id: number, siteData: any) {
    loading.value = true
    error.value = null
    try {
      const updatedSite = await siteService.update(id, siteData)
      const index = sites.value.findIndex(s => s.id === id)
      if (index !== -1) {
        sites.value[index] = updatedSite
      }
      if (currentSite.value?.id === id) {
        currentSite.value = updatedSite
      }
      return updatedSite
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la modification du site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteSite (id: number) {
    loading.value = true
    error.value = null
    try {
      await siteService.delete(id)
      sites.value = sites.value.filter(s => s.id !== id)
      if (currentSite.value?.id === id) {
        currentSite.value = null
      }
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors de la suppression du site'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function toggleActive (id: number) {
    loading.value = true
    error.value = null
    try {
      const updatedSite = await siteService.toggleActive(id)
      const index = sites.value.findIndex(s => s.id === id)
      if (index !== -1) {
        sites.value[index] = updatedSite
      }
      return updatedSite
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du changement de statut'
      throw error_
    } finally {
      loading.value = false
    }
  }

  function setCurrentSite (site: Site | null) {
    currentSite.value = site
  }

  function clearError () {
    error.value = null
  }

  return {
    // State
    sites,
    currentSite,
    loading,
    error,
    pagination,
    // Getters
    activeSites,
    headquarter,
    sitesCount,
    // Actions
    fetchAll,
    fetchById,
    create,
    update,
    deleteSite,
    toggleActive,
    setCurrentSite,
    clearError,
  }
})
