<template>
  <v-card rounded="xl">
    <v-data-table
      class="elevation-0"
      :headers="headers"
      item-value="id"
      :items="items"
      :loading="loading"
    >
      <template #[`item.code`]="{ item }">
        <v-chip :color="viewMode === 'risque' ? 'error' : 'success'" size="small" variant="flat">#{{ item.code }}</v-chip>
      </template>
      <template #[`item.process_name`]="{ item }">
        <v-chip color="primary" size="small" variant="tonal">{{ item.process_name || '-' }}</v-chip>
      </template>
      <template #[`item.evaluation`]="{ item }">
        <div class="d-flex ga-1">
          <v-chip size="x-small" variant="tonal">P: {{ item.probabilite }}</v-chip>
          <v-chip size="x-small" variant="tonal">{{ viewMode === 'risque' ? 'G' : 'R' }}: {{ item.gravite }}</v-chip>
        </div>
      </template>
      <template #[`item.score`]="{ item }">
        <v-chip :color="scoreColor(item.score, viewMode)" size="small" variant="tonal">{{ item.score }}</v-chip>
      </template>
      <template #[`item.status`]="{ item }">
        <v-chip :color="statusColor(item.status)" size="small" variant="tonal">
          {{ statusItems.find(s => s.value === item.status)?.label || item.status }}
        </v-chip>
      </template>
      <template #[`item.actions`]="{ item }">
        <div class="actions-cell">
          <v-btn icon="mdi-eye-outline" size="x-small" variant="text" @click="emit('view', item.id)" />
          <v-btn icon="mdi-pencil-outline" size="x-small" variant="text" @click="emit('edit', item)" />
          <v-btn
            color="error"
            icon="mdi-delete-outline"
            size="x-small"
            variant="text"
            @click="emit('delete', item)"
          />
        </div>
      </template>
      <template #no-data>
        <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    items: {
      type: Array as PropType<any[]>,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
    statusItems: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    statusColor: {
      type: Function as PropType<(status: string) => string>,
      required: true,
    },
    scoreColor: {
      type: Function as PropType<(score: number, type?: 'risque' | 'opportunite') => string>,
      required: true,
    },
    emptyStateMessage: {
      type: String,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'view', id: number): void
    (event: 'edit', item: any): void
    (event: 'delete', item: any): void
  }>()
</script>
