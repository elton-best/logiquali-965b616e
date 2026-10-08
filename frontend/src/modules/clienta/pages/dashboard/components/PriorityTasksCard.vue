<template>
  <v-card class="dashboard-panel h-100" elevation="0" rounded="lg">
    <v-card-title class="pa-4">
      <v-icon color="warning" size="20">mdi-alert-circle</v-icon>
      <span class="ml-2 text-subtitle-1 font-weight-bold">Tâches prioritaires</span>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-0">
      <v-list v-if="priorityTasks.length > 0" class="py-0">
        <v-list-item v-for="task in priorityTasks" :key="task.id" class="task-item">
          <template #prepend>
            <v-icon :color="task.color" size="20">{{ task.icon }}</v-icon>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ task.title }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ task.subtitle }}
          </v-list-item-subtitle>
          <template #append>
            <v-chip :color="task.color" size="small" variant="flat">{{ task.count }}</v-chip>
          </template>
        </v-list-item>
      </v-list>
      <div v-else class="text-center pa-6">
        <v-icon color="success" size="40">mdi-check-all</v-icon>
        <p class="text-caption text-medium-emphasis mt-2">Aucune tâche</p>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  export type PriorityTask = {
    id: number
    title: string
    subtitle: string
    count: number
    icon: string
    color: string
  }

  defineProps<{
    priorityTasks: PriorityTask[]
  }>()
</script>

<style scoped>
.task-item {
  transition: all 0.2s ease;
  animation: pulse 2s ease-in-out infinite;
}

.task-item:hover {
  background: rgba(91, 141, 217, 0.04);
  transform: translateX(4px);
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.95;
  }
}
</style>
