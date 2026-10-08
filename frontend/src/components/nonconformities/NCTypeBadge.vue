<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { NCType } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  interface Props {
    type: NCType
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<NCType, string> = {
    audit_interne: 'Audit interne',
    audit_externe: 'Audit externe',
    reclamation_client: 'Réclamation client',
    incident: 'Incident',
    non_conformite_produit: 'NC Produit',
    autre: 'Autre',
  }

  const label = computed(() => labels[props.type])

  const variantColor = computed(() => {
    const variants: Record<NCType, 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'> = {
      audit_interne: 'primary',
      audit_externe: 'purple',
      reclamation_client: 'danger',
      incident: 'warning',
      non_conformite_produit: 'info',
      autre: 'gray',
    }
    return variants[props.type]
  })
</script>
