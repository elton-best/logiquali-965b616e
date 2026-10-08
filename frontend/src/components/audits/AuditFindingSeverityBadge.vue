<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { FindingSeverity } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'

  interface Props {
    severity: FindingSeverity
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<FindingSeverity, string> = {
    majeur: 'Majeur',
    mineur: 'Mineur',
    observation: 'Observation',
    opportunite: 'Opportunité',
  }

  const label = computed(() => labels[props.severity])

  const variantColor = computed(() => {
    const variants: Record<FindingSeverity, BadgeVariant> = {
      majeur: 'danger',
      mineur: 'warning',
      observation: 'info',
      opportunite: 'success',
    }
    return variants[props.severity]
  })
</script>
