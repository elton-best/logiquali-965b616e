<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { ActionPriority } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'

  interface Props {
    priority: ActionPriority
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<ActionPriority, string> = {
    low: 'Faible',
    medium: 'Moyenne',
    high: 'Haute',
    critical: 'Critique',
  }

  const label = computed(() => labels[props.priority])

  const variantColor = computed(() => {
    const variants: Record<ActionPriority, BadgeVariant> = {
      low: 'gray',
      medium: 'info',
      high: 'warning',
      critical: 'danger',
    }
    return variants[props.priority]
  })
</script>
