<!-- AppSpinner.vue -->
<template>
  <div :aria-label="label" class="app-spinner" :class="`spinner-${size}`" role="status">
    <Loader2 class="spinner-icon" :size="iconSize" />
    <span v-if="label" class="spinner-label">{{ label }}</span>
  </div>
</template>

<script setup lang="ts">
  import { Loader2 } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    size?: 'sm' | 'md' | 'lg' | 'xl'
    label?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
  })

  const iconSize = computed(() => {
    const sizes = { sm: 16, md: 24, lg: 32, xl: 48 }
    return sizes[props.size]
  })
</script>

<style scoped>
.app-spinner {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.spinner-icon {
  color: #4471C4;
  animation: spin 1s linear infinite;
}

.spinner-label {
  font-size: 14px;
  color: #6B7280;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .spinner-icon { animation: none !important; }
}
</style>
