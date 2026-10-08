<template>
  <AppBadge
    :icon="lucideIcon"
    :label="chipLabel"
    :variant="badgeVariant"
    v-bind="$attrs"
  />
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, Clock, Info, XCircle } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'
  import { getSeverityColor, getSeverityLabel, getStatusColor, getStatusLabel } from '@/modules/clienta/utils/statusMappers'

  interface Props {
    status?: string
    severity?: string
    customLabel?: string
    variant?: 'flat' | 'tonal' | 'outlined' | 'text' | 'elevated' | 'plain'
    size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
    prependIcon?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    variant: 'tonal',
    size: 'small',
  })

  const chipColor = computed(() => {
    if (props.status) return getStatusColor(props.status)
    if (props.severity) return getSeverityColor(props.severity)
    return 'grey'
  })

  const chipLabel = computed(() => {
    if (props.customLabel) return props.customLabel
    if (props.status) return getStatusLabel(props.status)
    if (props.severity) return getSeverityLabel(props.severity)
    return ''
  })

  const badgeVariant = computed(() => {
    const colorMap: Record<string, 'primary' | 'success' | 'warning' | 'error' | 'info' | 'audit' | 'risk'> = {
      primary: 'primary',
      success: 'success',
      warning: 'warning',
      error: 'error',
      info: 'info',
      grey: 'info',
      purple: 'audit',
      orange: 'risk',
    }
    return colorMap[chipColor.value] || 'info'
  })

  const lucideIcon = computed(() => {
    const statusLower = (props.status || '').toLowerCase()
    if (statusLower.includes('success') || statusLower.includes('completed') || statusLower.includes('valid')) return CheckCircle
    if (statusLower.includes('error') || statusLower.includes('failed') || statusLower.includes('critical')) return XCircle
    if (statusLower.includes('warning') || statusLower.includes('pending')) return AlertCircle
    if (statusLower.includes('progress') || statusLower.includes('processing')) return Clock
    return Info
  })
</script>
