import type { Ref } from 'vue'
import { getCurrentInstance, onUnmounted, ref } from 'vue'
import { useGlobalLoaderStore } from '@/stores/globalLoader'

interface LoadingConfig {
  type?: 'global' | 'local' | 'button' | 'skeleton' | 'progress'
  timeout?: number
  message?: string
  cancellable?: boolean
  context?: string
}

interface UseLoadingReturn {
  loading: Ref<boolean>
  error: Ref<string | null>
  start: (config?: LoadingConfig) => void
  stop: () => void
  retry: () => void
  cancel: () => void
}

const activeLoaders = new Map<string, { timeout?: NodeJS.Timeout, config: LoadingConfig }>()

export function useLoading (initialConfig: LoadingConfig = {}): UseLoadingReturn {
  const globalStore = useGlobalLoaderStore()
  const loading = ref(false)
  const error = ref<string | null>(null)
  const loaderId = ref<string>()

  let timeoutId: NodeJS.Timeout | undefined
  let currentConfig: LoadingConfig = {}

  const start = (config: LoadingConfig = {}) => {
    currentConfig = { ...initialConfig, ...config }
    loaderId.value = `loader_${Date.now()}_${Math.random()}`

    loading.value = true
    error.value = null

    // Global loading si demandé
    if (currentConfig.type === 'global') {
      globalStore.startLoading({
        message: currentConfig.message,
        context: currentConfig.context,
      })
    }

    // Timeout automatique
    if (currentConfig.timeout) {
      timeoutId = setTimeout(() => {
        error.value = 'Timeout: Opération trop longue'
        stop()
      }, currentConfig.timeout)
    }

    // Enregistrer le loader actif
    if (loaderId.value) {
      activeLoaders.set(loaderId.value, {
        timeout: timeoutId,
        config: currentConfig,
      })
    }
  }

  const stop = () => {
    loading.value = false

    if (currentConfig.type === 'global') {
      globalStore.stopLoading()
    }

    if (timeoutId) {
      clearTimeout(timeoutId)
      timeoutId = undefined
    }

    if (loaderId.value) {
      activeLoaders.delete(loaderId.value)
    }
  }

  const retry = () => {
    error.value = null
    start(currentConfig)
  }

  const cancel = () => {
    error.value = 'Opération annulée'
    stop()
  }

  // Cleanup automatique (seulement dans un contexte composant)
  if (getCurrentInstance()) {
    onUnmounted(() => {
      stop()
    })
  }

  return {
    loading,
    error,
    start,
    stop,
    retry,
    cancel,
  }
}

// Composable pour requêtes API avec loading automatique
export function useApiCall<T> (
  apiCall: () => Promise<T>,
  config: LoadingConfig = {},
) {
  const { loading, error, start, stop } = useLoading(config)
  const data = ref<T | null>(null)

  const execute = async () => {
    start()
    try {
      data.value = await apiCall()
      return data.value
    } catch (error_) {
      error.value = error_ instanceof Error ? error_.message : 'Erreur inconnue'
      throw error_
    } finally {
      stop()
    }
  }

  const retryCall = () => {
    return execute()
  }

  return {
    loading,
    error,
    data,
    execute,
    retry: retryCall,
  }
}

// Utilitaires pour gestion globale
export const LoadingUtils = {
  // Arrêter tous les loaders
  stopAll: () => {
    for (const [id, loader] of activeLoaders.entries()) {
      if (loader.timeout) {
        clearTimeout(loader.timeout)
      }
      activeLoaders.delete(id)
    }
    useGlobalLoaderStore().resetLoading()
  },

  // Obtenir les loaders actifs
  getActive: () => {
    return Array.from(activeLoaders.entries()).map(([id, loader]) => ({
      id,
      config: loader.config,
    }))
  },

  // Vérifier si un contexte est en cours de chargement
  isContextLoading: (context: string) => {
    return Array.from(activeLoaders.values()).some(
      loader => loader.config.context === context,
    )
  },

  // Nombre de loaders en attente
  getPendingCount: () => {
    return activeLoaders.size
  },
}
