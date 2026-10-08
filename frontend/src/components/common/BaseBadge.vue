<template>
  <span
    :class="[
      'base-badge',
      sizeClass,
      variantClass
    ]"
  >
    <!-- Icon slot -->
    <span v-if="$slots.icon || icon" class="badge-icon">
      <slot name="icon">
        <component :is="icon" v-if="icon" class="icon-component" />
      </slot>
    </span>

    <!-- Label -->
    <span>{{ label }}</span>

    <!-- Dot indicator -->
    <span
      v-if="dot"
      :class="['badge-dot', dotClass]"
    />
  </span>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    label: string | number
    variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
    icon?: any
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    size: 'sm',
    dot: false,
  })

  const sizeClass = computed(() => {
    return `size-${props.size}`
  })

  const variantClass = computed(() => {
    return `variant-${props.variant}`
  })

  const dotClass = computed(() => {
    return `dot-${props.variant}`
  })
</script>

<style scoped>
.base-badge {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-1);
  border-radius: var(--radius-full);
  font-weight: var(--font-weight-semibold);
  white-space: nowrap;
}

/* Sizes */
.size-xs {
  padding: 2px var(--spacing-2);
  font-size: 10px;
}

.size-sm {
  padding: var(--spacing-1) var(--spacing-3);
  font-size: var(--font-size-xs);
}

.size-md {
  padding: var(--spacing-2) var(--spacing-4);
  font-size: var(--font-size-sm);
}

.size-lg {
  padding: var(--spacing-2) var(--spacing-5);
  font-size: var(--font-size-base);
}

/* Variants */
.variant-default {
  background: var(--color-gray-100);
  color: var(--color-gray-700);
}

.variant-primary {
  background: var(--color-primary-100);
  color: var(--color-primary-600);
}

.variant-success {
  background: var(--color-success-100);
  color: var(--color-success-600);
}

.variant-warning {
  background: var(--color-warning-100);
  color: var(--color-warning-600);
}

.variant-danger {
  background: var(--color-danger-100);
  color: var(--color-danger-600);
}

.variant-info {
  background: var(--color-primary-100);
  color: var(--color-primary-600);
}

.variant-purple {
  background: #ede9fe;
  color: #7c3aed;
}

.variant-gray {
  background: var(--color-gray-100);
  color: var(--color-gray-600);
}

/* Icon */
.badge-icon {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.icon-component {
  width: 12px;
  height: 12px;
}

/* Dot */
.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
}

.dot-default {
  background: var(--color-gray-500);
}

.dot-primary {
  background: var(--color-primary-500);
}

.dot-success {
  background: var(--color-success-500);
}

.dot-warning {
  background: var(--color-warning-500);
}

.dot-danger {
  background: var(--color-danger-500);
}

.dot-info {
  background: var(--color-primary-500);
}

.dot-purple {
  background: #7c3aed;
}

.dot-gray {
  background: var(--color-gray-400);
}
</style>
