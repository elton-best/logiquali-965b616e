<template>
  <BaseBadge :label="labelMap[type]" :size="size" :variant="variantMap[type]">
    <span class="flex items-center gap-1">
      <component :is="iconComponent" class="w-3.5 h-3.5" />
    </span>
  </BaseBadge>
</template>

<script setup lang="ts">
  import type { IndicatorType } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  interface Props {
    type: IndicatorType
    size?: 'sm' | 'md' | 'lg'
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'md',
  })

  const labelMap: Record<IndicatorType, string> = {
    kpi: 'KPI',
    objective: 'Objectif',
    target: 'Cible',
    metric: 'Métrique',
  }

  const variantMap: Record<IndicatorType, 'primary' | 'success' | 'warning' | 'info'> = {
    kpi: 'primary',
    objective: 'success',
    target: 'warning',
    metric: 'info',
  }

  const iconComponent = computed(() => {
    const icons: Record<IndicatorType, string> = {
      kpi: 'ChartBarIcon',
      objective: 'TargetIcon',
      target: 'FlagIcon',
      metric: 'ScaleIcon',
    }
    return icons[props.type]
  })
</script>
