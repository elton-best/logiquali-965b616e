import { onMounted, onUnmounted } from 'vue'

/**
 * Composable pour gérer la navigation au clavier
 *
 * @example
 * ```vue
 * <script setup>
 * import { useKeyboard } from '@/modules/shared/composables/useKeyboard'
 *
 * useKeyboard({
 *   'Escape': () => closeModal(),
 *   'Enter': () => submitForm(),
 *   'ArrowUp': () => navigateUp(),
 *   'ArrowDown': () => navigateDown()
 * })
 * </script>
 * ```
 */
export function useKeyboard (handlers: Record<string, (event: KeyboardEvent) => void>) {
  const handleKeydown = (event: KeyboardEvent) => {
    const handler = handlers[event.key]
    if (handler) {
      handler(event)
    }
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
  })

  return {
    handleKeydown,
  }
}

/**
 * Composable pour gérer le focus trap dans un élément
 * Utile pour les modals et dialogs accessibles
 *
 * @example
 * ```vue
 * <template>
 *   <div ref="modalRef" role="dialog" aria-modal="true">
 *     <button>First focusable</button>
 *     <input type="text" />
 *     <button>Last focusable</button>
 *   </div>
 * </template>
 *
 * <script setup>
 * import { ref, watch } from 'vue'
 * import { useFocusTrap } from '@/modules/shared/composables/useKeyboard'
 *
 * const modalRef = ref(null)
 * const isOpen = ref(false)
 *
 * const { activate, deactivate } = useFocusTrap(modalRef)
 *
 * watch(isOpen, (open) => {
 *   if (open) activate()
 *   else deactivate()
 * })
 * </script>
 * ```
 */
export function useFocusTrap (containerRef: any) {
  let firstFocusable: HTMLElement | null = null
  let lastFocusable: HTMLElement | null = null

  const getFocusableElements = () => {
    if (!containerRef.value) {
      return []
    }

    const focusableSelectors = [
      'a[href]',
      'button:not([disabled])',
      'textarea:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(', ')

    return Array.from(
      containerRef.value.querySelectorAll(focusableSelectors),
    ) as HTMLElement[]
  }

  const handleTabKey = (e: KeyboardEvent) => {
    const focusableElements = getFocusableElements()

    if (focusableElements.length === 0) {
      return
    }

    firstFocusable = focusableElements[0] || null
    lastFocusable = focusableElements.at(-1) || null

    if (e.key === 'Tab') {
      if (e.shiftKey && document.activeElement === firstFocusable) {
        e.preventDefault()
        lastFocusable?.focus()
      } else if (!e.shiftKey && document.activeElement === lastFocusable) {
        e.preventDefault()
        firstFocusable?.focus()
      }
    }
  }

  const activate = () => {
    const focusableElements = getFocusableElements()
    if (focusableElements.length > 0) {
      const first = focusableElements[0]
      if (first) {
        first.focus()
      }
    }
    window.addEventListener('keydown', handleTabKey)
  }

  const deactivate = () => {
    window.removeEventListener('keydown', handleTabKey)
  }

  onUnmounted(() => {
    deactivate()
  })

  return {
    activate,
    deactivate,
  }
}
