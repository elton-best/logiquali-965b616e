<template>
  <AppWidget
    :clickable="true"
    :icon="getLucideIcon(icon)"
    :loading="false"
    :title="label"
    :value="value"
    :variant="mapColorToVariant(iconColor)"
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

  const props = defineProps<{
    label: string
    value: string
    trend: string
    trendIcon: string
    trendColor: string
    icon: string
    iconBg: string
    iconColor: string
    sparklineData?: number[]
  }>()

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
      grey: 'info',
      purple: 'audit',
      orange: 'risk',
      white: 'primary',
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
    }
    return iconMap[mdiIcon] || FileText
  }
</script>
