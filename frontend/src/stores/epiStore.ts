import { defineStore } from 'pinia'
import { ref } from 'vue'
import httpClient from '@/api/http-client'

export interface EpiCatalogue {
  id: number
  categorie: string
  designation: string
  reference_fabricant?: string
  norme_ce?: string
  description?: string
}

export interface EpiStock {
  id: number
  enterprise_id: number
  site_id: number
  epi_catalogue_id: number
  taille?: string
  quantite_stock: number
  seuil_alerte: number
  prix_unitaire?: number
  emplacement_stockage?: string
  epiCatalogue?: EpiCatalogue
  site?: any
}

export interface EpiMouvement {
  id: number
  epi_stock_id: number
  type: 'entree' | 'sortie' | 'inventaire' | 'rebut'
  quantite: number
  user_id?: number
  motif?: string
  observations?: string
  date_mouvement: string
  user?: any
}

export interface EpiAttribution {
  id: number
  epi_stock_id: number
  user_id: number
  quantite_attribuee: number
  date_attribution: string
  date_renouvellement_prevue?: string
  statut: 'en_cours' | 'restitue' | 'perdu' | 'detruit'
  observations?: string
  epiStock?: EpiStock
  user?: any
}

export const useEpiStore = defineStore('epi', () => {
  const catalogue = ref<EpiCatalogue[]>([])
  const stocks = ref<EpiStock[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchCatalogue () {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/epi/catalogue')
      catalogue.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement catalogue'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createCatalogueItem (item: Partial<EpiCatalogue>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/epi/catalogue', item)
      catalogue.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création EPI'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStocks (filters?: any) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.get('/epi/stocks', { params: filters })
      stocks.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement stocks'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function createStock (stock: Partial<EpiStock>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/epi/stocks', stock)
      stocks.value.push(data)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création stock'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function updateStock (id: number, stock: Partial<EpiStock>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.put(`/epi/stocks/${id}`, stock)
      const index = stocks.value.findIndex(s => s.id === id)
      if (index !== -1) {
        stocks.value[index] = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur modification stock'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchMouvements (stockId: number) {
    try {
      const { data } = await httpClient.get(`/epi/stocks/${stockId}/mouvements`)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement mouvements'
      throw error_
    }
  }

  async function createMouvement (mouvement: Partial<EpiMouvement>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/epi/mouvements', mouvement)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création mouvement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchAttributions (filters?: any) {
    try {
      const { data } = await httpClient.get('/epi/attributions', { params: filters })
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement attributions'
      throw error_
    }
  }

  async function createAttribution (attribution: Partial<EpiAttribution>) {
    loading.value = true
    error.value = null
    try {
      const { data } = await httpClient.post('/epi/attributions', attribution)
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création attribution'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStats () {
    try {
      const { data } = await httpClient.get('/epi/stats')
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement stats'
      throw error_
    }
  }

  return {
    catalogue,
    stocks,
    loading,
    error,
    fetchCatalogue,
    createCatalogueItem,
    fetchStocks,
    createStock,
    updateStock,
    fetchMouvements,
    createMouvement,
    fetchAttributions,
    createAttribution,
    fetchStats,
  }
})
