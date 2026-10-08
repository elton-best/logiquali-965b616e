/**
 * useTheme Composable
 * Handles dark mode and theme switching
 */

import { ref } from 'vue'
import { storage } from '@/utils/storage'

const THEME_STORAGE_KEY = 'qb-theme'
type Theme = 'light' | 'dark' | 'system'

const isDark = ref(false)
const theme = ref<Theme>('system')

// Initialize theme from localStorage or system preference
function initTheme (): void {
  const savedTheme = storage.get<Theme>(THEME_STORAGE_KEY)

  if (savedTheme) {
    theme.value = savedTheme
  }

  updateTheme()
}

// Update theme based on selection
function updateTheme (): void {
  let shouldBeDark = false

  if (theme.value === 'dark') {
    shouldBeDark = true
  } else if (theme.value === 'system') {
    shouldBeDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  isDark.value = shouldBeDark

  document.documentElement.classList.toggle('dark', shouldBeDark)
}

// Watch for system theme changes
if (typeof window !== 'undefined') {
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', () => {
    if (theme.value === 'system') {
      updateTheme()
    }
  })
}

export function useTheme () {
  // Set theme
  function setTheme (newTheme: Theme): void {
    theme.value = newTheme
    storage.set(THEME_STORAGE_KEY, newTheme)
    updateTheme()
  }

  // Toggle between light and dark
  function toggleTheme (): void {
    setTheme(isDark.value ? 'light' : 'dark')
  }

  // Initialize on mount
  if (!isDark.value && !storage.get(THEME_STORAGE_KEY)) {
    initTheme()
  }

  return {
    isDark,
    theme,
    setTheme,
    toggleTheme,
  }
}
