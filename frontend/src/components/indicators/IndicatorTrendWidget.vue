<template>
  <BaseCard>
    <div class="flex items-start justify-between mb-4">
      <div>
        <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ indicator.name }}</h4>
        <div class="flex items-baseline gap-2 mt-1">
          <span class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ formattedCurrentValue }}
          </span>
          <span class="text-sm text-gray-600">{{ indicator.unit }}</span>
        </div>
      </div>

      <IndicatorAlertBadge
        v-if="indicator.alert_level"
        :level="indicator.alert_level"
        size="sm"
      />
    </div>

    <div v-if="trendData" class="mb-3">
      <apexchart
        height="80"
        :options="sparklineOptions"
        :series="sparklineSeries"
        type="line"
      />
    </div>

    <div class="flex items-center justify-between text-sm">
      <div :class="['flex items-center gap-1', trendColorClass]">
        <svg v-if="trendDirection === 'up'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path clip-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" fill-rule="evenodd" />
        </svg>
        <svg v-else-if="trendDirection === 'down'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path clip-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" fill-rule="evenodd" />
        </svg>
        <span class="font-medium">{{ varianceText }}</span>
      </div>

      <span class="text-gray-600 dark:text-gray-400">
        vs {{ periodLabel }}
      </span>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { ApexOptions } from 'apexcharts'
  import type { Indicator, TrendData } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import IndicatorAlertBadge from './IndicatorAlertBadge.vue'

  interface Props {
    indicator: Indicator
    trendData?: TrendData
    periodLabel?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    periodLabel: 'période précédente',
  })

  const formattedCurrentValue = computed(() => {
    if (props.indicator.current_value === undefined || props.indicator.current_value === null) {
      return 'N/A'
    }
    return props.indicator.current_value.toFixed(1)
  })

  const trendDirection = computed(() => {
    if (props.trendData) {
      return props.trendData.direction
    }
    return props.indicator.trend || 'stable'
  })

  const trendColorClass = computed(() => {
    const isReversed = props.indicator.is_reversed

    if (trendDirection.value === 'up') {
      return isReversed ? 'text-red-600' : 'text-green-600'
    }
    if (trendDirection.value === 'down') {
      return isReversed ? 'text-green-600' : 'text-red-600'
    }
    return 'text-gray-600'
  })

  const varianceText = computed(() => {
    if (!props.trendData) {
      return trendDirection.value === 'stable' ? 'Stable' : 'Variation'
    }

    const variance = props.trendData.variance_percent
    const sign = variance >= 0 ? '+' : ''
    return `${sign}${variance.toFixed(1)}%`
  })

  const sparklineSeries = computed(() => {
    if (!props.trendData) return []

    return [{
      name: props.indicator.name,
      data: props.trendData.values,
    }]
  })

  const sparklineOptions = computed(() => ({
    chart: {
      type: 'line',
      sparkline: {
        enabled: true,
      },
    },
    stroke: {
      curve: 'smooth' as const,
      width: 2,
    },
    colors: [
      getIndicatorColor(props.indicator.alert_level),
    ],
    tooltip: {
      enabled: true,
      theme: 'light',
      x: {
        show: true,
        formatter: (val: number, opts: any) => {
          if (props.trendData && props.trendData.periods[opts.dataPointIndex]) {
            return props.trendData.periods[opts.dataPointIndex]
          }
          return String(val)
        },
      },
      y: {
        formatter: (val: number) => `${val.toFixed(2)} ${props.indicator.unit}`,
      },
    },
  }) as ApexOptions)

  function getIndicatorColor (level: string | undefined): string {
    if (level === 'green') return '#10B981'
    if (level === 'yellow') return '#F59E0B'
    if (level === 'red') return '#EF4444'
    return '#3B82F6'
  }
</script>
