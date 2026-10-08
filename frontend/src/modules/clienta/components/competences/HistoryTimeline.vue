<template>
  <v-timeline class="history-timeline" density="compact" side="end">
    <v-timeline-item
      v-for="item in history"
      :key="item.id"
      :dot-color="getActionColor(item.action)"
      size="small"
    >
      <template #icon>
        <v-icon size="x-small">{{ getActionIcon(item.action) }}</v-icon>
      </template>

      <div class="timeline-content">
        <div class="d-flex align-center justify-space-between mb-1">
          <span class="text-subtitle-2 font-weight-medium">
            {{ getActionLabel(item.action) }}
          </span>
          <span class="text-caption text-grey">
            {{ formatDate(item.date) }}
          </span>
        </div>

        <div class="text-body-2 text-grey-darken-1">
          Par {{ item.userName }}
        </div>

        <div v-if="item.comment" class="text-body-2 mt-1">
          {{ item.comment }}
        </div>

        <div v-if="item.action === 'rescheduled' && item.previousDate && item.newDate" class="mt-2">
          <v-chip class="me-1" color="warning" size="x-small" variant="outlined">
            {{ formatDate(item.previousDate) }}
          </v-chip>
          <v-icon size="x-small">mdi-arrow-right</v-icon>
          <v-chip class="ms-1" color="success" size="x-small" variant="outlined">
            {{ formatDate(item.newDate) }}
          </v-chip>
        </div>
      </div>
    </v-timeline-item>
  </v-timeline>
</template>

<script setup lang="ts">
  import type { FormationHistory } from '../../types/formation.types'

  interface Props {
    history: FormationHistory[]
  }

  defineProps<Props>()

  function getActionColor (action: string) {
    const colors: Record<string, string> = {
      created: 'primary',
      updated: 'info',
      completed: 'success',
      rescheduled: 'warning',
      cancelled: 'error',
    }
    return colors[action] || 'grey'
  }

  function getActionIcon (action: string) {
    const icons: Record<string, string> = {
      created: 'mdi-plus',
      updated: 'mdi-pencil',
      completed: 'mdi-check',
      rescheduled: 'mdi-calendar-refresh',
      cancelled: 'mdi-cancel',
    }
    return icons[action] || 'mdi-circle'
  }

  function getActionLabel (action: string) {
    const labels: Record<string, string> = {
      created: 'Formation créée',
      updated: 'Formation modifiée',
      completed: 'Formation réalisée',
      rescheduled: 'Formation replanifiée',
      cancelled: 'Formation annulée',
    }
    return labels[action] || action
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }
</script>

<style scoped>
.history-timeline {
  padding: 16px 0;
}

.timeline-content {
  padding: 8px 0;
}
</style>
