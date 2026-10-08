<template>
  <v-tooltip location="top">
    <template #activator="{ props: tooltipProps }">
      <v-badge
        v-if="showBadge"
        :color="alertColor"
        :content="alertCount"
        overlap
        v-bind="tooltipProps"
      >
        <v-icon :color="alertColor" size="small">
          mdi-bell-ring
        </v-icon>
      </v-badge>
    </template>
    <span>{{ alertText }}</span>
  </v-tooltip>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface Props {
    dateDebut?: string | null
    status?: 'planifiee' | 'en_attente' | 'realisee' | 'replanifiee' | 'annulee'
    alertState?: 'active' | 'resolved' | 'expired'
  }

  const props = defineProps<Props>()

  const effectiveAlertState = computed(() => {
    if (props.alertState) {
      return props.alertState
    }

    if (props.status === 'realisee' || props.status === 'annulee') {
      return 'resolved'
    }

    if (props.status === 'en_attente') {
      return 'expired'
    }

    return 'active'
  })

  const daysUntil = computed(() => {
    if (!props.dateDebut) return null
    const today = new Date()
    const startDate = new Date(props.dateDebut)
    if (Number.isNaN(startDate.getTime())) return null
    const diff = Math.ceil((startDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
    return diff
  })

  const showBadge = computed(() => effectiveAlertState.value !== 'resolved' && alertCount.value > 0)

  const alertCount = computed(() => {
    if (daysUntil.value === null) return 0
    if (effectiveAlertState.value === 'resolved') return 0
    if (effectiveAlertState.value === 'expired') return 1

    const days = daysUntil.value
    if (days <= 1) return 1
    if (days <= 3) return 2
    if (days <= 7) return 3
    if (days <= 15) return 4
    if (days <= 30) return 5
    return 0
  })

  const alertColor = computed(() => {
    if (daysUntil.value === null) return 'info'
    if (effectiveAlertState.value === 'expired') return 'error'

    const days = daysUntil.value
    if (days <= 1) return 'error'
    if (days <= 3) return 'warning'
    if (days <= 7) return 'orange'
    return 'info'
  })

  const alertText = computed(() => {
    if (daysUntil.value === null) return 'Période à compléter'
    if (effectiveAlertState.value === 'resolved') return 'Alerte résolue'
    if (effectiveAlertState.value === 'expired') return 'Formation en retard (suivi requis)'

    const days = daysUntil.value
    if (days < 0) return 'Formation en retard'
    if (days === 0) return 'Formation aujourd\'hui'
    if (days === 1) return 'Formation demain'
    return `Formation dans ${days} jours`
  })
</script>
