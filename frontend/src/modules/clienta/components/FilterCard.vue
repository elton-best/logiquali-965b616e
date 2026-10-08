<template>
  <AppCard class="mb-6">
    <div
      class="filters-grid grid grid-cols-12 gap-4 p-4 md:items-end"
      :class="{ 'filters-grid--single-row': forceSingleRow }"
    >
      <div class="col-span-12 filter-cell" :style="getCellStyle(searchMd)">
        <AppInput
          class="filter-search-input"
          :model-value="modelValue?.search || ''"
          :placeholder="searchPlaceholder"
          type="search"
          @update:model-value="updateFilter('search', $event)"
        />
      </div>

      <div
        v-for="filter in filters"
        :key="filter.key"
        class="col-span-12 filter-cell"
        :style="getCellStyle(filter.md || filterMd)"
      >
        <AppSelect
          v-if="filter.type === 'select' || filter.type === 'autocomplete'"
          clearable
          :label="filter.label || undefined"
          :model-value="modelValue[filter.key]"
          :option-label="filter.itemTitle || 'label'"
          :option-value="filter.itemValue || 'value'"
          :options="filter.items || []"
          :placeholder="filter.placeholder || filter.label || ''"
          @update:model-value="updateFilter(filter.key, $event)"
        />

        <AppDatePickerField
          v-else-if="filter.type === 'date'"
          clearable
          :label="filter.label"
          :model-value="modelValue[filter.key]"
          :placeholder="filter.placeholder || 'Sélectionner une date'"
          @update:model-value="updateFilter(filter.key, $event)"
        />
      </div>

      <div
        v-if="showReset"
        class="col-span-12 filter-cell flex items-center md:justify-end"
        :class="{ 'reset-cell-auto': resetMd === 'auto' }"
        :style="getResetCellStyle(resetMd)"
      >
        <AppButton
          class="reset-action-btn"
          :prefix-icon="FilterX"
          rounded
          size="sm"
          variant="outline"
          @click="$emit('reset')"
        >
          Réinitialiser
        </AppButton>
      </div>
    </div>
  </AppCard>
</template>

<script setup lang="ts">
  import { FilterX } from 'lucide-vue-next'
  import AppButton from '@/components/common/AppButton.vue'
  import AppCard from '@/components/common/AppCard.vue'
  import AppInput from '@/components/common/AppInput.vue'
  import AppSelect from '@/components/common/AppSelect.vue'

  interface FilterItem {
    key: string
    type: string
    label?: string
    placeholder?: string
    items?: any[]
    itemTitle?: string
    itemValue?: string
    md?: number
  }

  interface Props {
    modelValue?: Record<string, any>
    filters?: FilterItem[]
    searchPlaceholder?: string
    searchMd?: number
    filterMd?: number
    resetMd?: number | 'auto'
    forceSingleRow?: boolean
    showReset?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({}),
    filters: () => [],
    searchPlaceholder: 'Rechercher...',
    searchMd: 4,
    filterMd: 3,
    resetMd: 'auto',
    forceSingleRow: false,
    showReset: true,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, any>): void
    (e: 'reset'): void
  }>()

  function updateFilter (key: string, value: any) {
    emit('update:modelValue', { ...props.modelValue, [key]: value })
  }

  function clampSpan (value: number) {
    if (!Number.isFinite(value)) return 12
    return Math.min(12, Math.max(1, Math.round(value)))
  }

  function getCellStyle (span: number) {
    const safeSpan = clampSpan(span)
    const baseSpan = props.forceSingleRow ? safeSpan : 12
    return {
      '--base-span': String(baseSpan),
      '--md-span': String(safeSpan),
    }
  }

  function getResetCellStyle (value: number | 'auto') {
    if (value === 'auto') {
      return {
        '--base-span': props.forceSingleRow ? 'auto' : '12',
        '--md-span': 'auto',
      }
    }
    return getCellStyle(value)
  }
</script>

<style scoped>
:deep(.filter-search-input .input-container) {
  background: #ffffff;
  border-color: #d1d5db;
}

:deep(.filter-search-input .input-field) {
  color: #111827;
}

:deep(.filter-search-input .input-field::placeholder) {
  color: #6b7280;
}

:deep(.app-select-wrapper .select-container) {
  background: #ffffff;
  border-color: #d1d5db;
}

:deep(.app-select-wrapper .select-field) {
  color: #111827;
}

:deep(.app-select-wrapper .select-field.select-placeholder) {
  color: #6b7280;
}

.reset-action-btn {
  border-color: #ef4444;
  color: #b91c1c;
  background: #fef2f2;
}

.reset-action-btn:hover {
  border-color: #dc2626;
  color: #991b1b;
  background: #fee2e2;
}

.filter-cell {
  grid-column: span var(--base-span, 12) / span var(--base-span, 12);
  min-width: 0;
}

@media (min-width: 768px) {
  .filter-cell {
    grid-column: span var(--md-span, 12) / span var(--md-span, 12);
  }

  .filter-cell.reset-cell-auto {
    grid-column: auto;
  }
}

.filters-grid--single-row {
  display: flex;
  flex-wrap: nowrap;
  align-items: flex-end;
  overflow-x: auto;
}

.filters-grid--single-row .filter-cell {
  flex: 0 0 220px;
}

.filters-grid--single-row .filter-cell.reset-cell-auto {
  flex: 0 0 auto;
  margin-left: auto;
}

@media (min-width: 768px) {
  .filters-grid--single-row .filter-cell {
    flex: 0 0 calc((var(--md-span, 12) / 12) * 100%);
  }
}
</style>
