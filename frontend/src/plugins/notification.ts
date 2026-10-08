/**
 * Notification Plugin for Vuetify
 * Provides toast notifications with Snackbar
 */

import type { App } from 'vue'

export interface NotificationOptions {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
  position?: 'top' | 'bottom'
}

class NotificationService {
  private app: App | null = null
  private snackbarState = reactive({
    show: false,
    message: '',
    color: 'success',
    timeout: 3000,
    position: 'top',
  })

  install (app: App) {
    this.app = app
    app.config.globalProperties.$notify = this.notify.bind(this)
    app.provide('notification', this)
  }

  success (message: string, duration = 3000) {
    this.notify({ message, type: 'success', duration })
  }

  error (message: string, duration = 5000) {
    this.notify({ message, type: 'error', duration })
  }

  warning (message: string, duration = 4000) {
    this.notify({ message, type: 'warning', duration })
  }

  info (message: string, duration = 3000) {
    this.notify({ message, type: 'info', duration })
  }

  getState () {
    return this.snackbarState
  }

  private notify (options: NotificationOptions) {
    const colorMap = {
      success: 'success',
      error: 'error',
      warning: 'warning',
      info: 'info',
    }

    this.snackbarState.message = options.message
    this.snackbarState.color = colorMap[options.type || 'info']
    this.snackbarState.timeout = options.duration || 3000
    this.snackbarState.position = options.position || 'top'
    this.snackbarState.show = true
  }
}

export const notificationPlugin = new NotificationService()

// Composable for using notifications
export function useNotification () {
  return notificationPlugin
}

// Type augmentation for global properties
declare module 'vue' {
  interface ComponentCustomProperties {
    $notify: (options: NotificationOptions) => void
  }
}
