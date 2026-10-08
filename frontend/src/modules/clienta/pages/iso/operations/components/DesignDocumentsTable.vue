<template>
  <v-card elevation="2" rounded="lg">
    <v-data-table
      :headers="headers"
      :items="documents"
      :items-per-page="10"
      :loading="loading"
    >
      <template #item.type="{ item }">
        <v-chip size="small" variant="tonal">{{ typeLabel(item.type) }}</v-chip>
      </template>
      <template #item.processus="{ item }">
        {{ processLabel(item.processus) }}
      </template>
      <template #item.statut="{ item }">
        <v-chip :color="statutColor(item.statut)" size="small" variant="tonal">{{ statutLabel(item.statut) }}</v-chip>
      </template>
      <template #item.etat="{ item }">
        <v-chip :color="etatColor(item.etat)" size="small" variant="tonal">{{ etatLabel(item.etat) }}</v-chip>
      </template>
      <template #item.date_creation="{ item }">
        {{ formatDate(item.date_creation) }}
      </template>
      <template #item.validated_at="{ item }">
        {{ item.validated_at ? formatDate(item.validated_at) : '-' }}
      </template>
      <template #item.fichier="{ item }">
        <div v-if="item.fichier" class="d-flex align-center ga-1">
          <v-btn
            color="primary"
            icon="mdi-eye"
            size="small"
            variant="text"
            @click="emit('view', item)"
          />
          <v-btn
            color="secondary"
            icon="mdi-download"
            size="small"
            variant="text"
            @click="emit('download', item)"
          />
        </div>
        <span v-else>-</span>
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
    documents: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    emptyStateMessage: {
      type: String,
      required: true,
    },
    typeLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    processLabel: {
      type: Function as PropType<(value?: string) => string>,
      required: true,
    },
    statutLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    statutColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    etatLabel: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    etatColor: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
    formatDate: {
      type: Function as PropType<(value?: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'view', item: any): void
    (e: 'download', item: any): void
  }>()

  void emit
</script>
