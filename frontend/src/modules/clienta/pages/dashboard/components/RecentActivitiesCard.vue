<template>
  <v-card class="dashboard-panel" elevation="0" rounded="lg">
    <v-card-title class="pa-4">
      <v-icon color="primary" size="20">mdi-history</v-icon>
      <span class="ml-2 text-subtitle-1 font-weight-bold">Activités récentes</span>
      <v-spacer />
      <v-chip color="primary" size="small" variant="tonal">{{ recentActivities.length }}</v-chip>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-0">
      <v-list v-if="recentActivities.length > 0" class="py-0">
        <v-list-item
          v-for="activity in recentActivities"
          :key="activity.id"
          :class="[
            'activity-item',
            { 'activity-item--clickable': activity.canOpen },
          ]"
          @click="emit('select', activity)"
        >
          <template #prepend>
            <v-avatar :color="activity.color" size="36" variant="tonal">
              <v-icon :color="activity.color" size="18">{{ activity.icon }}</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">{{ activity.title }}</v-list-item-title>
          <v-list-item-subtitle class="text-caption">{{ activity.time }}</v-list-item-subtitle>
          <template #append>
            <v-chip :color="activity.chipColor" size="small" variant="flat">{{ activity.category }}</v-chip>
            <v-icon v-if="activity.canOpen" class="ml-2" color="medium-emphasis" size="18">
              mdi-chevron-right
            </v-icon>
          </template>
        </v-list-item>
      </v-list>
      <div v-else class="text-center pa-6">
        <v-icon color="grey-lighten-1" size="40">mdi-inbox</v-icon>
        <p class="text-caption text-medium-emphasis mt-2">Aucune activité</p>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  export type RecentActivity = {
    id: string | number
    title: string
    time: string
    category: string
    icon: string
    color: string
    chipColor: string
    route: string | null
    canOpen: boolean
  }

  defineProps<{
    recentActivities: RecentActivity[]
  }>()

  const emit = defineEmits<{
    (event: 'select', activity: RecentActivity): void
  }>()
</script>

<style scoped>
.activity-item,
.task-item {
  transition: all 0.2s ease;
}

.activity-item--clickable {
  cursor: pointer;
}

.activity-item--clickable:hover,
.task-item:hover {
  background: rgba(91, 141, 217, 0.04);
  transform: translateX(4px);
}
</style>
