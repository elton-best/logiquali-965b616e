/**
 * main.ts
 *
 * Bootstraps Vuetify and other plugins then mounts the App`
 */

// Composables
import { createApp } from 'vue'

import AppDatePickerField from '@/components/common/AppDatePickerField.vue'

import { useToastStore } from '@/modules/shared/stores/toastStore'
// Plugins
import { registerPlugins } from '@/plugins'
// Stores
import { useAuthStore } from '@/stores/auth'

import { useLockScreenStore } from '@/stores/lockScreen'

// Components
import App from './App.vue'
// Styles - BestQHSE 2.0 Design System Premium
import 'unfonts.css'
import './styles/design-tokens.css'
import './styles/global.css'
import './styles/auth-premium.css'
import './assets/main.css'

import '@vuepic/vue-datepicker/dist/main.css'

const app = createApp(App)

registerPlugins(app)
app.component('AppDatePickerField', AppDatePickerField)

// Replace native alerts with toasts (global)
const toastStore = useToastStore()
const defaultDurations = {
  success: 3500,
  info: 4000,
  warning: 5000,
  error: 6000,
}

function inferToastType (message: string): 'success' | 'error' | 'warning' | 'info' {
  const normalized = message.toLowerCase()
  if (normalized.includes('❌') || normalized.includes('erreur') || normalized.includes('échec')) {
    return 'error'
  }
  if (normalized.includes('⚠️') || normalized.includes('attention') || normalized.includes('avert')) {
    return 'warning'
  }
  if (normalized.includes('✅') || normalized.includes('succès') || normalized.includes('réussi')) {
    return 'success'
  }
  return 'info'
}

window.alert = (message?: any) => {
  const text = String(message ?? '')
  const type = inferToastType(text)
  toastStore.add({
    type,
    message: text,
    duration: defaultDurations[type],
  })
}

// Initialize authentication BEFORE mounting the app
// This ensures the router guards have access to the auth state
const authStore = useAuthStore()
authStore.initAuth()

// Check lock screen status
const lockScreenStore = useLockScreenStore()
lockScreenStore.checkLockStatus()

app.mount('#app')
