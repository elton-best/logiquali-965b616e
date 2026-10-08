<template>
  <AppBadge
    :icon="statusConfig.icon"
    :label="statusConfig.label"
    :variant="statusConfig.variant"
  />
</template>

<script setup lang="ts">
  import type { FormationStatus } from '../../types/formation.types'
  import { Calendar, CalendarClock, CheckCircle, Clock, XCircle } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppBadge from '@/components/common/AppBadge.vue'

  interface Props {
    status: FormationStatus
    isIncomplete?: boolean
  }

  type BadgeVariant = 'primary' | 'success' | 'warning' | 'error' | 'info' | 'audit' | 'risk' | 'neutral'

  const props = defineProps<Props>()

  const statusConfig = computed<{ variant: BadgeVariant, icon: any, label: string }>(() => {
    if (props.isIncomplete) {
      return {
        variant: 'warning' as const,
        icon: Clock,
        label: 'À compléter',
      }
    }
    const configs: Record<FormationStatus, { variant: BadgeVariant, icon: any, label: string }> = {
      planifiee: {
        variant: 'info',
        icon: Calendar,
        label: 'Planifiée',
      },
      en_attente: {
        variant: 'warning',
        icon: Clock,
        label: 'En attente',
      },
      realisee: {
        variant: 'success',
        icon: CheckCircle,
        label: 'Réalisée',
      },
      replanifiee: {
        variant: 'info',
        icon: CalendarClock,
        label: 'Replanifiée',
      },
      annulee: {
        variant: 'error',
        icon: XCircle,
        label: 'Annulée',
      },
    }

    return configs[props.status]
  })
</script>
