<template>
  <v-card v-if="quickActions.length > 0" class="dashboard-panel h-100" elevation="0" rounded="lg">
    <v-card-title class="pa-4">
      <v-icon color="primary" size="20">mdi-lightning-bolt</v-icon>
      <span class="ml-2 text-subtitle-1 font-weight-bold">Actions rapides</span>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-2">
      <v-list class="py-0">
        <v-list-item
          v-for="action in quickActions"
          :key="action.id"
          class="quick-action-item"
          rounded="lg"
          @click="emit('action', action.id)"
        >
          <template #prepend>
            <v-avatar :color="action.color" size="36" variant="tonal">
              <v-icon :color="action.color" size="18">{{ action.icon }}</v-icon>
            </v-avatar>
          </template>
          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ action.title }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ action.description }}
          </v-list-item-subtitle>
        </v-list-item>
      </v-list>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  export type QuickAction = {
    id: string
    title: string
    description: string
    icon: string
    color: string
  }

  defineProps<{
    quickActions: QuickAction[]
  }>()

  const emit = defineEmits<{
    (event: 'action', id: string): void
  }>()
</script>

<style scoped>
.quick-action-item {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  margin-bottom: 4px;
}

.quick-action-item:hover {
  background: rgba(91, 141, 217, 0.04);
  border-color: rgba(91, 141, 217, 0.2);
  transform: translateX(4px);
}

.quick-action-item:active {
  transform: translateX(2px) scale(0.98);
}
</style>
