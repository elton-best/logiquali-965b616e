<template>
  <v-card elevation="2">
    <v-card-text class="pa-4">
      <h3 class="text-h6 mb-4">Diagramme de Gantt</h3>
      <div class="gantt-timeline">
        <v-row v-for="action in sortedActions" :key="action.id" class="gantt-row mb-2">
          <v-col cols="3">
            <div class="text-body-2 font-weight-medium">{{ action.ref }}</div>
            <div class="text-caption text-medium-emphasis">{{ action.title }}</div>
          </v-col>
          <v-col cols="9">
            <v-progress-linear
              :color="getStatusColor(action.status)"
              height="24"
              :model-value="action.progress || 0"
              rounded
            >
              <template #default>
                <span class="text-caption white--text">{{ action.progress || 0 }}% - {{ formatDate(action.deadline) }}</span>
              </template>
            </v-progress-linear>
          </v-col>
        </v-row>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{ actions: any[], loading?: boolean }>()
  defineEmits<{ 'view-action': [id: number] }>()

  const sortedActions = computed(() => {
    return [...(props.actions || [])].toSorted((a, b) => {
      return new Date(a.deadline).getTime() - new Date(b.deadline).getTime()
    })
  })

  function getStatusColor (status: string) {
    return { planned: 'info', in_progress: 'warning', completed: 'success', overdue: 'error' }[status] || 'grey'
  }

  function formatDate (date: string) {
    return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
  }
</script>

<style scoped>
.gantt-row { border-bottom: 1px solid #eee; padding: 8px 0; }
</style>
