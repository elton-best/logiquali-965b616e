<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { ActionType } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'

  interface Props {
    type: ActionType
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<ActionType, string> = {
    corrective: 'Corrective',
    preventive: 'Préventive',
    improvement: 'Amélioration',
    routine: 'Routine',
  }

  const label = computed(() => labels[props.type])

  const variantColor = computed(() => {
    const variants: Record<ActionType, BadgeVariant> = {
      corrective: 'danger',
      preventive: 'warning',
      improvement: 'success',
      routine: 'info',
    }
    return variants[props.type]
  })
</script>
