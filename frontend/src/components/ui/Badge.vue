<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    variant?: 'default' | 'success' | 'warning' | 'danger' | 'info' | 'primary'
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'md',
  })

  const badgeClasses = computed(() => {
    const base = 'badge'

    const variants = {
      default: 'badge-default',
      primary: 'badge-primary',
      success: 'badge-success',
      warning: 'badge-warning',
      danger: 'badge-danger',
      info: 'badge-primary',
    }

    const sizes = {
      sm: 'badge-sm',
      md: 'badge-md',
      lg: 'badge-lg',
    }

    return `${base} ${variants[props.variant]} ${sizes[props.size]}`
  })
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-1);
  padding: var(--spacing-1) var(--spacing-3);
  border-radius: var(--radius-full);
  font-weight: var(--font-weight-semibold);
  white-space: nowrap;
}

.badge-default {
  background: var(--color-gray-100);
  color: var(--color-gray-700);
}

.badge-primary {
  background: var(--color-primary-100);
  color: var(--color-primary-600);
}

.badge-success {
  background: var(--color-success-100);
  color: var(--color-success-600);
}

.badge-warning {
  background: var(--color-warning-100);
  color: var(--color-warning-600);
}

.badge-danger {
  background: var(--color-danger-100);
  color: var(--color-danger-600);
}

.badge-sm {
  padding: 2px var(--spacing-2);
  font-size: var(--font-size-xs);
}

.badge-md {
  font-size: var(--font-size-sm);
}

.badge-lg {
  padding: var(--spacing-2) var(--spacing-4);
  font-size: var(--font-size-base);
}
</style>
