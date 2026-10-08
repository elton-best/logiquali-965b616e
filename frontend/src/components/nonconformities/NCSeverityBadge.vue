<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { NCSeverity } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  interface Props {
    severity: NCSeverity
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<NCSeverity, string> = {
    majeur: 'Majeure',
    mineur: 'Mineure',
    observation: 'Observation',
  }

  const label = computed(() => labels[props.severity])

  const variantColor = computed(() => {
    const variants: Record<NCSeverity, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'> = {
      majeur: 'danger',
      mineur: 'warning',
      observation: 'info',
    }
    return variants[props.severity]
  })
</script>
