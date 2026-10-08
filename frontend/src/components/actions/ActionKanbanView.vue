<template>
  <v-row>
    <v-col v-for="column in columns" :key="column.value" cols="12" md="3">
      <v-card elevation="1">
        <v-card-title class="py-3 px-4 bg-grey-lighten-4">
          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-2" :color="column.color">{{ column.icon }}</v-icon>
              <span class="font-weight-bold">{{ column.title }}</span>
            </div>
            <v-chip :color="column.color" size="small">
              {{ getColumnCount(column.value) }}
            </v-chip>
          </div>
        </v-card-title>
        <div class="pa-2" style="min-height: 600px;">
          <draggable
            :group="{ name: 'actions' }"
            item-key="id"
            :list="getActionsForStatus(column.value)"
            @change="handleDrop($event, column.value)"
          >
            <template #item="{ element }">
              <v-card class="mb-3 cursor-pointer" elevation="2" hover @click="$emit('view-action', element.id)">
                <v-card-text class="pa-3">
                  <v-chip class="mb-2" color="primary" size="x-small" variant="outlined">{{ element.ref }}</v-chip>
                  <h4 class="text-body-2 font-weight-medium mb-2">{{ element.title }}</h4>
                  <v-chip :color="getPriorityColor(element.priority)" size="x-small">{{ element.priority }}</v-chip>
                  <div class="mt-2">
                    <v-progress-linear :color="getProgressColor(element.progress)" height="6" :model-value="element.progress || 0" rounded />
                  </div>
                  <div class="text-caption mt-2">{{ formatDate(element.deadline) }}</div>
                </v-card-text>
              </v-card>
            </template>
          </draggable>
        </div>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import draggable from 'vuedraggable'

  const props = defineProps<{
    actions: Array<{
      id: number
      status: string
      priority?: string
      progress?: number
      deadline?: string
      ref?: string
      title?: string
    }>
    loading?: boolean
  }>()
  const emit = defineEmits<{ 'update-status': [id: number, status: string], 'view-action': [id: number] }>()

  const columns = [
    { title: 'Planifiée', value: 'planned', icon: 'mdi-calendar', color: 'info' },
    { title: 'En cours', value: 'in_progress', icon: 'mdi-progress-clock', color: 'warning' },
    { title: 'Terminée', value: 'completed', icon: 'mdi-check-circle', color: 'success' },
    { title: 'En retard', value: 'overdue', icon: 'mdi-alert', color: 'error' },
  ]

  function getActionsForStatus (status: string) {
    return (props.actions || []).filter(a => a.status === status)
  }

  function getColumnCount (status: string) {
    return getActionsForStatus(status).length
  }

  function handleDrop (event: any, newStatus: string) {
    if (event.added) emit('update-status', event.added.element.id, newStatus)
  }

  function getPriorityColor (priority?: string) {
    const colors: Record<string, string> = {
      critical: 'error',
      high: 'warning',
      medium: 'info',
      low: 'grey',
    }
    return colors[priority ?? ''] || 'grey'
  }

  function getProgressColor (progress = 0) {
    const value = progress
    if (value >= 75) return 'success'
    if (value >= 50) return 'info'
    return 'warning'
  }

  function formatDate (date?: string) {
    return date ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) : '-'
  }
</script>
