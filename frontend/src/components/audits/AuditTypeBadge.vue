<template>
  <BaseBadge
    :dot="dot"
    :label="label"
    :size="size"
    :variant="variantColor"
  />
</template>

<script setup lang="ts">
  import type { AuditType } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'

  type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'

  interface Props {
    type: AuditType
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 'sm',
    dot: false,
  })

  const labels: Record<AuditType, string> = {
    internal_process: 'Processus',
    internal_system: 'Système',
    internal_product: 'Produit',
    internal_thematic: 'Thématique',
    supplier: 'Fournisseur',
    external_certification: 'Certification',
    external_surveillance: 'Surveillance',
    external_supplier: 'Fournisseur',
    thematic: 'Thématique',
  }

  const label = computed(() => labels[props.type])

  const variantColor = computed(() => {
    const variants: Record<AuditType, BadgeVariant> = {
      internal_process: 'primary',
      internal_system: 'info',
      internal_product: 'purple',
      internal_thematic: 'warning',
      supplier: 'warning',
      external_supplier: 'warning',
      external_certification: 'success',
      external_surveillance: 'info',
      thematic: 'gray',
    }
    return variants[props.type]
  })
</script>
