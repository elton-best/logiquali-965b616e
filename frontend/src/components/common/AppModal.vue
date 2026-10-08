<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="modal-overlay"
        :class="{ 'modal-blur': blur }"
        @click="handleOverlayClick"
      >
        <div
          :aria-labelledby="titleId"
          aria-modal="true"
          class="modal-container"
          :class="[`modal-${size}`, { 'modal-fullscreen': fullscreen }]"
          role="dialog"
          @click.stop
        >
          <!-- Header -->
          <div v-if="!hideHeader" class="modal-header">
            <h2 :id="titleId" class="modal-title">
              <slot name="title">{{ title }}</slot>
            </h2>
            <button
              v-if="closable"
              aria-label="Fermer"
              class="modal-close"
              type="button"
              @click="handleClose"
            >
              <X :size="20" />
            </button>
          </div>

          <!-- Body -->
          <div class="modal-body" :class="{ 'modal-body-padded': !noPadding }">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
  import { X } from 'lucide-vue-next'
  import { onMounted, onUnmounted, useId, watch } from 'vue'

  interface Props {
    modelValue: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
    closable?: boolean
    closeOnOverlay?: boolean
    hideHeader?: boolean
    noPadding?: boolean
    fullscreen?: boolean
    blur?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    closable: true,
    closeOnOverlay: true,
    hideHeader: false,
    noPadding: false,
    fullscreen: false,
    blur: true,
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'close': []
  }>()

  const titleId = `modal-title-${useId()}`

  function handleClose () {
    emit('update:modelValue', false)
    emit('close')
  }

  function handleOverlayClick () {
    if (props.closeOnOverlay) {
      handleClose()
    }
  }

  function handleEscape (event: KeyboardEvent) {
    if (event.key === 'Escape' && props.closable && props.modelValue) {
      handleClose()
    }
  }

  // Lock body scroll when modal is open
  watch(() => props.modelValue, isOpen => {
    document.body.style.overflow = isOpen ? 'hidden' : ''
  })

  onMounted(() => {
    document.addEventListener('keydown', handleEscape)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
    document.body.style.overflow = ''
  })
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 16px;
  overflow-y: auto;
}

.modal-overlay.modal-blur {
  backdrop-filter: blur(4px);
}

.modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-height: calc(100vh - 32px);
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Sizes */
.modal-sm { max-width: 400px; }
.modal-md { max-width: 600px; }
.modal-lg { max-width: 800px; }
.modal-xl { max-width: 1200px; }
.modal-full { max-width: 100%; }

.modal-fullscreen {
  max-width: 100%;
  max-height: 100vh;
  height: 100vh;
  border-radius: 0;
  margin: 0;
}

/* Header */
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #E5E7EB;
  flex-shrink: 0;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #1F2937;
  margin: 0;
}

.modal-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #6B7280;
  border-radius: 6px;
  cursor: pointer;
  transition: all 150ms;
  flex-shrink: 0;
}

.modal-close:hover {
  background: #F3F4F6;
  color: #374151;
}

.modal-close:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.2);
}

/* Body */
.modal-body {
  flex: 1;
  overflow-y: auto;
}

.modal-body-padded {
  padding: 24px;
}

/* Footer */
.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #E5E7EB;
  flex-shrink: 0;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 200ms ease;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: all 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.95) translateY(-20px);
  opacity: 0;
}

/* Scrollbar */
.modal-body::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track {
  background: #F3F4F6;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #D1D5DB;
  border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-overlay {
    padding: 0;
  }

  .modal-container {
    max-height: 100vh;
    border-radius: 0;
  }

  .modal-header {
    padding: 16px;
  }

  .modal-body-padded {
    padding: 16px;
  }

  .modal-footer {
    padding: 12px 16px;
  }
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .modal-container {
    background: #1F2937;
  }

  .modal-header,
  .modal-footer {
    border-color: #374151;
  }

  .modal-title {
    color: #F9FAFB;
  }

  .modal-close {
    color: #9CA3AF;
  }

  .modal-close:hover {
    background: #374151;
    color: #E5E7EB;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active,
  .modal-leave-active,
  .modal-enter-active .modal-container,
  .modal-leave-active .modal-container {
    transition: none !important;
  }
}
</style>
