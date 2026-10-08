<template>
  <AppWidget
    :clickable="true"
    :icon="getLucideIcon(icon)"
    :loading="false"
    :title="label"
    :trend="parseTrend(trend)"
    :value="value"
    :variant="mapColorToVariant(color)"
    v-bind="$attrs"
    @click="$emit('click')"
  />
</template>

<script setup lang="ts">
  import {
    AlertCircle,
    AlertTriangle,
    Building,
    Calendar,
    CheckCircle,
    Clock,
    FileText,
    Shield,
    TrendingUp,
    Users,
  } from 'lucide-vue-next'
  import AppWidget from '@/components/common/AppWidget.vue'

  interface Props {
    label: string
    value: string | number
    icon: string
    color?: 'primary' | 'success' | 'warning' | 'error' | 'info' | 'purple' | 'indigo'
    trend?: string
    trendIcon?: string
    trendColor?: string
    progress?: number
    elevated?: boolean
  }

  withDefaults(defineProps<Props>(), {
    color: 'primary',
    elevated: false,
  })

  defineEmits<{
    click: []
  }>()

  function mapColorToVariant (color?: string): 'primary' | 'success' | 'warning' | 'error' | 'info' | 'audit' | 'risk' {
    const mapping: Record<string, 'primary' | 'success' | 'warning' | 'error' | 'info' | 'audit' | 'risk'> = {
      primary: 'primary',
      success: 'success',
      warning: 'warning',
      error: 'error',
      info: 'info',
      purple: 'audit',
      indigo: 'primary',
    }
    return mapping[color || 'primary'] || 'primary'
  }

  function getLucideIcon (mdiIcon: string) {
    const iconMap: Record<string, any> = {
      'mdi-shield': Shield,
      'mdi-alert': AlertTriangle,
      'mdi-alert-outline': AlertCircle,
      'mdi-alert-circle': AlertCircle,
      'mdi-trending-up': TrendingUp,
      'mdi-check-circle': CheckCircle,
      'mdi-file-alert': FileText,
      'mdi-progress-clock': Clock,
      'mdi-clock-outline': Clock,
      'mdi-checkbox-marked-circle': CheckCircle,
      'mdi-play-circle': TrendingUp,
      'mdi-building': Building,
      'mdi-account-group': Users,
      'mdi-calendar': Calendar,
      'mdi-map-marker': Building,
      'mdi-factory': Building,
    }
    return iconMap[mdiIcon] || FileText
  }

  function parseTrend (trend?: string): number | undefined {
    if (!trend) return undefined
    const match = trend.match(/([+-]?\d+)/)
    return match ? Number.parseInt(match[1]) : undefined
  }
</script>
