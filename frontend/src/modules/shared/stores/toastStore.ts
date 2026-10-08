import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message: string
  duration: number
  actionLabel?: string
  action?: () => void
}

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<Toast[]>([])

  function add (toast: Omit<Toast, 'id'>) {
    const id = `toast-${Date.now()}-${Math.random()}`
    const newToast: Toast = {
      id,
      ...toast,
    }

    toasts.value.push(newToast)

    setTimeout(() => {
      remove(id)
    }, toast.duration)

    return id
  }

  function remove (id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function clear () {
    toasts.value = []
  }

  return {
    toasts,
    add,
    remove,
    clear,
  }
})
