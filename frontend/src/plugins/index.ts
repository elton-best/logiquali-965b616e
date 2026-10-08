/**
 * plugins/index.ts
 *
 * Automatically included in `./src/main.ts`
 */

// Types
import type { App } from 'vue'
import Toast from 'vue-toastification'
import i18n from '../i18n'
import router from '../router'
import pinia from '../stores'
import { notificationPlugin } from './notification'
// Plugins
import vuetify from './vuetify'

import 'vue-toastification/dist/index.css'

export function registerPlugins (app: App) {
  app
    .use(vuetify)
    .use(router)
    .use(pinia)
    .use(i18n)
    .use(notificationPlugin)
    .use(Toast, {
      position: 'top-right',
      timeout: 3000,
      closeOnClick: true,
      pauseOnFocusLoss: true,
      pauseOnHover: true,
      draggable: true,
      draggablePercent: 0.6,
      showCloseButtonOnHover: false,
      hideProgressBar: false,
      closeButton: 'button',
      icon: true,
      rtl: false,
    })
}
