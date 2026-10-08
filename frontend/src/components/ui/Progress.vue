<script setup lang="ts">
  import { ProgressIndicator, ProgressRoot } from 'radix-vue'
  import { computed } from 'vue'
  import { cn } from './utils'

  interface ProgressProps {
    modelValue?: number | null
    max?: number
    class?: string
    variant?: 'default' | 'success' | 'warning' | 'danger'
  }

  const props = withDefaults(defineProps<ProgressProps>(), {
    modelValue: null,
    max: 100,
    variant: 'default',
  })

  const progressClass = computed(() =>
    cn(
      'progress-bar',
      props.class,
    ),
  )

  const indicatorClass = computed(() => {
    return `progress-fill variant-${props.variant}`
  })

  const indicatorStyle = computed(() => ({
    transform: `translateX(-${100 - (props.modelValue || 0)}%)`,
  }))
</script>

<template>
  <ProgressRoot
    :class="progressClass"
    data-slot="progress"
    :max="max"
    :model-value="modelValue"
  >
    <ProgressIndicator
      :class="indicatorClass"
      data-slot="progress-indicator"
      :style="indicatorStyle"
    />
  </ProgressRoot>
</template>

<style scoped>
.progress-bar {
  position: relative;
  height: 8px;
  width: 100%;
  overflow: hidden;
  border-radius: var(--radius-base);
  background: var(--color-gray-200);
}

.progress-fill {
  height: 100%;
  width: 100%;
  flex: 1;
  transition: all var(--transition-slow);
  border-radius: var(--radius-base);
}

.variant-default {
  background: linear-gradient(90deg, var(--color-primary-500), var(--color-success-500));
}

.variant-success {
  background: var(--color-success-500);
}

.variant-warning {
  background: var(--color-warning-500);
}

.variant-danger {
  background: var(--color-danger-500);
}
</style>
