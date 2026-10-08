export interface SmoothScrollIntoViewOptions {
  behavior?: 'smooth' | 'auto'
  block?: 'start' | 'center' | 'end' | 'nearest'
}

/**
 * Composable pour le smooth scrolling
 *
 * @example
 * ```vue
 * <template>
 *   <button @click="scrollToTop">Retour en haut</button>
 *   <button @click="scrollToElement('#section2')">Section 2</button>
 * </template>
 *
 * <script setup>
 * import { useSmoothScroll } from '@/modules/shared/composables/useSmoothScroll'
 *
 * const { scrollToTop, scrollToElement, scrollToPosition } = useSmoothScroll()
 * </script>
 * ```
 */
export function useSmoothScroll () {
  const scrollToPosition = (
    position: number,
    duration = 500,
    easing: 'easeInOut' | 'easeOut' | 'linear' = 'easeInOut',
  ) => {
    const start = window.pageYOffset
    const distance = position - start
    const startTime = performance.now()

    const easingFunctions = {
      linear: (t: number) => t,
      easeOut: (t: number) => t * (2 - t),
      easeInOut: (t: number) => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t,
    }

    const animate = (currentTime: number) => {
      const elapsed = currentTime - startTime
      const progress = Math.min(elapsed / duration, 1)
      const easeProgress = easingFunctions[easing](progress)

      window.scrollTo(0, start + distance * easeProgress)

      if (progress < 1) {
        requestAnimationFrame(animate)
      }
    }

    requestAnimationFrame(animate)
  }

  const scrollToTop = (duration = 500) => {
    scrollToPosition(0, duration)
  }

  const scrollToElement = (
    selector: string | HTMLElement,
    offset = 0,
    duration = 500,
  ) => {
    const element = typeof selector === 'string'
      ? document.querySelector(selector) as HTMLElement
      : selector

    if (!element) {
      console.warn(`Element not found: ${selector}`)
      return
    }

    const targetPosition = element.getBoundingClientRect().top + window.pageYOffset - offset
    scrollToPosition(targetPosition, duration)
  }

  const scrollIntoView = (
    element: HTMLElement,
    options?: SmoothScrollIntoViewOptions,
  ) => {
    element.scrollIntoView(options || { behavior: 'smooth', block: 'start' })
  }

  return {
    scrollToPosition,
    scrollToTop,
    scrollToElement,
    scrollIntoView,
  }
}

/**
 * Active le smooth scrolling pour toute l'application
 */
export function enableSmoothScrolling () {
  // CSS-based smooth scrolling
  if (typeof document !== 'undefined') {
    document.documentElement.style.scrollBehavior = 'smooth'
  }
}
