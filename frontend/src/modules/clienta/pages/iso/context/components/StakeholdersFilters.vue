<template>
  <AppCard class="mb-6">
    <div class="stakeholders-filters-row p-4">
      <div class="stakeholders-filter-search">
        <AppInput
          clearable
          :model-value="filters.search"
          placeholder="Rechercher une partie prenante..."
          type="search"
          @update:model-value="updateFilter('search', String($event || ''))"
        />
      </div>
      <div class="stakeholders-filter-type">
        <AppSelect
          :model-value="filters.type ?? ''"
          :options="stakeholderTypeOptions"
          placeholder="Type"
          @update:model-value="updateFilter('type', $event || null)"
        />
      </div>
      <div class="stakeholders-filter-influence">
        <AppSelect
          :model-value="filters.influence ?? ''"
          :options="influenceOptions"
          placeholder="Influence"
          @update:model-value="updateFilter('influence', $event || null)"
        />
      </div>
      <div class="stakeholders-filter-reset">
        <AppButton
          :disabled="!hasActiveFilters"
          rounded
          size="sm"
          variant="outline"
          @click="emit('reset')"
        >
          Réinitialiser
        </AppButton>
      </div>
      <div class="stakeholders-filter-view">
        <v-btn-toggle
          v-model="viewModeValue"
          class="w-100"
          color="primary"
          density="compact"
          mandatory
          rounded="xl"
        >
          <v-btn icon="mdi-view-grid" size="small" value="grid" />
          <v-btn icon="mdi-view-list" size="small" value="list" />
          <v-btn icon="mdi-format-list-checks" size="small" value="actions" />
        </v-btn-toggle>
      </div>
    </div>
  </AppCard>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'
  import { AppButton, AppCard, AppInput, AppSelect } from '@/components/common'

  const props = defineProps({
    filters: {
      type: Object as PropType<{ search: string, type: string | null, influence: string | null }>,
      required: true,
    },
    stakeholderTypeOptions: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    influenceOptions: {
      type: Array as PropType<Array<{ label: string, value: string }>>,
      required: true,
    },
    viewMode: {
      type: String as PropType<'grid' | 'list' | 'actions'>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:filters', value: { search: string, type: string | null, influence: string | null }): void
    (event: 'update:viewMode', value: 'grid' | 'list' | 'actions'): void
    (event: 'reset'): void
  }>()

  const viewModeValue = computed({
    get: () => props.viewMode,
    set: value => emit('update:viewMode', value),
  })

  function updateFilter (key: 'search' | 'type' | 'influence', value: string | null) {
    emit('update:filters', { ...props.filters, [key]: value })
  }
</script>

<style scoped>
.stakeholders-filters-row {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
  gap: 12px;
  align-items: end;
}

.stakeholders-filter-reset {
  display: flex;
  justify-content: flex-end;
}

.stakeholders-filter-view {
  display: flex;
  justify-content: flex-end;
}

:deep(.stakeholders-filter-search .input-container),
:deep(.stakeholders-filter-type .select-container),
:deep(.stakeholders-filter-influence .select-container) {
  background: #ffffff;
  border-color: #d1d5db;
}

:deep(.stakeholders-filter-search .input-container:focus-within) {
  box-shadow: none;
}

:deep(.stakeholders-filter-search .input-field) {
  color: #111827;
}

:deep(.stakeholders-filter-search .input-field::placeholder),
:deep(.stakeholders-filter-type .select-field.select-placeholder),
:deep(.stakeholders-filter-influence .select-field.select-placeholder) {
  color: #6b7280;
}

:deep(.stakeholders-filter-reset .app-button) {
  background: #ffffff;
  border-color: #d1d5db;
  color: #1f2937;
}

@media (max-width: 1280px) {
  .stakeholders-filters-row {
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1fr);
  }

  .stakeholders-filter-reset,
  .stakeholders-filter-view {
    justify-content: flex-start;
  }
}

@media (max-width: 960px) {
  .stakeholders-filters-row {
    grid-template-columns: 1fr;
  }
}
</style>
