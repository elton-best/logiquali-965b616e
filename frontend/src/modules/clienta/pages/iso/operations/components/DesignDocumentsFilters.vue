<template>
  <v-card class="mb-4" elevation="0" rounded="lg">
    <v-card-text>
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.search"
            clearable
            density="comfortable"
            hide-details
            label="Recherche (nom, code)"
            prepend-inner-icon="mdi-magnify"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.type"
            clearable
            density="comfortable"
            hide-details
            :items="typeOptions"
            label="Type"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.processus"
            clearable
            density="comfortable"
            hide-details
            item-title="title"
            item-value="value"
            :items="processOptions"
            label="Processus lié"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.statut"
            clearable
            density="comfortable"
            hide-details
            :items="statutOptions"
            label="Statut"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filters.etat"
            clearable
            density="comfortable"
            hide-details
            :items="etatOptions"
            label="État"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex align-center" cols="12" md="1">
          <v-btn
            block
            :disabled="!hasActiveFilters"
            prepend-icon="mdi-filter-off"
            rounded="lg"
            variant="outlined"
            @click="emit('reset')"
          >
            Réinitialiser
          </v-btn>
        </v-col>
        <v-col class="d-flex align-center" cols="12" md="2">
          <v-btn
            block
            color="primary"
            :loading="loading"
            rounded="lg"
            variant="tonal"
            @click="emit('refresh')"
          >
            Actualiser
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  interface FiltersState {
    search: string
    type: string | null
    processus: string | null
    statut: string | null
    etat: string | null
  }

  defineProps({
    filters: {
      type: Object as PropType<FiltersState>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    typeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    statutOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    etatOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'reset'): void
    (e: 'refresh'): void
  }>()

  void emit
</script>
