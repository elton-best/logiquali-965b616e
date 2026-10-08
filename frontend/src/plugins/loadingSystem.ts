import type { App } from 'vue'
// Composants globaux
import LoadingStates from '@/components/ui/LoadingStates.vue'

import ProgressiveLoader from '@/components/ui/ProgressiveLoader.vue'
import UniversalSkeleton from '@/components/ui/UniversalSkeleton.vue'
import { useGlobalLoaderStore } from '@/stores/globalLoader'
import { setupLoadingInterceptors } from '@/utils/loadingInterceptors'

// Types globaux
declare module '@vue/runtime-core' {
  interface ComponentCustomProperties {
    $loading: {
      start: (config?: any) => void
      stop: () => void
      isLoading: boolean
    }
  }
}

export default {
  install (app: App) {
    // Enregistrer les composants globalement
    app.component('LoadingStates', LoadingStates)
    app.component('UniversalSkeleton', UniversalSkeleton)
    app.component('ProgressiveLoader', ProgressiveLoader)

    // Initialiser les intercepteurs HTTP
    setupLoadingInterceptors()

    // Propriété globale pour accès facile au loading
    app.config.globalProperties.$loading = {
      start: (_config = {}) => {
        useGlobalLoaderStore().startLoading()
      },
      stop: () => {
        useGlobalLoaderStore().stopLoading()
      },
      get isLoading () {
        return useGlobalLoaderStore().isLoading
      },
    }

    // Directives personnalisées pour loading
    app.directive('loading', {
      mounted (el, binding) {
        if (binding.value) {
          el.style.position = 'relative'
          el.style.pointerEvents = 'none'
          el.style.opacity = '0.6'

          // Ajouter spinner
          const spinner = document.createElement('div')
          spinner.className = 'loading-overlay'
          spinner.innerHTML = `
            <div class="loading-spinner">
              <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          `
          el.append(spinner)
        }
      },
      updated (el, binding) {
        const overlay = el.querySelector('.loading-overlay')
        if (binding.value && !overlay) {
          // Ajouter loading
          el.style.position = 'relative'
          el.style.pointerEvents = 'none'
          el.style.opacity = '0.6'

          const spinner = document.createElement('div')
          spinner.className = 'loading-overlay'
          spinner.innerHTML = `
            <div class="loading-spinner">
              <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          `
          el.append(spinner)
        } else if (!binding.value && overlay) {
          // Retirer loading
          el.style.pointerEvents = ''
          el.style.opacity = ''
          overlay.remove()
        }
      },
    })

    console.log('🔄 Loading System initialized')
  },
}

// Styles CSS pour la directive
const styles = `
.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.8);
  z-index: 10;
}

.loading-spinner {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
`

// Injecter les styles
if (typeof document !== 'undefined') {
  const styleSheet = document.createElement('style')
  styleSheet.textContent = styles
  document.head.append(styleSheet)
}
