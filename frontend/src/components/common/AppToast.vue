<template>
  <Teleport to="body">
    <div class="toast-container" :class="`toast-position-${position}`">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :aria-live="toast.variant === 'error' ? 'assertive' : 'polite'"
          class="toast"
          :class="`toast-${toast.variant}`"
          role="alert"
        >
          <div class="toast-icon">
            <CheckCircle v-if="toast.variant === 'success'" :size="20" />
            <AlertCircle v-else-if="toast.variant === 'error'" :size="20" />
            <AlertTriangle v-else-if="toast.variant === 'warning'" :size="20" />
            <Info v-else :size="20" />
          </div>

          <div class="toast-content">
            <p v-if="toast.title" class="toast-title">{{ toast.title }}</p>
            <p class="toast-message">{{ toast.message }}</p>
          </div>

          <button
            v-if="toast.closable"
            aria-label="Fermer"
            class="toast-close"
            type="button"
            @click="removeToast(toast.id)"
          >
            <X :size="16" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
  import { AlertCircle, AlertTriangle, CheckCircle, Info, X } from 'lucide-vue-next'
  import { ref } from 'vue'

  interface Toast {
    id: string
    message: string
    title?: string
    variant: 'success' | 'error' | 'warning' | 'info'
    duration?: number
    closable?: boolean
  }

  interface Props {
    position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'
  }

  withDefaults(defineProps<Props>(), {
    position: 'top-right',
  })

  const toasts = ref<Toast[]>([])

  function addToast (toast: Omit<Toast, 'id'>) {
    const id = `toast-${Date.now()}-${Math.random()}`
    const newToast: Toast = {
      id,
      closable: true,
      duration: 5000,
      ...toast,
    }

    toasts.value.push(newToast)

    if (newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, newToast.duration)
    }

    return id
  }

  function removeToast (id: string) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function clearAll () {
    toasts.value = []
  }

  defineExpose({
    addToast,
    removeToast,
    clearAll,
  })
</script>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 1080;
  display: flex;
  flex-direction: column;
  gap: 12px;
  pointer-events: none;
  max-width: 420px;
  width: 100%;
  padding: 16px;
}

/* Positions */
.toast-position-top-right {
  top: 0;
  right: 0;
}

.toast-position-top-left {
  top: 0;
  left: 0;
}

.toast-position-bottom-right {
  bottom: 0;
  right: 0;
}

.toast-position-bottom-left {
  bottom: 0;
  left: 0;
}

.toast-position-top-center {
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.toast-position-bottom-center {
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
}

/* Toast */
.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
  border-left: 4px solid;
  pointer-events: auto;
  min-width: 300px;
}

.toast-success {
  border-left-color: #10B981;
}

.toast-error {
  border-left-color: #EF4444;
}

.toast-warning {
  border-left-color: #F59E0B;
}

.toast-info {
  border-left-color: #3B82F6;
}

/* Icon */
.toast-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.toast-success .toast-icon {
  background: #DCFCE7;
  color: #059669;
}

.toast-error .toast-icon {
  background: #FEE2E2;
  color: #DC2626;
}

.toast-warning .toast-icon {
  background: #FEF3C7;
  color: #D97706;
}

.toast-info .toast-icon {
  background: #DBEAFE;
  color: #2563EB;
}

/* Content */
.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: 14px;
  font-weight: 600;
  color: #1F2937;
  margin: 0 0 4px 0;
}

.toast-message {
  font-size: 14px;
  color: #6B7280;
  margin: 0;
  line-height: 1.5;
}

/* Close button */
.toast-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: none;
  background: transparent;
  color: #9CA3AF;
  border-radius: 4px;
  cursor: pointer;
  transition: all 150ms;
  flex-shrink: 0;
}

.toast-close:hover {
  background: #F3F4F6;
  color: #6B7280;
}

.toast-close:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(68, 113, 196, 0.2);
}

/* Transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.toast-position-top-left .toast-enter-from,
.toast-position-bottom-left .toast-enter-from {
  transform: translateX(-100%);
}

.toast-position-top-left .toast-leave-to,
.toast-position-bottom-left .toast-leave-to {
  transform: translateX(-100%) scale(0.95);
}

.toast-position-top-center .toast-enter-from,
.toast-position-bottom-center .toast-enter-from {
  transform: translateY(-100%);
}

.toast-position-top-center .toast-leave-to,
.toast-position-bottom-center .toast-leave-to {
  transform: translateY(-100%) scale(0.95);
}

/* Responsive */
@media (max-width: 640px) {
  .toast-container {
    max-width: 100%;
    padding: 12px;
  }

  .toast {
    min-width: auto;
  }
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .toast {
    background: #1F2937;
  }

  .toast-title {
    color: #F9FAFB;
  }

  .toast-message {
    color: #D1D5DB;
  }

  .toast-close {
    color: #6B7280;
  }

  .toast-close:hover {
    background: #374151;
    color: #9CA3AF;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active,
  .toast-leave-active {
    transition: none !important;
  }
}
</style>
