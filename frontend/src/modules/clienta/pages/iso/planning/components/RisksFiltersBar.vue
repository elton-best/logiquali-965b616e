<template>
  <v-card class="filters-card mb-6" elevation="0" rounded="xl">
    <v-card-text class="pa-4">
      <v-row align="center" class="filters-row" dense>
        <v-col cols="12" md="3">
          <v-btn-toggle
            v-model="viewModeValue"
            class="w-100"
            density="compact"
            mandatory
            rounded="lg"
          >
            <v-btn class="flex-grow-1" prepend-icon="mdi-alert-outline" size="small" value="risque">Risques</v-btn>
            <v-btn class="flex-grow-1" prepend-icon="mdi-lightbulb-outline" size="small" value="opportunite">Opportunités</v-btn>
          </v-btn-toggle>
        </v-col>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="searchValue"
            class="filters-search"
            clearable
            density="compact"
            hide-details
            placeholder="Rechercher description, cause, processus..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            @click:clear="searchValue = ''"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="processValue"
            clearable
            density="compact"
            hide-details
            item-title="title"
            item-value="value"
            :items="processOptions"
            placeholder="Processus"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="statusValue"
            clearable
            density="compact"
            hide-details
            item-title="label"
            item-value="value"
            :items="statusItems"
            placeholder="Statut"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="niveauValue"
            clearable
            density="compact"
            hide-details
            item-title="label"
            item-value="value"
            :items="niveauOptions"
            placeholder="Niveau"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex justify-start align-center filters-actions" cols="12" md="2">
          <v-btn
            aria-label="Réinitialiser les filtres"
            class="filters-reset-btn"
            :disabled="!hasActiveFilters"
            icon="mdi-filter-off"
            size="small"
            variant="outlined"
            @click="emit('reset')"
          />
          <v-btn-toggle
            v-model="viewTypeValue"
            class="filters-view-toggle"
            density="compact"
            mandatory
            rounded="lg"
          >
            <v-btn icon="mdi-view-grid-outline" size="small" value="grid" />
            <v-btn icon="mdi-format-list-bulleted" size="small" value="list" />
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
    viewType: {
      type: String as PropType<'grid' | 'list'>,
      required: true,
    },
    filters: {
      type: Object as PropType<{
        search: string
        process_id: number | null
        status: string | null
        niveau: string | null
      }>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ title: string, value: number }>>,
      required: true,
    },
    statusItems: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    niveauOptions: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:viewMode', value: 'risque' | 'opportunite'): void
    (event: 'update:viewType', value: 'grid' | 'list'): void
    (event: 'update:filters', value: { search: string, process_id: number | null, status: string | null, niveau: string | null }): void
    (event: 'reset'): void
  }>()

  const viewModeValue = computed({
    get: () => props.viewMode,
    set: value => emit('update:viewMode', value),
  })

  const viewTypeValue = computed({
    get: () => props.viewType,
    set: value => emit('update:viewType', value),
  })

  const searchValue = computed({
    get: () => props.filters.search,
    set: value => emit('update:filters', { ...props.filters, search: value }),
  })

  const processValue = computed({
    get: () => props.filters.process_id,
    set: value => emit('update:filters', { ...props.filters, process_id: value }),
  })

  const statusValue = computed({
    get: () => props.filters.status,
    set: value => emit('update:filters', { ...props.filters, status: value }),
  })

  const niveauValue = computed({
    get: () => props.filters.niveau,
    set: value => emit('update:filters', { ...props.filters, niveau: value }),
  })
</script>

<style scoped>
.filters-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.filters-row {
  flex-wrap: nowrap;
  gap: 10px;
}

.filters-actions {
  gap: 8px;
}

:deep(.filters-card .v-field) {
  background: #ffffff;
}

:deep(.filters-card .v-field__outline) {
  opacity: 1;
}

:deep(.filters-card .v-field__input),
:deep(.filters-card .v-field__input::placeholder),
:deep(.filters-card .v-select__selection-text) {
  font-size: 0.8rem;
}

:deep(.filters-reset-btn) {
  min-height: 32px;
  width: 32px;
  height: 32px;
  font-size: 0.78rem;
  white-space: nowrap;
  border-color: #94a3b8;
  color: #1f2937;
  background: #ffffff;
}

:deep(.filters-reset-btn .v-btn__content) {
  gap: 6px;
}

:deep(.filters-reset-btn .v-icon) {
  font-size: 18px;
}

:deep(.filters-view-toggle) {
  border: 1px solid #e2e8f0;
  background: #ffffff;
}

:deep(.filters-view-toggle .v-btn) {
  min-width: 34px;
}

@media (max-width: 960px) {
  .filters-row {
    flex-wrap: wrap;
  }
}
</style>
