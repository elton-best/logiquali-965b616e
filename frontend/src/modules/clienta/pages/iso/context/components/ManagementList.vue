<template>
  <v-card
    class="management-list-card w-100"
    elevation="0"
    rounded="xl"
    style="border: 2px solid #e2e8f0;"
  >
    <v-card-text class="pa-0">
      <DataTable
        empty-action-label="Créer un processus"
        empty-description="Commencez par créer votre premier processus"
        empty-icon="mdi-sitemap"
        empty-title="Aucun processus"
        :headers="listHeaders"
        :items="processes"
        :items-per-page="listItemsPerPage"
        :loading="loading"
        @create="openCreateModal"
      >
        <template #item.name="{ item }">
          <div class="d-flex align-center gap-3">
            <v-avatar :color="getCategoryColor(item.category)" size="40">
              <v-icon color="white" size="20">mdi-cog</v-icon>
            </v-avatar>
            <div>
              <div class="text-body-1 font-weight-bold">{{ getProcessName(item) }}</div>
              <div class="text-caption text-medium-emphasis">ID: {{ item.id }}</div>
            </div>
          </div>
        </template>

        <template #item.category="{ item }">
          <v-chip
            class="font-weight-bold"
            :color="getCategoryColor(item.category)"
            size="small"
            variant="tonal"
          >
            <v-icon start>{{ getCategoryIcon(item.category) }}</v-icon>
            {{ getCategoryTitle(item.category) }}
          </v-chip>
        </template>

        <template #item.actions="{ item }">
          <v-btn
            :color="getCategoryColor(item.category)"
            icon="mdi-eye"
            size="small"
            variant="tonal"
            @click.stop="handleViewProcess(item)"
          />
        </template>
      </DataTable>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'

  type ProcessItem = {
    id: number | string
    title?: string
    name?: string
    category?: string
  }

  defineProps({
    listHeaders: {
      type: Array as PropType<Array<{ title: string, key: string, sortable?: boolean }>>,
      required: true,
    },
    processes: {
      type: Array as PropType<ProcessItem[]>,
      required: true,
    },
    listItemsPerPage: {
      type: Number,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    getCategoryColor: {
      type: Function as PropType<(category?: string) => string>,
      required: true,
    },
    getCategoryIcon: {
      type: Function as PropType<(category?: string) => string>,
      required: true,
    },
    getCategoryTitle: {
      type: Function as PropType<(category?: string) => string>,
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
    openCreateModal: {
      type: Function as PropType<() => void>,
      required: true,
    },
  })
</script>

<style scoped>
.management-list-card {
  width: 100%;
  max-width: 100%;
}

:deep(.app-table) {
  width: 100%;
}

:deep(.app-table .overflow-x-auto) {
  width: 100%;
}

:deep(.app-table table) {
  table-layout: fixed;
  width: 100%;
}

:deep(.app-table th),
:deep(.app-table td) {
  vertical-align: middle;
}
</style>
