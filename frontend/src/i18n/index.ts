/**
 * i18n Configuration
 * Internationalization setup for Vue 3
 */

import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import fr from './locales/fr.json'

export type MessageSchema = typeof fr

const i18n = createI18n<[MessageSchema], 'fr' | 'en'>({
  legacy: false,
  locale: localStorage.getItem('locale') || 'fr',
  fallbackLocale: 'fr',
  messages: {
    fr,
    en,
  },
  globalInjection: true,
})

export default i18n
