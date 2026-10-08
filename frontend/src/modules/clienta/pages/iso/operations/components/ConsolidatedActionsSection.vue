<template>
  <div>
    <v-card class="mb-4 filters-card" rounded="xl" variant="tonal">
      <v-card-text class="pa-4">
        <v-row dense>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="searchModel"
              clearable
              hide-details
              label="Rechercher (source, action, responsable, processus...)"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3" sm="6">
            <v-select
              v-model="sourceFilterModel"
              clearable
              hide-details
              item-title="title"
              item-value="value"
              :items="sourceFilterItems"
              label="Source"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3" sm="6">
            <v-select
              v-model="statusFilterModel"
              clearable
              hide-details
              item-title="title"
              item-value="value"
              :items="statusFilterItems"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col class="d-flex justify-end align-center" cols="12" md="12">
            <v-btn
              class="mr-3"
              :disabled="!hasActiveFilters"
              prepend-icon="mdi-filter-off"
              variant="outlined"
              @click="emit('reset')"
            >
              Réinitialiser
            </v-btn>
            <v-btn-toggle
              v-model="viewModeModel"
              color="primary"
              density="comfortable"
              mandatory
              variant="outlined"
            >
              <v-btn value="table">
                <v-icon class="mr-1" size="16">mdi-view-list</v-icon>
                Liste
              </v-btn>
              <v-btn value="grid">
                <v-icon class="mr-1" size="16">mdi-view-grid-outline</v-icon>
                Grille
              </v-btn>
            </v-btn-toggle>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <v-card v-if="viewModeModel === 'table'" rounded="xl">
      <v-data-table
        class="modern-table"
        density="comfortable"
        :headers="actionHeaders"
        :items="filteredActions"
        :items-per-page="25"
        :loading="loading"
      >
        <template #[`item.source_module`]="{ item }">
          <v-chip class="font-weight-medium" size="small" variant="tonal">{{ formatSourceLabel(item.source_module) }}</v-chip>
        </template>
        <template #[`item.title`]="{ item }">
          <div class="action-title-cell">
            <div class="action-title-text">{{ item.title || 'Sans titre' }}</div>
            <div v-if="item.source_type" class="action-meta-line">
              {{ item.source_type }}
            </div>
          </div>
        </template>
        <template #[`item.description`]="{ item }">
          <div class="action-description-cell">
            {{ item.description || '—' }}
          </div>
        </template>
        <template #[`item.priority`]="{ item }">
          <v-chip :color="priorityColor(item.priority)" size="small" variant="flat">
            {{ formatPriorityLabel(item.priority) }}
          </v-chip>
        </template>
        <template #[`item.status`]="{ item }">
          <v-chip size="small" variant="outlined">{{ formatStatusLabel(item.status) }}</v-chip>
        </template>
        <template #[`item.start_date`]="{ item }">
          <span class="font-weight-medium">{{ item.start_date || '—' }}</span>
        </template>
        <template #[`item.progress`]="{ item }">
          <div class="progress-cell">
            <span class="font-weight-medium">{{ item.progress != null ? `${item.progress}%` : '—' }}</span>
            <v-progress-linear
              v-if="item.progress != null"
              class="mt-1"
              color="primary"
              height="6"
              :model-value="Number(item.progress)"
              rounded
            />
          </div>
        </template>
        <template #[`item.due_date`]="{ item }">
          <span class="font-weight-medium">{{ item.due_date || '—' }}</span>
        </template>
        <template #no-data>
          <div class="text-center py-6 text-medium-emphasis">{{ emptyStateMessage }}</div>
        </template>
      </v-data-table>
    </v-card>

    <v-row v-else dense>
      <v-col
        v-for="item in filteredActions"
        :key="item.id"
        cols="12"
        lg="4"
        md="6"
      >
        <v-card class="action-grid-card h-100" rounded="xl" variant="outlined">
          <v-card-text>
            <div class="d-flex justify-space-between align-center mb-2">
              <v-chip class="font-weight-medium" size="small" variant="tonal">{{ formatSourceLabel(item.source_module) }}</v-chip>
              <span class="text-caption text-medium-emphasis">{{ item.due_date || 'Sans échéance' }}</span>
            </div>
            <div class="text-subtitle-1 font-weight-bold mb-1">{{ item.title }}</div>
            <div class="text-caption text-medium-emphasis mb-3">{{ item.source_title }}</div>
            <div class="grid-description mb-3">{{ item.description || 'Aucune description disponible.' }}</div>
            <div class="d-flex flex-wrap ga-2 mb-2">
              <v-chip size="x-small" variant="outlined">Processus: {{ item.process_name || 'N/A' }}</v-chip>
              <v-chip size="x-small" variant="outlined">Resp: {{ item.responsible_name || 'N/A' }}</v-chip>
            </div>
            <div class="grid-meta mb-3">
              <div><strong>Début:</strong> {{ item.start_date || '—' }}</div>
              <div><strong>Avancement:</strong> {{ item.progress != null ? `${item.progress}%` : '—' }}</div>
            </div>
            <v-progress-linear
              v-if="item.progress != null"
              class="mb-3"
              color="primary"
              height="8"
              :model-value="Number(item.progress)"
              rounded
            />
            <div class="d-flex ga-2">
              <v-chip size="small" variant="outlined">{{ formatStatusLabel(item.status) }}</v-chip>
              <v-chip :color="priorityColor(item.priority)" size="small" variant="flat">{{ formatPriorityLabel(item.priority) }}</v-chip>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col v-if="filteredActions.length === 0" cols="12">
        <v-alert density="comfortable" type="info" variant="tonal">
          {{ emptyStateMessage }}
        </v-alert>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  const props = defineProps({
    search: {
      type: String,
      required: true,
    },
    sourceFilter: {
      type: String as PropType<string | null>,
      default: null,
    },
    statusFilter: {
      type: String as PropType<string | null>,
      default: null,
    },
    sourceFilterItems: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    statusFilterItems: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    actionsViewMode: {
      type: String as PropType<'table' | 'grid'>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      default: false,
    },
    actionHeaders: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    filteredActions: {
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
    formatSourceLabel: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    formatPriorityLabel: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    formatStatusLabel: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
    priorityColor: {
      type: Function as PropType<(value: string | null | undefined) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:search', value: string): void
    (e: 'update:sourceFilter' | 'update:statusFilter', value: string | null): void
    (e: 'update:actionsViewMode', value: 'table' | 'grid'): void
    (e: 'reset'): void
  }>()

  const searchModel = computed({
    get: () => props.search,
    set: (value: string) => emit('update:search', value),
  })

  const sourceFilterModel = computed({
    get: () => props.sourceFilter,
    set: (value: string | null) => emit('update:sourceFilter', value),
  })

  const statusFilterModel = computed({
    get: () => props.statusFilter,
    set: (value: string | null) => emit('update:statusFilter', value),
  })

  const viewModeModel = computed({
    get: () => props.actionsViewMode,
    set: (value: 'table' | 'grid') => emit('update:actionsViewMode', value),
  })
</script>

<style scoped>
.modern-table {
  border-radius: 16px;
}

.modern-table :deep(table) {
  min-width: 1440px;
}

.action-title-cell,
.progress-cell {
  min-width: 0;
}

.action-title-text,
.action-description-cell,
.grid-description {
  white-space: normal;
  word-break: break-word;
  line-height: 1.5;
}

.action-description-cell {
  min-width: 280px;
  max-width: 420px;
}

.action-meta-line,
.grid-meta {
  color: rgb(100 116 139);
  font-size: 0.8rem;
}

.grid-meta {
  display: grid;
  gap: 0.35rem;
}

.action-grid-card {
  border-color: rgba(15, 23, 42, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.action-grid-card:hover {
  border-color: rgba(68, 113, 196, 0.45);
  box-shadow: 0 12px 28px rgba(68, 113, 196, 0.14);
  transform: translateY(-2px);
}
</style>
