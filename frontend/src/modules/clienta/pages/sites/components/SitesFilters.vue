<template>
  <AppCard class="mb-6">
    <div class="sites-filters-row p-4">
      <div class="sites-filter-search">
        <AppInput
          :model-value="filters.search"
          placeholder="Rechercher un site..."
          type="search"
          @update:model-value="emit('update:search', String($event || ''))"
        />
      </div>

      <div class="sites-filter-status">
        <AppSelect
          :model-value="filters.status"
          :options="statusFilterOptions"
          placeholder="Statut"
          @update:model-value="emit('update:status', $event)"
        />
      </div>

      <div class="sites-filter-reset">
        <AppButton
          class="reset-action-btn"
          rounded
          size="sm"
          variant="outline"
          @click="emit('reset')"
        >
          Réinitialiser
        </AppButton>
      </div>
    </div>
  </AppCard>
</template>

<script setup lang="ts">
  import { AppButton, AppCard, AppInput, AppSelect } from '@/components/common'

  type Filters = { search: string, status: string }

  defineProps<{
    filters: Filters
    statusFilterOptions: Array<{ label: string, value: string }>
  }>()

  const emit = defineEmits<{
    (event: 'update:search', value: string): void
    (event: 'update:status', value: string): void
    (event: 'reset'): void
  }>()
</script>

<style scoped>
.sites-filters-row {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) auto;
  gap: 12px;
  align-items: end;
}

.sites-filter-reset {
  display: flex;
  justify-content: flex-end;
}

:deep(.sites-filter-search .input-container),
:deep(.sites-filter-status .select-container) {
  background: #ffffff;
  border-color: #d1d5db;
}

:deep(.sites-filter-search .input-field::placeholder),
:deep(.sites-filter-status .select-field.select-placeholder) {
  color: #6b7280;
}

@media (max-width: 960px) {
  .sites-filters-row {
    grid-template-columns: 1fr;
  }

  .sites-filter-reset {
    justify-content: flex-start;
  }
}
</style>
