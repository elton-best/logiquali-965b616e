<template>
  <div class="indicator-progress-bar">
    <div class="flex items-center justify-between text-xs mb-1">
      <span class="text-gray-600 dark:text-gray-400">Progression</span>
      <span :class="['font-medium', percentageColorClass]">{{ achievementRate }}%</span>
    </div>

    <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      <div
        :class="['h-full transition-all duration-500', barColorClass]"
        :style="{ width: `${Math.min(achievementRate, 100)}%` }"
      />
    </div>

    <div v-if="showThresholds && thresholds.length > 0" class="relative mt-1">
      <div
        v-for="threshold in thresholds"
        :key="threshold.label"
        class="absolute top-0 transform -translate-x-1/2"
        :style="{ left: `${threshold.position}%` }"
      >
        <div class="w-px h-2 bg-gray-400" />
        <span class="text-xs text-gray-500 whitespace-nowrap">{{ threshold.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { IndicatorAlertLevel } from '@/types/indicator'
  import { computed } from 'vue'

  interface Props {
    current: number
    target: number
    alertLevel?: IndicatorAlertLevel
    showThresholds?: boolean
    minAcceptable?: number
    maxAcceptable?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    showThresholds: false,
  })

  const achievementRate = computed(() => {
    if (props.target === 0) return 0
    return Math.round((props.current / props.target) * 100)
  })

  const barColorClass = computed(() => {
    if (props.alertLevel) {
      const colors = {
        green: 'bg-green-500',
        yellow: 'bg-yellow-500',
        red: 'bg-red-500',
      }
      return colors[props.alertLevel]
    }

    // Default color based on achievement rate
    const rate = achievementRate.value
    if (rate >= 100) return 'bg-green-500'
    if (rate >= 75) return 'bg-yellow-500'
    return 'bg-red-500'
  })

  const percentageColorClass = computed(() => {
    if (props.alertLevel) {
      const colors = {
        green: 'text-green-600',
        yellow: 'text-yellow-600',
        red: 'text-red-600',
      }
      return colors[props.alertLevel]
    }

    const rate = achievementRate.value
    if (rate >= 100) return 'text-green-600'
    if (rate >= 75) return 'text-yellow-600'
    return 'text-red-600'
  })

  const thresholds = computed(() => {
    const result: Array<{ label: string, position: number }> = []

    if (props.minAcceptable !== undefined && props.target > 0) {
      result.push({
        label: 'Min',
        position: (props.minAcceptable / props.target) * 100,
      })
    }

    if (props.maxAcceptable !== undefined && props.target > 0) {
      result.push({
        label: 'Max',
        position: (props.maxAcceptable / props.target) * 100,
      })
    }

    return result
  })
</script>
