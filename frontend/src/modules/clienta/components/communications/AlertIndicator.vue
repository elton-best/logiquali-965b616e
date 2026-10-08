<template>
  <v-tooltip location="top">
    <template #activator="{ props: tooltipProps }">
      <v-badge
        v-if="alertCount > 0"
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
  }

  const props = defineProps<Props>()

  const daysUntil = computed(() => {
    if (!props.dateDebut) return null
    const today = new Date()
    const startDate = new Date(props.dateDebut)
    if (Number.isNaN(startDate.getTime())) return null
    const diff = Math.ceil((startDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))
    return diff
  })

  const alertCount = computed(() => {
    if (daysUntil.value === null) return 0
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
    const days = daysUntil.value
    if (days <= 1) return 'error'
    if (days <= 3) return 'warning'
    if (days <= 7) return 'orange'
    return 'info'
  })

  const alertText = computed(() => {
    if (daysUntil.value === null) return 'Période à compléter'
    const days = daysUntil.value
    if (days < 0) return 'Formation en retard'
    if (days === 0) return 'Formation aujourd\'hui'
    if (days === 1) return 'Formation demain'
    return `Formation dans ${days} jours`
  })
</script>
