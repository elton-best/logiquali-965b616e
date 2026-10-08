<template>
  <BaseBadge :label="labelMap[level]" :size="size" :variant="variantMap[level]">
    <span class="flex items-center gap-1">
      <div :class="['w-2 h-2 rounded-full', dotColorMap[level]]" />
    </span>
  </BaseBadge>
</template>

<script setup lang="ts">
  import type { IndicatorAlertLevel } from '@/types/indicator'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  interface Props {
    level: IndicatorAlertLevel
    size?: 'sm' | 'md' | 'lg'
  }

  withDefaults(defineProps<Props>(), {
    size: 'md',
  })

  const labelMap: Record<IndicatorAlertLevel, string> = {
    green: 'Conforme',
    yellow: 'Attention',
    red: 'Critique',
  }

  const variantMap: Record<IndicatorAlertLevel, 'success' | 'warning' | 'danger'> = {
    green: 'success',
    yellow: 'warning',
    red: 'danger',
  }

  const dotColorMap: Record<IndicatorAlertLevel, string> = {
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
  }
</script>
