<template>
  <div :class="cardClasses">
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">
        <h3 class="card-title">{{ title }}</h3>
        <p v-if="subtitle" class="card-subtitle">{{ subtitle }}</p>
      </slot>
    </div>

    <div class="card-body">
      <slot />
    </div>

    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    title?: string
    subtitle?: string
    variant?: 'default' | 'bordered' | 'elevated'
    padding?: 'none' | 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    padding: 'md',
  })

  const cardClasses = computed(() => {
    const base = 'card'

    const variants = {
      default: '',
      bordered: 'card-bordered',
      elevated: 'card-elevated',
    }

    const paddings = {
      none: 'p-0',
      sm: 'p-4',
      md: 'p-6',
      lg: 'p-8',
    }

    return `${base} ${variants[props.variant]} ${paddings[props.padding]}`
  })
</script>

<style scoped>
.card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-base);
  transition: all var(--transition-base);
  border: 1px solid var(--border-color);
}

.card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.card-bordered {
  border: 2px solid var(--border-color);
}

.card-elevated {
  box-shadow: var(--shadow-lg);
}

.card-header {
  margin-bottom: var(--spacing-4);
  padding-bottom: var(--spacing-4);
  border-bottom: 1px solid var(--border-color);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.card-subtitle {
  margin-top: var(--spacing-1);
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.card-body {
  flex: 1;
}

.card-footer {
  margin-top: var(--spacing-6);
  padding-top: var(--spacing-4);
  border-top: 1px solid var(--border-color);
}
</style>
