<template>
  <TransitionRoot as="template" :show="modelValue">
    <Dialog as="div" class="relative z-50" @close="handleClose">
      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" />
      </TransitionChild>

      <!-- Modal container -->
      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              :class="[
                'w-full transform overflow-hidden rounded-lg bg-white dark:bg-gray-800',
                'shadow-xl transition-all',
                sizeClasses
              ]"
            >
              <!-- Header -->
              <div
                v-if="title || $slots.header"
                class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4"
              >
                <DialogTitle
                  as="h3"
                  class="text-lg font-semibold text-gray-900 dark:text-white"
                >
                  <slot name="header">{{ title }}</slot>
                </DialogTitle>
                <button
                  v-if="showClose"
                  class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                  type="button"
                  @click="handleClose"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div :class="['px-6 py-4', bodyClass]">
                <slot />
              </div>

              <!-- Footer -->
              <div
                v-if="$slots.footer"
                class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4"
              >
                <slot name="footer" />
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
  import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
  } from '@headlessui/vue'
  import { computed } from 'vue'

  interface Props {
    modelValue: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
    showClose?: boolean
    bodyClass?: string
    closeOnClickOutside?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showClose: true,
    closeOnClickOutside: true,
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'close': []
  }>()

  const sizeClasses = computed(() => {
    const sizes = {
      'sm': 'max-w-sm',
      'md': 'max-w-md',
      'lg': 'max-w-lg',
      'xl': 'max-w-xl',
      '2xl': 'max-w-2xl',
      'full': 'max-w-full mx-4',
    }
    return sizes[props.size]
  })

  function handleClose () {
    if (props.closeOnClickOutside) {
      emit('update:modelValue', false)
      emit('close')
    }
  }
</script>
