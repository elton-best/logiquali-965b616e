<template>
  <v-card elevation="1">
    <v-data-table
      class="elevation-0"
      :headers="headers"
      :items="items"
      :items-per-page="10"
    >
      <template #item.numero="{ item }">
        <v-chip color="primary" size="small" variant="flat">
          #{{ item.numero }}
        </v-chip>
      </template>

      <template #item.status="{ item }">
        <StatusBadge
          :is-incomplete="!item.dateDebut || !item.dateFin"
          :status="item.status"
        />
      </template>

      <template #item.alerts="{ item }">
        <AlertIndicator
          :alert-state="item.alertState"
          :date-debut="item.dateDebut"
          :status="item.status"
        />
      </template>

      <template #item.dateDebut="{ item }">
        <span v-if="item.dateDebut">
          {{ formatDate(item.dateDebut) }}
        </span>
        <span v-else class="text-grey">À compléter</span>
      </template>

      <template #item.cibles="{ item }">
        <div class="d-flex flex-wrap gap-1">
          <v-chip
            v-for="target in getTargetLabels(item).slice(0, 2)"
            :key="target"
            size="x-small"
            variant="outlined"
          >
            {{ target }}
          </v-chip>
          <v-chip v-if="getTargetLabels(item).length > 2" size="x-small" variant="outlined">
            +{{ getTargetLabels(item).length - 2 }}
          </v-chip>
        </div>
      </template>

      <template #item.actions="{ item }">
        <v-btn
          icon="mdi-eye"
          size="small"
          variant="text"
          @click="emit('view', item)"
        />
        <v-btn
          icon="mdi-chart-line"
          size="small"
          variant="text"
          @click="emit('track', item)"
        />
        <v-btn
          icon="mdi-pencil"
          size="small"
          variant="text"
          @click="emit('edit', item)"
        />
        <v-btn
          v-if="!['realisee', 'annulee'].includes(item.status)"
          icon="mdi-delete"
          size="small"
          variant="text"
          @click="emit('delete', item)"
        />
      </template>
    </v-data-table>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { Formation } from '../../../types/formation.types'
  import AlertIndicator from '../../../components/competences/AlertIndicator.vue'
  import StatusBadge from '../../../components/competences/StatusBadge.vue'

  defineProps({
    headers: {
      type: Array as PropType<Array<Record<string, unknown>>>,
      required: true,
    },
    items: {
      type: Array as PropType<Formation[]>,
      required: true,
    },
    getTargetLabels: {
      type: Function as PropType<(formation: Formation) => string[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'view', value: Formation): void
    (event: 'track', value: Formation): void
    (event: 'edit', value: Formation): void
    (event: 'delete', value: Formation): void
  }>()

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>
