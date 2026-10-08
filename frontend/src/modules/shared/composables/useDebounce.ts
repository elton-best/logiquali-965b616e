import { onUnmounted, ref } from 'vue'

/**
 * Composable pour implémenter un debounce sur une fonction
 *
 * @example
 * ```vue
 * <template>
 *   <input v-model="searchTerm" @input="debouncedSearch" placeholder="Search..." />
 * </template>
 *
 * <script setup>
 * import { ref } from 'vue'
 * import { useDebounce } from '@/modules/shared/composables/useDebounce'
 *
 * const searchTerm = ref('')
 *
 * const performSearch = () => {
 *   console.log('Searching for:', searchTerm.value)
 *   // API call here
 * }
 *
 * const debouncedSearch = useDebounce(performSearch, 500)
 * </script>
 * ```
 */
export function useDebounce<T extends (...args: any[]) => any> (
  fn: T,
  delay = 300,
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout> | null = null

  return function (this: any, ...args: Parameters<T>) {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    timeoutId = setTimeout(() => {
      fn.apply(this, args)
    }, delay)
  }
}

/**
 * Composable pour créer une valeur debouncée réactive
 *
 * @example
 * ```vue
 * <script setup>
 * import { ref, watch } from 'vue'
 * import { useDebouncedRef } from '@/modules/shared/composables/useDebounce'
 *
 * const searchTerm = ref('')
 * const debouncedSearchTerm = useDebouncedRef(searchTerm, 500)
 *
 * watch(debouncedSearchTerm, (value) => {
 *   console.log('Debounced search:', value)
 *   // Perform search
 * })
 * </script>
 * ```
 */
export function useDebouncedRef<T> (value: T, delay = 300) {
  const debouncedValue = ref<T>(value)
  let timeoutId: ReturnType<typeof setTimeout> | null = null

  const updateValue = (newValue: T) => {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }

    timeoutId = setTimeout(() => {
      debouncedValue.value = newValue
    }, delay)
  }

  onUnmounted(() => {
    if (timeoutId) {
      clearTimeout(timeoutId)
    }
  })

  return {
    value: debouncedValue,
    update: updateValue,
  }
}
