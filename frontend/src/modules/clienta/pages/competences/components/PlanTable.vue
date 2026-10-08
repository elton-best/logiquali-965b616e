<template>
  <v-card elevation="1">
    <v-data-table
      :headers="headers"
      :items="items"
      :items-per-page="10"
    >
      <template #[`item.stats.totalFormations`]="{ item }">
        {{ item.stats.totalFormations }}
      </template>
      <template #[`item.stats.tauxRealisation`]="{ item }">
        {{ item.stats.tauxRealisation }}%
      </template>
      <template #item.status="{ item }">
        <v-chip :color="item.status === 'active' ? 'success' : (item.status === 'closed' ? 'grey' : 'warning')" size="small" variant="tonal">
          {{ item.status }}
        </v-chip>
      </template>
      <template #item.totalBudget="{ item }">
        {{ formatCurrency(item.totalBudget || 0) }}
      </template>
      <template #item.budgetEngaged="{ item }">
        {{ formatCurrency(item.budgetEngaged || 0) }}
      </template>
      <template #item.spentAmount="{ item }">
        {{ formatCurrency(item.spentAmount || 0) }}
      </template>
      <template #item.budgetRemaining="{ item }">
        {{ formatCurrency(item.budgetRemaining || 0) }}
      </template>
      <template #item.actions="{ item }">
        <v-btn color="info" size="small" variant="text" @click="emit('sync', item.id)">
          Recalculer
        </v-btn>
        <v-btn color="success" size="small" variant="text" @click="emit('export', item.id)">
          Exporter
        </v-btn>
        <v-btn color="primary" size="small" variant="text" @click="emit('edit', item)">
          Modifier
        </v-btn>
        <v-btn color="error" size="small" variant="text" @click="emit('delete', item)">
          Supprimer
        </v-btn>
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { TrainingPlan } from '../../../types/formation.types'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, unknown>>>,
      required: true,
    },
    items: {
      type: Array as PropType<TrainingPlan[]>,
      required: true,
    },
    formatCurrency: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'sync', value: number): void
    (event: 'export', value: number): void
    (event: 'edit', value: TrainingPlan): void
    (event: 'delete', value: TrainingPlan): void
  }>()
</script>
