<template>
  <Teleport to="body">
    <TransitionGroup
      class="toast-container"
      :class="position"
      name="toast"
      tag="div"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
        :class="['toast', `toast-${toast.type}`]"
        role="alert"
      >
        <div class="toast-icon">
          <v-icon :color="getIconColor(toast.type)" size="24">
            {{ getIcon(toast.type) }}
          </v-icon>
        </div>
        <div class="toast-content">
          <div v-if="toast.title" class="toast-title">{{ toast.title }}</div>
          <div class="toast-message">{{ toast.message }}</div>
          <div v-if="toast.actionLabel" class="toast-actions">
            <button
              class="toast-action"
              type="button"
              @click.stop="handleAction(toast)"
            >
              {{ toast.actionLabel }}
            </button>
          </div>
        </div>
        <button
          aria-label="Fermer"
          class="toast-close"
          @click="removeToast(toast.id)"
        >
          <v-icon size="20">mdi-close</v-icon>
        </button>
      </div>
    </TransitionGroup>
  </Teleport>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import { useToastStore } from '@/modules/shared/stores/toastStore'

  interface Props {
    position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'
  }

  const _props = withDefaults(defineProps<Props>(), {
    position: 'top-right',
  })

  const toastStore = useToastStore()
  const toasts = computed(() => toastStore.toasts)

  function removeToast (id: string) {
    toastStore.remove(id)
  }

  function handleAction (toast: { id: string, action?: () => void }) {
    if (toast.action) {
      toast.action()
    }
    removeToast(toast.id)
  }

  function getIcon (type: string) {
    const icons: Record<string, string> = {
      success: 'mdi-check-circle',
      error: 'mdi-alert-circle',
      warning: 'mdi-alert',
      info: 'mdi-information',
    }
    return icons[type] || icons.info
  }

  function getIconColor (type: string) {
    const colors: Record<string, string> = {
      success: 'success',
      error: 'error',
      warning: 'warning',
      info: 'info',
    }
    return colors[type] || colors.info
  }
</script>

<style scoped>
.toast-container {
  position: fixed;
  z-index: 9999;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-width: 420px;
  width: 100%;
  padding: 16px;
}

.toast-container.top-right {
  top: 0;
  right: 0;
}

.toast-container.top-left {
  top: 0;
  left: 0;
}

.toast-container.bottom-right {
  bottom: 0;
  right: 0;
}

.toast-container.bottom-left {
  bottom: 0;
  left: 0;
}

.toast-container.top-center {
  top: 0;
  left: 50%;
  transform: translateX(-50%);
}

.toast-container.bottom-center {
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
}

.toast {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border-radius: 12px;
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12),
              0 4px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  min-height: 64px;
}

.toast:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.16),
              0 6px 12px rgba(0, 0, 0, 0.12);
}

.toast-success {
  border-left: 4px solid rgb(var(--v-theme-success));
}

.toast-error {
  border-left: 4px solid rgb(var(--v-theme-error));
}

.toast-warning {
  border-left: 4px solid rgb(var(--v-theme-warning));
}

.toast-info {
  border-left: 4px solid rgb(var(--v-theme-info));
}

.toast-icon {
  flex-shrink: 0;
  animation: iconPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes iconPop {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.toast-content {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 600;
  font-size: 14px;
  line-height: 20px;
  color: rgb(var(--v-theme-on-surface));
  margin-bottom: 4px;
}

.toast-message {
  font-size: 13px;
  line-height: 18px;
  color: rgb(var(--v-theme-on-surface-variant));
}

.toast-actions {
  margin-top: 8px;
}

.toast-action {
  border: none;
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.toast-action:hover {
  background: rgba(var(--v-theme-primary), 0.18);
}

.toast-action:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

.toast-close {
  flex-shrink: 0;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  color: rgb(var(--v-theme-on-surface-variant));
  transition: all 0.2s ease;
  opacity: 0.6;
}

.toast-close:hover {
  opacity: 1;
  background: rgba(var(--v-theme-on-surface), 0.08);
}

.toast-close:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

/* Toast animations */
.toast-enter-active {
  animation: toastSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-leave-active {
  animation: toastSlideOut 0.2s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes toastSlideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes toastSlideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

.top-left .toast-enter-active,
.bottom-left .toast-enter-active {
  animation: toastSlideInLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.top-left .toast-leave-active,
.bottom-left .toast-leave-active {
  animation: toastSlideOutLeft 0.2s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes toastSlideInLeft {
  from {
    transform: translateX(-100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes toastSlideOutLeft {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(-100%);
    opacity: 0;
  }
}

.top-center .toast-enter-active,
.bottom-center .toast-enter-active {
  animation: toastSlideInTop 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.top-center .toast-leave-active,
.bottom-center .toast-leave-active {
  animation: toastSlideOutTop 0.2s cubic-bezier(0.4, 0, 1, 1);
}

@keyframes toastSlideInTop {
  from {
    transform: translateX(-50%) translateY(-100%);
    opacity: 0;
  }
  to {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
  }
}

@keyframes toastSlideOutTop {
  from {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
  }
  to {
    transform: translateX(-50%) translateY(-100%);
    opacity: 0;
  }
}

/* Mobile responsive */
@media (max-width: 640px) {
  .toast-container {
    max-width: 100%;
    padding: 12px;
  }

  .toast {
    padding: 12px;
  }

  .toast-title {
    font-size: 13px;
  }

  .toast-message {
    font-size: 12px;
  }
}
</style>
