<template>
  <AppWidget
    :clickable="true"
    :icon="getLucideIcon(icon)"
    :loading="false"
    :title="label"
    :trend="trend"
    :value="formattedValue"
    :variant="mapColorToVariant(iconColor)"
    v-bind="$attrs"
    @click="$emit('click')"
  >
    <template v-if="subtitle" #subtitle>
      <div class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">
        {{ subtitle }}
      </div>
    </template>
  </AppWidget>
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
  import { computed } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'

  interface Props {
    label: string
    value: number | string
    icon: string
    iconColor?: string
    iconTextColor?: string
    iconSize?: number
    valueClass?: string
    cardClass?: string
    subtitle?: string
    trend?: number
    format?: 'number' | 'currency' | 'percentage' | 'none'
  }

  const props = withDefaults(defineProps<Props>(), {
    iconColor: 'primary',
    iconTextColor: 'white',
    iconSize: 48,
    valueClass: 'text-h5 font-weight-bold',
    cardClass: '',
    format: 'number',
  })

  defineEmits<{
    click: []
  }>()

  const formattedValue = computed(() => {
    if (typeof props.value === 'string') return props.value

    switch (props.format) {
      case 'currency': {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(props.value)
      }
      case 'percentage': {
        return `${props.value}%`
      }
      case 'number': {
        return new Intl.NumberFormat('fr-FR').format(props.value)
      }
      default: {
        return props.value
      }
    }
  })

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
