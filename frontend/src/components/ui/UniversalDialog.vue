<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center"
      @click="handleBackdropClick"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity" />

      <!-- Dialog -->
      <div
        class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all"
        :class="sizeClasses"
        @click.stop
      >
        <!-- Header -->
        <div v-if="title || $slots.header" class="flex items-center justify-between p-6 border-b border-gray-200">
          <div class="flex items-center space-x-3">
            <div v-if="icon" class="p-2 rounded-full" :class="iconClasses">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path :d="iconPath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
              <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
            </div>
          </div>
          <button
            v-if="closable"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            @click="close"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </button>
          <slot name="header" />
        </div>

        <!-- Content -->
        <div class="p-6" :class="contentClasses">
          <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer || showDefaultActions" class="flex justify-end space-x-3 p-6 border-t border-gray-200">
          <slot name="footer">
            <button
              v-if="showDefaultActions"
              class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              @click="close"
            >
              {{ cancelText }}
            </button>
            <button
              v-if="showDefaultActions && confirmText"
              class="px-4 py-2 rounded-lg transition-colors"
              :class="confirmButtonClasses"
              @click="confirm"
            >
              {{ confirmText }}
            </button>
          </slot>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    modelValue: boolean
    title?: string
    subtitle?: string
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
    type?: 'default' | 'success' | 'warning' | 'danger' | 'info'
    icon?: string
    closable?: boolean
    closeOnBackdrop?: boolean
    showDefaultActions?: boolean
    confirmText?: string
    cancelText?: string
    contentClasses?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    type: 'default',
    closable: true,
    closeOnBackdrop: true,
    showDefaultActions: false,
    cancelText: 'Annuler',
    contentClasses: '',
  })

  const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'confirm': []
    'close': []
  }>()

  const sizeClasses = computed(() => {
    const sizes = {
      sm: 'max-w-sm',
      md: 'max-w-md',
      lg: 'max-w-lg',
      xl: 'max-w-xl',
      full: 'max-w-4xl',
    }
    return sizes[props.size]
  })

  const iconClasses = computed(() => {
    const classes = {
      default: 'bg-gray-100 text-gray-600',
      success: 'bg-green-100 text-green-600',
      warning: 'bg-yellow-100 text-yellow-600',
      danger: 'bg-red-100 text-red-600',
      info: 'bg-blue-100 text-blue-600',
    }
    return classes[props.type]
  })

  const confirmButtonClasses = computed(() => {
    const classes = {
      default: 'bg-blue-600 text-white hover:bg-blue-700',
      success: 'bg-green-600 text-white hover:bg-green-700',
      warning: 'bg-yellow-600 text-white hover:bg-yellow-700',
      danger: 'bg-red-600 text-white hover:bg-red-700',
      info: 'bg-blue-600 text-white hover:bg-blue-700',
    }
    return classes[props.type]
  })

  const iconPath = computed(() => {
    const paths = {
      success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
      warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z',
      danger: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z',
      info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
      default: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    }
    return paths[props.icon as keyof typeof paths] || paths.default
  })

  function close () {
    emit('update:modelValue', false)
    emit('close')
  }

  function confirm () {
    emit('confirm')
  }

  function handleBackdropClick () {
    if (props.closeOnBackdrop) {
      close()
    }
  }
</script>
