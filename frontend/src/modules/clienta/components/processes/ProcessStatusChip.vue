<template>
  <AppBadge
    :icon="lucideIcon"
    :label="label"
    :variant="badgeVariant"
  />
</template>

<script setup lang="ts">
  import { AlertCircle, CheckCircle, Clock, FileEdit, Play } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'
  import processWorkflowService from '@/services/processWorkflowService'

  interface Props {
    status: string
    size?: 'x-small' | 'small' | 'default' | 'large' | 'x-large'
    variant?: 'flat' | 'text' | 'elevated' | 'tonal' | 'outlined' | 'plain'
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'small',
    variant: 'tonal',
  })

  const label = computed(() => processWorkflowService.getStatusLabel(props.status))
  const color = computed(() => processWorkflowService.getStatusColor(props.status))

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
    return colorMap[color.value] || 'info'
  })

  const lucideIcon = computed(() => {
    const statusLower = props.status.toLowerCase()
    if (statusLower.includes('draft') || statusLower.includes('brouillon')) return FileEdit
    if (statusLower.includes('progress') || statusLower.includes('cours')) return Play
    if (statusLower.includes('review') || statusLower.includes('revision')) return Clock
    if (statusLower.includes('valid') || statusLower.includes('approved')) return CheckCircle
    if (statusLower.includes('rejected') || statusLower.includes('error')) return AlertCircle
    return CheckCircle
  })
</script>
