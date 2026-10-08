<template>
  <FilterCard class="mb-4">
    <v-row>
      <v-col cols="12" md="3">
        <v-text-field
          v-model="filters.search"
          density="comfortable"
          hide-details
          placeholder="Rechercher (titre, description, collaborateur...)"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filters.status"
          density="comfortable"
          hide-details
          :items="statusOptions"
          prepend-inner-icon="mdi-traffic-light"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.process"
          density="comfortable"
          hide-details
          :items="processOptions"
          prepend-inner-icon="mdi-cog-outline"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filters.impact"
          density="comfortable"
          hide-details
          :items="impactOptions"
          prepend-inner-icon="mdi-chart-line"
          variant="outlined"
        />
      </v-col>
      <v-col class="d-flex align-center justify-end" cols="12" md="2">
        <v-btn
          class="mr-2"
          :disabled="!hasActiveFilters"
          prepend-icon="mdi-filter-off"
          variant="outlined"
          @click="emit('reset')"
        >
          Réinitialiser
        </v-btn>
      </v-col>
      <v-col class="d-flex align-center justify-end" cols="12" md="2">
        <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('create')">
          Ajouter
        </v-btn>
      </v-col>
    </v-row>
  </FilterCard>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'

  defineProps({
    filters: {
      type: Object as PropType<{ search: string, status: string, process: string, impact: string }>,
      required: true,
    },
    statusOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    impactOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'reset'): void
    (event: 'create'): void
  }>()
</script>
