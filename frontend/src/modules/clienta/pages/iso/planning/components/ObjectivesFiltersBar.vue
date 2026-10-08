<template>
  <v-card class="mb-6" elevation="0" rounded="lg">
    <v-card-text>
      <v-row align="center">
        <v-col cols="12" md="3">
          <v-text-field
            v-model="searchValue"
            density="comfortable"
            hide-details
            placeholder="Rechercher..."
            prepend-inner-icon="mdi-magnify"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="processusValue"
            clearable
            density="comfortable"
            hide-details
            :items="processusList"
            label="Processus"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="axeValue"
            clearable
            density="comfortable"
            hide-details
            :items="axisOptions"
            label="Axe"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="frequenceValue"
            clearable
            density="comfortable"
            hide-details
            :items="frequenceOptions"
            label="Fréquence"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex justify-end" cols="12" md="3" style="gap: 8px;">
          <v-btn-toggle v-model="viewModeValue" density="comfortable" mandatory rounded="lg">
            <v-btn icon="mdi-view-grid" value="grid" />
            <v-btn icon="mdi-view-list" value="list" />
          </v-btn-toggle>
          <v-btn
            :disabled="!hasActiveFilters"
            prepend-icon="mdi-filter-off"
            rounded="lg"
            variant="outlined"
            @click="emit('reset')"
          >
            Réinitialiser
          </v-btn>
          <v-btn
            color="success"
            :disabled="loading || filteredCount === 0"
            :loading="exporting"
            prepend-icon="mdi-file-excel"
            rounded="lg"
            variant="tonal"
            @click="emit('export')"
          >
            Exporter
          </v-btn>
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            rounded="lg"
            @click="emit('add')"
          >
            Ajouter
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    search: {
      type: String,
      required: true,
    },
    filterProcessus: {
      type: String as PropType<string | null>,
      required: true,
    },
    filterAxe: {
      type: String as PropType<string | null>,
      required: true,
    },
    filterFrequence: {
      type: String as PropType<string | null>,
      required: true,
    },
    viewMode: {
      type: String,
      required: true,
    },
    processusList: {
      type: Array as PropType<string[]>,
      required: true,
    },
    axisOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      required: true,
    },
    loading: {
      type: Boolean,
      required: true,
    },
    exporting: {
      type: Boolean,
      required: true,
    },
    filteredCount: {
      type: Number,
      required: true,
    },
  })

  const frequenceOptions = ['Mensuel', 'Trimestriel', 'Semestriel', 'Annuel']

  const emit = defineEmits<{
    (event: 'update:search', value: string): void
    (event: 'update:filterProcessus', value: string | null): void
    (event: 'update:filterAxe', value: string | null): void
    (event: 'update:filterFrequence', value: string | null): void
    (event: 'update:viewMode', value: string): void
    (event: 'reset'): void
    (event: 'export'): void
    (event: 'add'): void
  }>()

  const searchValue = computed({
    get: () => props.search,
    set: value => emit('update:search', value),
  })

  const processusValue = computed({
    get: () => props.filterProcessus,
    set: value => emit('update:filterProcessus', value),
  })

  const axeValue = computed({
    get: () => props.filterAxe,
    set: value => emit('update:filterAxe', value),
  })

  const frequenceValue = computed({
    get: () => props.filterFrequence,
    set: value => emit('update:filterFrequence', value),
  })

  const viewModeValue = computed({
    get: () => props.viewMode,
    set: value => emit('update:viewMode', value),
  })
</script>
