<template>
  <BaseCard :class="['indicator-card', alertBorderClass]" hoverable>
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ indicator.name }}
          </h3>
          <IndicatorTypeBadge size="sm" :type="indicator.type" />
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ indicator.code }}</p>
      </div>

      <IndicatorAlertBadge
        v-if="indicator.alert_level"
        :level="indicator.alert_level"
      />
    </div>

    <div class="mb-4">
      <div class="flex items-baseline gap-2">
        <span class="text-3xl font-bold text-gray-900 dark:text-white">
          {{ formattedValue }}
        </span>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ indicator.unit }}</span>

        <div v-if="indicator.trend" :class="['ml-2 flex items-center text-sm', trendColorClass]">
          <svg v-if="indicator.trend === 'up'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" fill-rule="evenodd" />
          </svg>
          <svg v-else-if="indicator.trend === 'down'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" fill-rule="evenodd" />
          </svg>
          <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path clip-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" fill-rule="evenodd" />
          </svg>
        </div>
      </div>

      <div v-if="indicator.target_value !== undefined" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Cible : {{ indicator.target_value }} {{ indicator.unit }}
      </div>
    </div>

    <div v-if="indicator.target_value && indicator.current_value !== undefined" class="mb-3">
      <IndicatorProgressBar
        :alert-level="indicator.alert_level"
        :current="indicator.current_value"
        :target="indicator.target_value"
      />
    </div>

    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-200 dark:border-gray-700">
      <span v-if="indicator.responsible">
        {{ indicator.responsible.name }}
      </span>
      <span v-if="indicator.last_measured_at">
        Mesuré {{ formatDate(indicator.last_measured_at) }}
      </span>
      <span v-else>
        Jamais mesuré
      </span>
    </div>

    <div v-if="showActions" class="flex gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
      <button
        class="flex-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        @click="$emit('view')"
      >
        Voir détails
      </button>
      <button
        class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
        @click="$emit('record-value')"
      >
        Saisir valeur
      </button>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { Indicator } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import IndicatorAlertBadge from './IndicatorAlertBadge.vue'
  import IndicatorProgressBar from './IndicatorProgressBar.vue'
  import IndicatorTypeBadge from './IndicatorTypeBadge.vue'

  interface Props {
    indicator: Indicator
    showActions?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    showActions: true,
  })

  defineEmits<{
    'view': []
    'record-value': []
    'edit': []
  }>()

  const formattedValue = computed(() => {
    if (props.indicator.current_value === undefined || props.indicator.current_value === null) {
      return 'N/A'
    }
    return props.indicator.current_value.toFixed(2)
  })

  const alertBorderClass = computed(() => {
    const level = props.indicator.alert_level
    if (!level) return ''

    const borders = {
      green: 'border-l-4 border-l-green-500',
      yellow: 'border-l-4 border-l-yellow-500',
      red: 'border-l-4 border-l-red-500',
    }
    return borders[level]
  })

  const trendColorClass = computed(() => {
    const trend = props.indicator.trend
    const isReversed = props.indicator.is_reversed

    if (trend === 'up') return isReversed ? 'text-red-600' : 'text-green-600'
    if (trend === 'down') return isReversed ? 'text-green-600' : 'text-red-600'
    return 'text-gray-600'
  })

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
    })
  }
</script>
