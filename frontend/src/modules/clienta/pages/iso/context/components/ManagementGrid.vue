<template>
  <v-row>
    <v-col v-for="category in processCategories" :key="category.value" cols="12" md="4">
      <v-card class="category-card" elevation="0" rounded="xl" style="border: 2px solid #e2e8f0; overflow: hidden;">
        <div class="pa-6" :style="`background: ${category.bgColor}`">
          <div class="d-flex align-center">
            <v-avatar class="mr-4 elevation-4" :color="category.color" size="56">
              <v-icon color="white" size="28">{{ category.icon }}</v-icon>
            </v-avatar>
            <div>
              <h3 class="text-h5 font-weight-bold">{{ category.title }}</h3>
              <p class="text-body-2 text-medium-emphasis mb-0">{{ getProcessesByCategory(category.value).length }} processus</p>
            </div>
          </div>
        </div>
        <v-divider />
        <v-card-text class="pa-4">
          <v-list v-if="getProcessesByCategory(category.value).length > 0" density="compact">
            <v-list-item
              v-for="process in getProcessesByCategory(category.value)"
              :key="process.id"
              class="mb-2 process-item"
              rounded="lg"
              @click="handleViewProcess(process)"
            >
              <template #prepend>
                <v-avatar class="mr-2" :color="category.color" size="32">
                  <v-icon color="white" size="16">mdi-cog</v-icon>
                </v-avatar>
              </template>
              <v-list-item-title class="font-weight-medium">{{ getProcessName(process) }}</v-list-item-title>
              <template #append>
                <v-btn
                  :color="category.color"
                  icon="mdi-eye"
                  size="small"
                  variant="tonal"
                  @click.stop="handleViewProcess(process)"
                />
              </template>
            </v-list-item>
          </v-list>
          <div v-else class="text-center py-8">
            <v-icon class="mb-3" color="grey-lighten-2" size="64">{{ category.icon }}</v-icon>
            <p class="text-body-2 text-medium-emphasis">Aucun processus</p>
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  type ProcessCategory = {
    title: string
    value: string
    icon: string
    color: string
    bgColor: string
  }

  type ProcessItem = {
    id: number | string
    title?: string
    name?: string
    category?: string
  }

  defineProps({
    processCategories: {
      type: Array as PropType<ProcessCategory[]>,
      required: true,
    },
    getProcessesByCategory: {
      type: Function as PropType<(category: string) => ProcessItem[]>,
      required: true,
    },
    getProcessName: {
      type: Function as PropType<(process: ProcessItem) => string>,
      required: true,
    },
    handleViewProcess: {
      type: Function as PropType<(process: ProcessItem) => void>,
      required: true,
    },
  })
</script>

<style scoped>
.category-card {
  transition: all 0.3s ease;
}

.category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.process-item {
  transition: all 0.2s ease;
  cursor: pointer;
}

.process-item:hover {
  background: rgba(91, 141, 217, 0.08);
  transform: translateX(4px);
}
</style>
