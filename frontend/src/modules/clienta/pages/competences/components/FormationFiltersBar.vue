<template>
  <v-card class="mb-6" elevation="1">
    <v-card-text class="pa-4">
      <v-row>
        <v-col cols="12" md="3">
          <v-text-field
            v-model="filters.search"
            clearable
            density="comfortable"
            hide-details
            label="Rechercher"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="filters.status"
            chips
            clearable
            density="comfortable"
            hide-details
            :items="statusOptions"
            label="Statut"
            multiple
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="filters.frequency"
            chips
            clearable
            density="comfortable"
            hide-details
            :items="frequencyOptions"
            label="Récurrence"
            multiple
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="filters.year"
            clearable
            density="comfortable"
            hide-details
            item-title="title"
            item-value="value"
            :items="yearOptions"
            label="Année"
            variant="outlined"
          />
        </v-col>

        <v-col cols="12" md="3">
          <v-select
            v-model="filters.cibles"
            chips
            clearable
            density="comfortable"
            hide-details
            :items="collaboratorOptions"
            label="Collaborateurs"
            multiple
            variant="outlined"
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { FormationFilters } from '../../../types/formation.types'

  defineProps({
    filters: {
      type: Object as PropType<FormationFilters>,
      required: true,
    },
    statusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    frequencyOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    yearOptions: {
      type: Array as PropType<Array<{ title: string, value: number | null }>>,
      required: true,
    },
    collaboratorOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
  })
</script>
