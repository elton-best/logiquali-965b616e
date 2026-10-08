/**
 * StatCard Component
 * Reusable card for displaying statistics
 */

<script setup lang="ts">
  import type { Component } from 'vue'
  import { computed } from 'vue'

  interface Props {
    title: string
    value: string | number
    icon: Component
    trend?: {
      value: number
      label: string
    }
    color?: 'primary' | 'accent' | 'yellow' | 'red' | 'blue' | 'neutral'
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    color: 'primary',
    loading: false,
  })

  const colorClasses = computed(() => {
    const colors = {
      primary: 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400',
      accent: 'bg-accent-100 dark:bg-accent-900/30 text-accent-600 dark:text-accent-400',
      yellow: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
      red: 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
      blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
      neutral: 'bg-neutral-100 dark:bg-neutral-900/30 text-neutral-600 dark:text-neutral-400',
    }
    return colors[props.color]
  })

  const trendColor = computed(() => {
    if (!props.trend) return ''
    return props.trend.value >= 0 ? 'text-green-600' : 'text-red-600'
  })
</script>

<template>
  <div class="card p-6 hover:shadow-lg transition-shadow">
    <div class="flex items-start justify-between mb-4">
      <div :class="`w-12 h-12 rounded-xl flex items-center justify-center ${colorClasses}`">
        <component :is="icon" class="w-6 h-6" />
      </div>

      <div v-if="trend" :class="`text-xs font-medium ${trendColor}`">
        {{ trend.value >= 0 ? '+' : '' }}{{ trend.value }}%
      </div>
    </div>

    <div>
      <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400 mb-1">
        {{ title }}
      </p>

      <div v-if="loading" class="flex items-center gap-2">
        <div class="h-8 w-24 bg-neutral-200 dark:bg-neutral-700 animate-pulse rounded" />
      </div>

      <p v-else class="text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        {{ value }}
      </p>

      <p v-if="trend" class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
        {{ trend.label }}
      </p>
    </div>
  </div>
</template>
