<template>
  <button
    :aria-label="ariaLabel"
    class="app-icon-button"
    :class="buttonClasses"
    :disabled="disabled || loading"
    :type="type"
    @click="handleClick"
  >
    <Loader2 v-if="loading" class="animate-spin" :size="iconSize" />
    <component :is="icon" v-else :size="iconSize" />
  </button>
</template>

<script setup lang="ts">
  import { Loader2 } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    icon: any
    ariaLabel: string
    variant?: 'primary' | 'secondary' | 'success' | 'error' | 'ghost' | 'outline'
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    loading?: boolean
    rounded?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'ghost',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    rounded: false,
  })

  const emit = defineEmits<{
    click: [event: MouseEvent]
  }>()

  const buttonClasses = computed(() => ({
    [`button-${props.variant}`]: true,
    [`button-${props.size}`]: true,
    'button-rounded': props.rounded,
    'button-loading': props.loading,
    'button-disabled': props.disabled,
  }))

  const iconSize = computed(() => {
    const sizes = { xs: 14, sm: 16, md: 18, lg: 20, xl: 22 }
    return sizes[props.size]
  })

  function handleClick (event: MouseEvent) {
    if (!props.disabled && !props.loading) {
      emit('click', event)
    }
  }
</script>

<style scoped>
.app-icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.app-icon-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.2);
}

.app-icon-button:active:not(.button-disabled):not(.button-loading) {
  transform: scale(0.95);
}

/* Variants */
.button-primary { background: #4471C4; color: white; border-color: #4471C4; }
.button-primary:hover:not(.button-disabled):not(.button-loading) { background: #365A9D; }

.button-secondary { background: white; color: #4471C4; border-color: #4471C4; }
.button-secondary:hover:not(.button-disabled):not(.button-loading) { background: #EEF2FB; }

.button-success { background: #10B981; color: white; border-color: #10B981; }
.button-success:hover:not(.button-disabled):not(.button-loading) { background: #059669; }

.button-error { background: #EF4444; color: white; border-color: #EF4444; }
.button-error:hover:not(.button-disabled):not(.button-loading) { background: #DC2626; }

.button-ghost { background: transparent; color: #6B7280; border-color: transparent; }
.button-ghost:hover:not(.button-disabled):not(.button-loading) { background: #F3F4F6; color: #374151; }

.button-outline { background: transparent; color: #374151; border-color: #D1D5DB; }
.button-outline:hover:not(.button-disabled):not(.button-loading) { background: #F9FAFB; }

/* Sizes */
.button-xs { padding: 6px; min-width: 28px; min-height: 28px; }
.button-sm { padding: 8px; min-width: 36px; min-height: 36px; }
.button-md { padding: 10px; min-width: 44px; min-height: 44px; }
.button-lg { padding: 12px; min-width: 52px; min-height: 52px; }
.button-xl { padding: 14px; min-width: 60px; min-height: 60px; }

.button-rounded { border-radius: 9999px; }
.button-disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
.button-loading { cursor: wait; pointer-events: none; }

@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@media (prefers-reduced-motion: reduce) {
  .app-icon-button { transition: none !important; }
  .app-icon-button:active { transform: none !important; }
  .animate-spin { animation: none !important; }
}
</style>
