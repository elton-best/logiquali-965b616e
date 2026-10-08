import type { AxiosError, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import axios from 'axios'
import { useGlobalLoaderStore } from '@/stores/globalLoader'

interface LoadingRequestConfig extends InternalAxiosRequestConfig {
  skipGlobalLoading?: boolean
  loadingContext?: string
}

// Map pour tracker les requêtes en cours
const pendingRequests = new Map<string, boolean>()

// Générer un ID unique pour chaque requête
function generateRequestId (config: LoadingRequestConfig): string {
  const { method, url, params, data } = config
  return `${method}-${url}-${JSON.stringify(params)}-${JSON.stringify(data)}`
}

// Configuration des intercepteurs
export function setupLoadingInterceptors () {
  const globalStore = useGlobalLoaderStore()

  // Intercepteur de requête
  axios.interceptors.request.use(
    (config: LoadingRequestConfig) => {
      // Skip si explicitement demandé
      if (config.skipGlobalLoading) {
        return config
      }

      const requestId = generateRequestId(config)

      // Éviter les doublons
      if (!pendingRequests.has(requestId)) {
        pendingRequests.set(requestId, true)
        globalStore.startLoading()
      }

      // Ajouter l'ID à la config pour le retrouver en réponse
      config.metadata = { requestId }

      return config
    },
    (error: AxiosError) => {
      return Promise.reject(error)
    },
  )

  // Intercepteur de réponse
  axios.interceptors.response.use(
    (response: AxiosResponse) => {
      const requestId = response.config.metadata?.requestId

      if (requestId && pendingRequests.has(requestId)) {
        pendingRequests.delete(requestId)
        globalStore.stopLoading()
      }

      return response
    },
    (error: AxiosError) => {
      const requestId = error.config?.metadata?.requestId

      if (requestId && pendingRequests.has(requestId)) {
        pendingRequests.delete(requestId)
        globalStore.stopLoading()
      }

      return Promise.reject(error)
    },
  )
}

// Utilitaires pour contrôle manuel
export const LoadingInterceptors = {
  // Désactiver temporairement les intercepteurs
  disable: () => {
    axios.defaults.skipGlobalLoading = true
  },

  // Réactiver les intercepteurs
  enable: () => {
    axios.defaults.skipGlobalLoading = false
  },

  // Nettoyer toutes les requêtes en cours
  clearPending: () => {
    pendingRequests.clear()
    useGlobalLoaderStore().resetLoading()
  },

  // Obtenir le nombre de requêtes en cours
  getPendingCount: () => {
    return pendingRequests.size
  },
}

// Types pour TypeScript
declare module 'axios' {
  interface AxiosRequestConfig {
    skipGlobalLoading?: boolean
    loadingContext?: string
    metadata?: {
      requestId: string
    }
  }
}
