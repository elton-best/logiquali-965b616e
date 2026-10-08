<template>
  <v-card elevation="2" rounded="lg">
    <v-card-title class="d-flex align-center justify-space-between">
      <span class="text-h6">Registre des suggestions</span>
      <v-chip color="primary" variant="tonal">{{ count }} résultat(s)</v-chip>
    </v-card-title>

    <DataTable :headers="headers" :items="items" :items-per-page="10">
      <template #item.reference="{ item }">
        <v-btn class="link-cell-btn" size="small" variant="text" @click="emit('select', item.id)">
          {{ item.reference }}
        </v-btn>
      </template>

      <template #item.title="{ item }">
        <div class="font-weight-medium">{{ item.title }}</div>
        <div class="text-caption text-medium-emphasis truncate max-w-[340px]">{{ item.description }}</div>
      </template>

      <template #item.status="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="flat">{{ statusLabel(item.status) }}</v-chip>
      </template>

      <template #item.impact="{ item }">
        <v-chip :color="impactColor(item.impact)" size="small" variant="tonal">{{ item.impact }}</v-chip>
      </template>

      <template #item.actions="{ item }">
        <div class="d-flex align-center justify-end ga-1">
          <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="emit('select', item.id)" />
          <v-btn icon="mdi-pencil-outline" size="small" variant="text" @click="emit('edit', item.id)" />
          <v-btn
            color="error"
            icon="mdi-delete-outline"
            size="small"
            variant="text"
            @click="emit('delete', item.id)"
          />
        </div>
      </template>
    </DataTable>

    <v-alert
      v-if="items.length === 0"
      class="mx-4 mb-4"
      density="comfortable"
      type="info"
      variant="tonal"
    >
      {{ emptyStateMessage }}
    </v-alert>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'

  interface SuggestionItem {
    id: number
    reference: string
    title: string
    description: string
    status: string
    impact: string
  }

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, unknown>>>,
      required: true,
    },
    items: {
      type: Array as PropType<SuggestionItem[]>,
      required: true,
    },
    count: {
      type: Number,
      required: true,
    },
    statusColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    statusLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    impactColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    emptyStateMessage: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'select', value: number): void
    (event: 'edit', value: number): void
    (event: 'delete', value: number): void
  }>()
</script>
