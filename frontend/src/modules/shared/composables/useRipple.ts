/**
 * Composable pour ajouter un effet ripple sur les éléments
 *
 * @example
 * ```vue
 * <button @click="handleClick" ref="buttonRef">
 *   Click me
 * </button>
 *
 * <script setup>
 * import { ref } from 'vue'
 * import { useRipple } from '@/modules/shared/composables/useRipple'
 *
 * const buttonRef = ref(null)
 * const { createRipple } = useRipple()
 *
 * const handleClick = (e) => {
 *   createRipple(e, buttonRef.value)
 * }
 * </script>
 * ```
 */
export function useRipple () {
  function createRipple (event: MouseEvent, element: HTMLElement) {
    const ripple = document.createElement('span')
    const rect = element.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)
    const x = event.clientX - rect.left - size / 2
    const y = event.clientY - rect.top - size / 2

    ripple.style.width = ripple.style.height = `${size}px`
    ripple.style.left = `${x}px`
    ripple.style.top = `${y}px`
    ripple.classList.add('ripple-effect')

    const existingRipple = element.querySelector('.ripple-effect')
    if (existingRipple) {
      existingRipple.remove()
    }

    element.style.position = 'relative'
    element.style.overflow = 'hidden'
    element.append(ripple)

    setTimeout(() => {
      ripple.remove()
    }, 600)
  }

  return {
    createRipple,
  }
}

// Ajouter les styles CSS dans votre fichier global ou composant
export const rippleStyles = `
.ripple-effect {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.5);
  transform: scale(0);
  animation: ripple-animation 0.6s ease-out;
  pointer-events: none;
}

@keyframes ripple-animation {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
`
