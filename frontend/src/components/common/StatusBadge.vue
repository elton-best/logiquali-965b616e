<template>
  <BaseBadge
    :dot="dot"
    :label="displayLabel"
    :size="size"
    :variant="variantColor"
  >
    <template v-if="$slots.icon" #icon>
      <slot name="icon" />
    </template>
  </BaseBadge>
</template>

<script setup lang="ts">
  import type {
    ActionStatus,
    AuditStatus,
    DocumentStatus,
    NCStatus,
  } from '@/types/shared'
  import { computed } from 'vue'
  import BaseBadge from './BaseBadge.vue'

  type Status = AuditStatus | NCStatus | ActionStatus | DocumentStatus | string
  type BadgeVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'gray'

  interface Props {
    status: Status
    module?: 'audit' | 'nc' | 'action' | 'document' | 'process'
    size?: 'xs' | 'sm' | 'md' | 'lg'
    dot?: boolean
    showLabel?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    module: 'audit',
    size: 'sm',
    dot: true,
    showLabel: true,
  })

  // Labels par module
  const statusLabels: Record<string, Record<string, string>> = {
    audit: {
      draft: 'Brouillon',
      planned: 'Planifié',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
    },
    nc: {
      nouveau: 'Nouveau',
      en_analyse: 'En analyse',
      action_en_cours: 'Action en cours',
      en_verification: 'En vérification',
      clos: 'Clos',
      rejete: 'Rejeté',
    },
    action: {
      pending: 'En attente',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
      overdue: 'En retard',
    },
    document: {
      draft: 'Brouillon',
      under_review: 'En révision',
      approved: 'Approuvé',
      obsolete: 'Obsolète',
      archived: 'Archivé',
    },
  }

  // Couleurs par statut et module
  const statusVariants: Record<string, Record<string, BadgeVariant>> = {
    audit: {
      draft: 'gray',
      planned: 'info',
      in_progress: 'warning',
      completed: 'success',
      cancelled: 'danger',
    },
    nc: {
      nouveau: 'info',
      en_analyse: 'warning',
      action_en_cours: 'purple',
      en_verification: 'info',
      clos: 'success',
      rejete: 'danger',
    },
    action: {
      pending: 'gray',
      in_progress: 'warning',
      completed: 'success',
      cancelled: 'danger',
      overdue: 'danger',
    },
    document: {
      draft: 'gray',
      under_review: 'warning',
      approved: 'success',
      obsolete: 'danger',
      archived: 'gray',
    },
  }

  const displayLabel = computed(() => {
    if (!props.showLabel) return ''
    return statusLabels[props.module]?.[props.status] || props.status
  })

  const variantColor = computed<BadgeVariant>(() => {
    return statusVariants[props.module]?.[props.status] || 'default'
  })
</script>
