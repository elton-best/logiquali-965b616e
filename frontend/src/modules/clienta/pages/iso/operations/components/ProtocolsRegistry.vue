<template>
  <v-card class="registry-shell" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
      <div>
        <div class="text-h6 font-weight-bold">Bibliothèque des protocoles</div>
        <div class="text-body-2 text-medium-emphasis">Vue globale, filtrage, prévisualisation, téléchargement et archivage.</div>
      </div>
      <div class="d-flex align-center ga-2 flex-wrap">
        <v-btn-toggle v-model="viewModeModel" color="primary" mandatory variant="outlined">
          <v-btn prepend-icon="mdi-table" value="table">Tableau</v-btn>
          <v-btn prepend-icon="mdi-view-grid-outline" value="grid">Grille</v-btn>
        </v-btn-toggle>
        <v-btn
          color="primary"
          prepend-icon="mdi-file-document-plus-outline"
          rounded="lg"
          variant="tonal"
          @click="emit('open-procedure')"
        >
          Ajouter une procédure
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" rounded="lg" @click="emit('open-protocol')">
          Ajouter un protocole
        </v-btn>
      </div>
    </v-card-title>

    <v-card-text class="pb-2">
      <v-row dense>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="searchModel"
            clearable
            label="Rechercher"
            prepend-inner-icon="mdi-magnify"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="filterProcessModel"
            clearable
            item-title="title"
            item-value="value"
            :items="processOptions"
            label="Filtrer par processus"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="statusFilterModel"
            clearable
            :items="statusOptions"
            label="Statut"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex align-center" cols="12" md="2">
          <v-btn
            block
            :disabled="!hasActiveFilters"
            prepend-icon="mdi-filter-off"
            rounded="lg"
            variant="outlined"
            @click="emit('reset-filters')"
          >
            Réinitialiser
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>

    <v-divider />

    <v-data-table
      v-if="viewModeModel === 'table'"
      :headers="headers"
      :items="filteredProtocols"
      :loading="loading"
    >
      <template #[`item.status`]="{ item }">
        <v-chip :color="item.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal">
          {{ item.status === 'active' ? 'Actif' : 'Archivé' }}
        </v-chip>
      </template>
      <template #[`item.fileSize`]="{ item }">
        {{ formatFileSize(item.fileSize) }}
      </template>
      <template #[`item.uploadedAt`]="{ item }">
        {{ formatDate(item.uploadedAt) }}
      </template>
      <template #[`item.actions`]="{ item }">
        <div class="d-flex ga-1">
          <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="emit('preview', item)" />
          <v-btn icon="mdi-download" size="small" variant="text" @click="emit('download', item)" />
          <v-btn
            v-if="item.status === 'active'"
            color="warning"
            icon="mdi-archive-outline"
            size="small"
            variant="text"
            @click="emit('archive', item)"
          />
        </div>
      </template>
      <template #no-data>
        <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
      </template>
    </v-data-table>

    <v-card-text v-else>
      <v-row dense>
        <v-col
          v-for="item in filteredProtocols"
          :key="item.id"
          cols="12"
          md="6"
          xl="4"
        >
          <v-card class="protocol-card" rounded="lg" variant="outlined">
            <v-card-item>
              <v-card-title class="text-subtitle-1 font-weight-bold">{{ item.name }}</v-card-title>
              <v-card-subtitle>{{ item.fileName }}</v-card-subtitle>
              <template #append>
                <v-chip :color="item.status === 'active' ? 'success' : 'grey'" size="small" variant="tonal">
                  {{ item.status === 'active' ? 'Actif' : 'Archivé' }}
                </v-chip>
              </template>
            </v-card-item>
            <v-divider />
            <v-card-text class="pt-4">
              <div class="meta-row"><span>Processus</span><strong>{{ item.processName }}</strong></div>
              <div class="meta-row"><span>Procédure</span><strong>{{ item.procedureName || '-' }}</strong></div>
              <div class="meta-row"><span>Activité</span><strong>{{ item.activityName || '-' }}</strong></div>
              <div class="meta-row"><span>Taille</span><strong>{{ formatFileSize(item.fileSize) }}</strong></div>
              <div class="meta-row"><span>Téléversé le</span><strong>{{ formatDate(item.uploadedAt) }}</strong></div>
            </v-card-text>
            <v-card-actions>
              <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="emit('preview', item)" />
              <v-btn icon="mdi-download" size="small" variant="text" @click="emit('download', item)" />
              <v-spacer />
              <v-btn
                v-if="item.status === 'active'"
                color="warning"
                prepend-icon="mdi-archive-outline"
                size="small"
                variant="tonal"
                @click="emit('archive', item)"
              >
                Archiver
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>

    <v-card-text v-if="!loading && filteredProtocols.length === 0" class="text-center text-medium-emphasis py-8">
      {{ emptyStateMessage }}
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, type PropType } from 'vue'

  const props = defineProps({
    viewMode: {
      type: String as PropType<'table' | 'grid'>,
      required: true,
    },
    search: {
      type: String,
      required: true,
    },
    filterProcessId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    statusFilter: {
      type: String as PropType<'active' | 'archived' | null>,
      default: null,
    },
    processOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    statusOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    headers: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    filteredProtocols: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    hasActiveFilters: {
      type: Boolean,
      default: false,
    },
    emptyStateMessage: {
      type: String,
      required: true,
    },
    formatFileSize: {
      type: Function as PropType<(bytes: number) => string>,
      required: true,
    },
    formatDate: {
      type: Function as PropType<(value: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:viewMode', value: 'table' | 'grid'): void
    (e: 'update:search', value: string): void
    (e: 'update:filterProcessId', value: number | null): void
    (e: 'update:statusFilter', value: 'active' | 'archived' | null): void
    (e: 'open-procedure'): void
    (e: 'open-protocol'): void
    (e: 'reset-filters'): void
    (e: 'preview', item: any): void
    (e: 'download', item: any): void
    (e: 'archive', item: any): void
  }>()

  const viewModeModel = computed({
    get: () => props.viewMode,
    set: (value: 'table' | 'grid') => emit('update:viewMode', value),
  })

  const searchModel = computed({
    get: () => props.search,
    set: (value: string) => emit('update:search', value),
  })

  const filterProcessModel = computed({
    get: () => props.filterProcessId,
    set: (value: number | null) => emit('update:filterProcessId', value),
  })

  const statusFilterModel = computed({
    get: () => props.statusFilter,
    set: (value: 'active' | 'archived' | null) => emit('update:statusFilter', value),
  })

  void emit
</script>

<style scoped>
.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.protocol-card {
  height: 100%;
  border-color: rgba(15, 23, 42, 0.12);
  background: linear-gradient(180deg, rgba(255, 255, 255, 1), rgba(248, 250, 252, 0.9));
}

.meta-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 6px;
}

.meta-row span {
  color: rgba(15, 23, 42, 0.62);
  font-size: 12px;
}

.meta-row strong {
  color: #0f172a;
  font-size: 13px;
  text-align: right;
}
</style>
