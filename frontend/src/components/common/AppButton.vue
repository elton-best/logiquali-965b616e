<template>
  <component
    :is="tag"
    class="app-button"
    :class="buttonClasses"
    :disabled="disabled || loading"
    :href="tag === 'a' ? href : undefined"
    :to="tag === 'router-link' ? to : undefined"
    :type="tag === 'button' ? type : undefined"
    @click="handleClick"
  >
    <!-- Loading spinner -->
    <Loader2 v-if="loading" class="button-icon animate-spin" :size="iconSize" />

    <!-- Prefix icon -->
    <component
      :is="prefixIcon"
      v-else-if="prefixIcon"
      class="button-icon"
      :size="iconSize"
    />

    <!-- Button text -->
    <span v-if="$slots.default || label" class="button-text">
      <slot>{{ label }}</slot>
    </span>

    <!-- Suffix icon -->
    <component
      :is="suffixIcon"
      v-if="suffixIcon && !loading"
      class="button-icon"
      :size="iconSize"
    />
  </component>
</template>

<script setup lang="ts">
  import { Loader2 } from 'lucide-vue-next'
  import { computed, useSlots } from 'vue'

  interface Props {
    label?: string
    variant?:
      | 'primary'
      | 'secondary'
      | 'success'
      | 'error'
      | 'warning'
      | 'ghost'
      | 'outline'
      | 'link'
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    loading?: boolean
    block?: boolean
    rounded?: boolean
    prefixIcon?: any
    suffixIcon?: any
    tag?: 'button' | 'a' | 'router-link'
    href?: string
    to?: string | object
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    block: false,
    rounded: false,
    tag: 'button',
  })

  const slots = useSlots()

  const emit = defineEmits<{
    click: [event: MouseEvent]
  }>()

  const buttonClasses = computed(() => ({
    [`button-${props.variant}`]: true,
    [`button-${props.size}`]: true,
    'button-block': props.block,
    'button-rounded': props.rounded,
    'button-loading': props.loading,
    'button-disabled': props.disabled,
    'button-icon-only':
      !props.label && !slots.default && (props.prefixIcon || props.suffixIcon),
  }))

  const iconSize = computed(() => {
    const sizes = {
      xs: 14,
      sm: 16,
      md: 18,
      lg: 20,
      xl: 22,
    }
    return sizes[props.size]
  })

  function handleClick (event: MouseEvent) {
    if (!props.disabled && !props.loading) {
      emit('click', event)
    }
  }
</script>

<style scoped>
.app-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-family: inherit;
  font-weight: 600;
  text-decoration: none;
  border: 2px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: all 150ms cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  user-select: none;
  position: relative;
  overflow: hidden;
}

.app-button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(68, 113, 196, 0.2);
}

.app-button:active:not(.button-disabled):not(.button-loading) {
  transform: scale(0.98);
}

/* Variants */
.button-primary {
  background: #4471c4;
  color: white;
  border-color: #4471c4;
}

.button-primary:hover:not(.button-disabled):not(.button-loading) {
  background: #365a9d;
  border-color: #365a9d;
  box-shadow: 0 4px 12px rgba(68, 113, 196, 0.3);
}

.button-secondary {
  background: white;
  color: #4471c4;
  border-color: #4471c4;
}

.button-secondary:hover:not(.button-disabled):not(.button-loading) {
  background: #eef2fb;
  border-color: #365a9d;
}

.button-success {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.button-success:hover:not(.button-disabled):not(.button-loading) {
  background: #059669;
  border-color: #059669;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.button-error {
  background: #ef4444;
  color: white;
  border-color: #ef4444;
}

.button-error:hover:not(.button-disabled):not(.button-loading) {
  background: #dc2626;
  border-color: #dc2626;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.button-warning {
  background: #f59e0b;
  color: white;
  border-color: #f59e0b;
}

.button-warning:hover:not(.button-disabled):not(.button-loading) {
  background: #d97706;
  border-color: #d97706;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.button-ghost {
  background: transparent;
  color: #374151;
  border-color: transparent;
}

.button-ghost:hover:not(.button-disabled):not(.button-loading) {
  background: #f3f4f6;
}

.button-outline {
  background: transparent;
  color: #374151;
  border-color: #d1d5db;
}

.button-outline:hover:not(.button-disabled):not(.button-loading) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.button-link {
  background: transparent;
  color: #4471c4;
  border-color: transparent;
  text-decoration: underline;
  text-underline-offset: 2px;
}

.button-link:hover:not(.button-disabled):not(.button-loading) {
  color: #365a9d;
}

/* Sizes */
.button-xs {
  padding: 6px 12px;
  font-size: 12px;
  min-height: 28px;
  gap: 4px;
}

.button-sm {
  padding: 8px 16px;
  font-size: 13px;
  min-height: 36px;
  gap: 6px;
}

.button-md {
  padding: 10px 20px;
  font-size: 14px;
  min-height: 44px;
  gap: 8px;
}

.button-lg {
  padding: 12px 24px;
  font-size: 16px;
  min-height: 52px;
  gap: 10px;
}

.button-xl {
  padding: 14px 28px;
  font-size: 18px;
  min-height: 60px;
  gap: 12px;
}

/* Icon only buttons */
.button-icon-only.button-xs {
  padding: 6px;
  min-width: 28px;
}

.button-icon-only.button-sm {
  padding: 8px;
  min-width: 36px;
}

.button-icon-only.button-md {
  padding: 10px;
  min-width: 44px;
}

.button-icon-only.button-lg {
  padding: 12px;
  min-width: 52px;
}

.button-icon-only.button-xl {
  padding: 14px;
  min-width: 60px;
}

/* Block */
.button-block {
  width: 100%;
}

/* Rounded */
.button-rounded {
  border-radius: 9999px;
}

/* Disabled */
.button-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

/* Loading */
.button-loading {
  cursor: wait;
  pointer-events: none;
}

/* Icon */
.button-icon {
  flex-shrink: 0;
}

/* Text */
.button-text {
  line-height: 1;
}

/* Animations */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Dark mode ready */
@media (prefers-color-scheme: dark) {
  .button-secondary {
    background: #1f2937;
    color: #93c5fd;
    border-color: #4b5563;
  }

  .button-secondary:hover:not(.button-disabled):not(.button-loading) {
    background: #374151;
  }

  .button-ghost {
    color: #e5e7eb;
  }

  .button-ghost:hover:not(.button-disabled):not(.button-loading) {
    background: #374151;
  }

  .button-outline {
    color: #e5e7eb;
    border-color: #4b5563;
  }

  .button-outline:hover:not(.button-disabled):not(.button-loading) {
    background: #1f2937;
    border-color: #6b7280;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .app-button {
    transition: none !important;
  }

  .app-button:active {
    transform: none !important;
  }

  .animate-spin {
    animation: none !important;
  }
}
</style>
