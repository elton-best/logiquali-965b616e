<template>
  <div
    :class="[
      'base-card',
      variantClass,
      paddingClass,
      hoverClass,
      clickable ? 'clickable' : ''
    ]"
    @click="handleClick"
  >
    <!-- Header slot -->
    <div v-if="$slots.header" class="card-header">
      <slot name="header" />
    </div>

    <!-- Default content -->
    <slot />

    <!-- Footer slot -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    variant?: 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info'
    padding?: 'none' | 'sm' | 'md' | 'lg'
    hoverable?: boolean
    clickable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    padding: 'md',
    hoverable: false,
    clickable: false,
  })

  const emit = defineEmits<{
    click: []
  }>()

  const variantClass = computed(() => {
    return `variant-${props.variant}`
  })

  const paddingClass = computed(() => {
    return `padding-${props.padding}`
  })

  const hoverClass = computed(() => {
    if (!props.hoverable && !props.clickable) return ''
    return 'hoverable'
  })

  function handleClick () {
    if (props.clickable) {
      emit('click')
    }
  }
</script>

<style scoped>
.base-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
}

.variant-default {
  background: var(--bg-primary);
  border-color: var(--border-color);
}

.variant-primary {
  background: var(--color-primary-50);
  border-color: var(--color-primary-200);
}

.variant-success {
  background: var(--color-success-50);
  border-color: var(--color-success-200);
}

.variant-warning {
  background: var(--color-warning-50);
  border-color: var(--color-warning-200);
}

.variant-danger {
  background: var(--color-danger-50);
  border-color: var(--color-danger-200);
}

.variant-info {
  background: var(--color-primary-50);
  border-color: var(--color-primary-200);
}

.padding-none {
  padding: 0;
}

.padding-sm {
  padding: var(--spacing-3);
}

.padding-md {
  padding: var(--spacing-4);
}

.padding-lg {
  padding: var(--spacing-6);
}

.hoverable {
  cursor: pointer;
}

.hoverable:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.clickable {
  cursor: pointer;
}

.card-header {
  padding-bottom: var(--spacing-4);
  margin-bottom: var(--spacing-4);
  border-bottom: 1px solid var(--border-color);
}

.card-footer {
  padding-top: var(--spacing-4);
  margin-top: var(--spacing-4);
  border-top: 1px solid var(--border-color);
}
</style>
