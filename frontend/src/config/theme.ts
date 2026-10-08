/**
 * QualiBest Design System
 * Modern color palette and theming configuration
 */

export const colors = {
  // Primary Brand Colors (Blue)
  primary: {
    50: '#EFF6FF',
    100: '#DBEAFE',
    200: '#BFDBFE',
    300: '#93C5FD',
    400: '#60A5FA',
    500: '#3B82F6', // Main primary
    600: '#2563EB',
    700: '#1D4ED8',
    800: '#1E40AF',
    900: '#1E3A8A',
    950: '#172554',
  },

  // Secondary/Accent Colors (Green for success/conformity)
  accent: {
    50: '#ECFDF5',
    100: '#D1FAE5',
    200: '#A7F3D0',
    300: '#6EE7B7',
    400: '#34D399',
    500: '#10B981', // Main accent
    600: '#059669',
    700: '#047857',
    800: '#065F46',
    900: '#064E3B',
    950: '#022C22',
  },

  // Neutral Colors
  neutral: {
    50: '#F8FAFC',
    100: '#F1F5F9',
    200: '#E2E8F0',
    300: '#CBD5E1',
    400: '#94A3B8',
    500: '#64748B',
    600: '#475569',
    700: '#334155',
    800: '#1E293B',
    900: '#0F172A',
    950: '#020617',
  },

  // Semantic Colors
  success: {
    light: '#D1FAE5',
    DEFAULT: '#10B981',
    dark: '#065F46',
  },
  warning: {
    light: '#FEF3C7',
    DEFAULT: '#F59E0B',
    dark: '#92400E',
  },
  error: {
    light: '#FEE2E2',
    DEFAULT: '#EF4444',
    dark: '#991B1B',
  },
  info: {
    light: '#DBEAFE',
    DEFAULT: '#3B82F6',
    dark: '#1E40AF',
  },
} as const

export const spacing = {
  'xs': '0.25rem', // 4px
  'sm': '0.5rem', // 8px
  'md': '1rem', // 16px
  'lg': '1.5rem', // 24px
  'xl': '2rem', // 32px
  '2xl': '3rem', // 48px
  '3xl': '4rem', // 64px
  '4xl': '6rem', // 96px
} as const

export const borderRadius = {
  'none': '0',
  'sm': '0.25rem', // 4px
  'DEFAULT': '0.5rem', // 8px
  'md': '0.75rem', // 12px
  'lg': '1rem', // 16px
  'xl': '1.5rem', // 24px
  '2xl': '2rem', // 32px
  'full': '9999px',
} as const

export const shadows = {
  'sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
  'DEFAULT': '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
  'md': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
  'lg': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
  'xl': '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
  '2xl': '0 25px 50px -12px rgb(0 0 0 / 0.25)',
  'inner': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
  'none': 'none',
} as const

export const typography = {
  fontFamily: {
    sans: [
      'Inter',
      'ui-sans-serif',
      'system-ui',
      '-apple-system',
      'BlinkMacSystemFont',
      'Segoe UI',
      'Roboto',
      'Helvetica Neue',
      'Arial',
      'sans-serif',
    ].join(', '),
    mono: [
      'ui-monospace',
      'SFMono-Regular',
      'Menlo',
      'Monaco',
      'Consolas',
      'Liberation Mono',
      'Courier New',
      'monospace',
    ].join(', '),
  },
  fontSize: {
    'xs': ['0.75rem', { lineHeight: '1rem' }], // 12px
    'sm': ['0.875rem', { lineHeight: '1.25rem' }], // 14px
    'base': ['1rem', { lineHeight: '1.5rem' }], // 16px
    'lg': ['1.125rem', { lineHeight: '1.75rem' }], // 18px
    'xl': ['1.25rem', { lineHeight: '1.75rem' }], // 20px
    '2xl': ['1.5rem', { lineHeight: '2rem' }], // 24px
    '3xl': ['1.875rem', { lineHeight: '2.25rem' }], // 30px
    '4xl': ['2.25rem', { lineHeight: '2.5rem' }], // 36px
    '5xl': ['3rem', { lineHeight: '1' }], // 48px
  },
  fontWeight: {
    light: '300',
    normal: '400',
    medium: '500',
    semibold: '600',
    bold: '700',
    extrabold: '800',
  },
} as const

export const transitions = {
  duration: {
    fast: '150ms',
    DEFAULT: '200ms',
    slow: '300ms',
    slower: '500ms',
  },
  timing: {
    ease: 'ease',
    linear: 'linear',
    easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
    easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
    easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
  },
} as const

// Dark mode color overrides
export const darkModeColors = {
  background: {
    primary: colors.neutral[950],
    secondary: colors.neutral[900],
    tertiary: colors.neutral[800],
  },
  text: {
    primary: colors.neutral[50],
    secondary: colors.neutral[300],
    tertiary: colors.neutral[400],
  },
  border: {
    DEFAULT: colors.neutral[700],
    light: colors.neutral[800],
  },
} as const

// Light mode color overrides
export const lightModeColors = {
  background: {
    primary: '#FFFFFF',
    secondary: colors.neutral[50],
    tertiary: colors.neutral[100],
  },
  text: {
    primary: colors.neutral[900],
    secondary: colors.neutral[600],
    tertiary: colors.neutral[500],
  },
  border: {
    DEFAULT: colors.neutral[200],
    light: colors.neutral[100],
  },
} as const

export const theme = {
  colors,
  spacing,
  borderRadius,
  shadows,
  typography,
  transitions,
  darkModeColors,
  lightModeColors,
} as const

export type Theme = typeof theme
