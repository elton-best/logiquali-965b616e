/**
 * Composable pour afficher des snackbars/toasts
 * Alias de useToast pour Vuetify
 */

import { useToast as useToastBase } from './useToast'

export function useSnackbar () {
  const toast = useToastBase()

  return {
    showSuccess: (message: string) => toast.success(message),
    showError: (message: string) => toast.error(message),
    showWarning: (message: string) => toast.warning(message),
    showInfo: (message: string) => toast.info(message),
    show: (message: string, type?: 'success' | 'error' | 'warning' | 'info') => {
      switch (type) {
        case 'success': {
          return toast.success(message)
        }
        case 'error': {
          return toast.error(message)
        }
        case 'warning': {
          return toast.warning(message)
        }
        case 'info': {
          return toast.info(message)
        }
        default: {
          return toast.info(message)
        }
      }
    },
  }
}
