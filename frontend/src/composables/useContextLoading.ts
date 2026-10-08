import { computed, ref } from 'vue'
import { useLoading } from './useLoading'

type LoadingContext
  = | 'dashboard'
    | 'form'
    | 'table'
    | 'export'
    | 'import'
    | 'auth'
    | 'navigation'
    | 'search'
    | 'upload'

interface ContextConfig {
  type: 'global' | 'local' | 'skeleton' | 'progress'
  timeout: number
  message: string
  showProgress?: boolean
  cancellable?: boolean
}

// Configuration par contexte
const contextConfigs: Record<LoadingContext, ContextConfig> = {
  dashboard: {
    type: 'skeleton',
    timeout: 10_000,
    message: 'Chargement du tableau de bord...',
    showProgress: false,
    cancellable: false,
  },
  form: {
    type: 'local',
    timeout: 15_000,
    message: 'Enregistrement en cours...',
    showProgress: false,
    cancellable: false,
  },
  table: {
    type: 'skeleton',
    timeout: 8000,
    message: 'Chargement des données...',
    showProgress: false,
    cancellable: true,
  },
  export: {
    type: 'progress',
    timeout: 60_000,
    message: 'Export en cours...',
    showProgress: true,
    cancellable: true,
  },
  import: {
    type: 'progress',
    timeout: 120_000,
    message: 'Import en cours...',
    showProgress: true,
    cancellable: true,
  },
  auth: {
    type: 'global',
    timeout: 10_000,
    message: 'Connexion en cours...',
    showProgress: false,
    cancellable: false,
  },
  navigation: {
    type: 'global',
    timeout: 5000,
    message: 'Chargement de la page...',
    showProgress: false,
    cancellable: false,
  },
  search: {
    type: 'local',
    timeout: 5000,
    message: 'Recherche en cours...',
    showProgress: false,
    cancellable: true,
  },
  upload: {
    type: 'progress',
    timeout: 300_000,
    message: 'Upload en cours...',
    showProgress: true,
    cancellable: true,
  },
}

export function useContextLoading (context: LoadingContext) {
  const config = contextConfigs[context]
  const { loading, error, start, stop, retry, cancel } = useLoading({
    type: config.type,
    timeout: config.timeout,
    message: config.message,
    cancellable: config.cancellable,
    context,
  })

  const progress = ref(0)
  const stage = ref<'initial' | 'loading' | 'processing' | 'finalizing'>('initial')

  // Méthodes spécialisées par contexte
  const startWithStage = (initialStage: typeof stage.value = 'loading') => {
    stage.value = initialStage
    progress.value = 0
    start(config)
  }

  const updateProgress = (value: number, newStage?: typeof stage.value) => {
    progress.value = Math.min(Math.max(value, 0), 100)
    if (newStage) {
      stage.value = newStage
    }
  }

  const complete = () => {
    progress.value = 100
    stage.value = 'initial'
    stop()
  }

  // Messages contextuels
  const contextMessage = computed(() => {
    if (error.value) {
      return error.value
    }

    switch (stage.value) {
      case 'loading': {
        return config.message
      }
      case 'processing': {
        return context === 'export'
          ? 'Génération du fichier...'
          : (context === 'import'
              ? 'Traitement des données...'
              : 'Traitement en cours...')
      }
      case 'finalizing': {
        return 'Finalisation...'
      }
      default: {
        return config.message
      }
    }
  })

  // Composant de loading approprié
  const LoadingComponent = computed(() => {
    switch (config.type) {
      case 'skeleton': {
        return 'UniversalSkeleton'
      }
      case 'progress': {
        return 'LoadingStates'
      }
      case 'global': {
        return null
      } // Géré par AppGlobalLoader
      default: {
        return 'LoadingStates'
      }
    }
  })

  return {
    loading,
    error,
    progress,
    stage,
    contextMessage,
    LoadingComponent,
    start: startWithStage,
    stop,
    retry,
    cancel,
    updateProgress,
    complete,
    config,
  }
}

// Hook pour loading de données avec retry automatique
export function useDataLoading<T> (
  context: LoadingContext,
  fetcher: () => Promise<T>,
  options: {
    retries?: number
    retryDelay?: number
    onError?: (error: Error) => void
  } = {},
) {
  const { retries = 3, retryDelay = 1000, onError } = options
  const contextLoading = useContextLoading(context)
  const data = ref<T | null>(null)
  const retryCount = ref(0)

  const execute = async (): Promise<T | null> => {
    contextLoading.start()

    try {
      const result = await fetcher()
      data.value = result
      contextLoading.complete()
      retryCount.value = 0
      return result
    } catch (error_) {
      const error = error_ instanceof Error ? error_ : new Error('Erreur inconnue')

      if (retryCount.value < retries) {
        retryCount.value++
        contextLoading.updateProgress(0, 'processing')

        // Délai avant retry
        await new Promise(resolve => setTimeout(resolve, retryDelay))
        return execute()
      } else {
        contextLoading.error.value = error.message
        contextLoading.stop()
        onError?.(error)
        throw error
      }
    }
  }

  const refresh = () => {
    retryCount.value = 0
    return execute()
  }

  return {
    ...contextLoading,
    data,
    retryCount,
    execute,
    refresh,
  }
}
