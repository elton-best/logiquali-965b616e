<script setup lang="ts">
  import { X } from 'lucide-vue-next'
  import { DialogClose, DialogContent, DialogOverlay, DialogPortal } from 'radix-vue'
  import { cn } from './utils'

  defineOptions({
    name: 'DialogContent',
    inheritAttrs: false,
  })

  const props = defineProps<{
    class?: string
    forceMount?: boolean
  }>()
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="dialog-overlay"
      data-slot="dialog-overlay"
    />
    <DialogContent
      v-bind="$attrs"
      :class="cn(
        'dialog-content',
        props.class
      )"
      data-slot="dialog-content"
      :force-mount="forceMount"
    >
      <slot />
      <DialogClose
        class="dialog-close"
      >
        <X />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>

<style scoped>
.dialog-overlay {
  position: fixed;
  inset: 0;
  z-index: var(--z-modal-backdrop);
  background: rgba(0, 0, 0, 0.5);
  animation: fadeIn 200ms ease;
}

.dialog-content {
  position: fixed;
  top: 50%;
  left: 50%;
  z-index: var(--z-modal);
  display: grid;
  width: 100%;
  max-width: calc(100% - 2rem);
  transform: translate(-50%, -50%);
  gap: var(--spacing-4);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  padding: var(--spacing-6);
  background: var(--bg-primary);
  box-shadow: var(--shadow-xl);
  animation: slideUp 200ms ease;
}

@media (min-width: 640px) {
  .dialog-content {
    max-width: 32rem;
  }
}

.dialog-close {
  position: absolute;
  top: var(--spacing-4);
  right: var(--spacing-4);
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: var(--radius-sm);
  opacity: 0.7;
  cursor: pointer;
  transition: all var(--transition-base);
}

.dialog-close:hover {
  opacity: 1;
  background: var(--bg-secondary);
}

.dialog-close:focus {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translate(-50%, -48%) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
  }
}
</style>
