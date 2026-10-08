/**
 * plugins/vuetify.ts
 * BestQHSE 2.0 Design System - "Intelligent Clarity"
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Composables
import { createVuetify } from 'vuetify'
// Styles
import '@mdi/font/css/materialdesignicons.css'

import 'vuetify/styles'

// BestQHSE 2.0 - Trust Blue Palette
export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          // 🔵 Primary - Trust Blue (#4471C4)
          'primary': '#4471C4',
          'primary-darken-1': '#3559A0',
          'primary-darken-2': '#2B3F6C',
          'primary-lighten-1': '#5C8AD6',
          'primary-lighten-2': '#75A3E8',

          // 🟢 Success - Certified Green
          'success': '#16A34A',
          'success-darken-1': '#166534',
          'success-lighten-1': '#4ADE80',

          // 🟠 Warning - Alert Amber
          'warning': '#F97316',
          'warning-darken-1': '#C2410C',

          // 🔴 Error - Critical Red
          'error': '#EF4444',
          'error-darken-1': '#B91C1C',

          // ℹ️ Info - Data Cyan
          'info': '#0891B2',

          // ⚫ Neutrals - Adaptive Slate
          'background': '#FEFEFF', // White Soft
          'surface': '#FFFFFF',
          'on-surface': '#334155',
          'on-surface-variant': '#64748B',
          'surface-variant': '#F8FAFC',

          // 💜 Premium - Enterprise Purple
          'secondary': '#7C3AED',
        },
      },
      dark: {
        colors: {
          'primary': '#5C8AD6',
          'primary-darken-1': '#4471C4',
          'primary-lighten-1': '#75A3E8',

          'success': '#4ADE80',
          'success-darken-1': '#16A34A',

          'warning': '#FDBA74',
          'warning-darken-1': '#F97316',

          'error': '#F87171',
          'error-darken-1': '#EF4444',

          'info': '#22D3EE',

          'background': '#0F172A',
          'surface': '#1E293B',
          'on-surface': '#F1F5F9',
          'on-surface-variant': '#CBD5E1',
          'surface-variant': '#334155',

          'secondary': '#A78BFA',
        },
      },
    },
  },
  defaults: {
    global: {
      ripple: true,
    },
    VBtn: {
      style: 'text-transform: none; letter-spacing: 0.3px; font-weight: 600;',
      rounded: 'lg',
      elevation: 0,
    },
    VCard: {
      rounded: 'xl',
      elevation: 0,
      border: true,
      style: 'border-color: rgba(148, 163, 184, 0.1);',
    },
    VTextField: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VTextarea: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VAutocomplete: {
      variant: 'outlined',
      density: 'comfortable',
      rounded: 'lg',
      color: 'primary',
    },
    VChip: {
      rounded: 'pill',
      size: 'small',
    },
    VTooltip: {
      location: 'top',
    },
    VDialog: {
      rounded: 'xl',
    },
    VSheet: {
      rounded: 'lg',
    },
  },
})
