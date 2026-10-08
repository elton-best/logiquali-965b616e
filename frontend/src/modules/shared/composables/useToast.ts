import { useToastStore } from '../stores/toastStore'

/**
 * Composable pour afficher des notifications toast
 *
 * @example
 * ```ts
 * import { useToast } from '@/modules/shared/composables/useToast'
 *
 * const toast = useToast()
 *
 * // Success notification
 * toast.success('Opération réussie!')
 *
 * // Error notification
 * toast.error('Une erreur s\'est produite')
 *
 * // Warning notification
 * toast.warning('Attention!', 'Votre session expire bientôt')
 *
 * // Info notification
 * toast.info('Nouvelle mise à jour disponible')
 *
 * // Custom duration (default: 5000ms)
 * toast.success('Sauvegarde réussie', undefined, 3000)
 * ```
 */
export function useToast () {
  const toastStore = useToastStore()
  const defaults = {
    success: 3500,
    info: 4000,
    warning: 5000,
    error: 6000,
  }

  type ToastAction = {
    actionLabel?: string
    action?: () => void
  }

  /**
   * Affiche une notification de succès
   * @param message - Message principal
   * @param title - Titre optionnel
   * @param duration - Durée d'affichage en ms (défaut: 5000)
   */
  function success (message: string, title?: string, duration = defaults.success, options?: ToastAction) {
    return toastStore.add({
      type: 'success',
      message,
      title,
      duration,
      ...options,
    })
  }

  /**
   * Affiche une notification d'erreur
   * @param message - Message principal
   * @param title - Titre optionnel
   * @param duration - Durée d'affichage en ms (défaut: 5000)
   */
  function error (message: string, title?: string, duration = defaults.error, options?: ToastAction) {
    return toastStore.add({
      type: 'error',
      message,
      title,
      duration,
      ...options,
    })
  }

  /**
   * Affiche une notification d'avertissement
   * @param message - Message principal
   * @param title - Titre optionnel
   * @param duration - Durée d'affichage en ms (défaut: 5000)
   */
  function warning (message: string, title?: string, duration = defaults.warning, options?: ToastAction) {
    return toastStore.add({
      type: 'warning',
      message,
      title,
      duration,
      ...options,
    })
  }

  /**
   * Affiche une notification d'information
   * @param message - Message principal
   * @param title - Titre optionnel
   * @param duration - Durée d'affichage en ms (défaut: 5000)
   */
  function info (message: string, title?: string, duration = defaults.info, options?: ToastAction) {
    return toastStore.add({
      type: 'info',
      message,
      title,
      duration,
      ...options,
    })
  }

  /**
   * Supprime une notification spécifique
   * @param id - ID de la notification à supprimer
   */
  function remove (id: string) {
    toastStore.remove(id)
  }

  /**
   * Supprime toutes les notifications
   */
  function clear () {
    toastStore.clear()
  }

  return {
    success,
    error,
    warning,
    info,
    remove,
    clear,
  }
}
