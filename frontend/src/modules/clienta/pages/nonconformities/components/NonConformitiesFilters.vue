<template>
  <FilterCard class="mb-4">
    <v-row>
      <v-col cols="12" md="4">
        <v-text-field
          v-model="filtersValue.search"
          density="comfortable"
          hide-details
          placeholder="Rechercher (réf, processus, exigence, cause...)"
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filtersValue.typeNc"
          density="comfortable"
          hide-details
          :items="typeNcOptions"
          prepend-inner-icon="mdi-alert"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="3">
        <v-select
          v-model="filtersValue.status"
          density="comfortable"
          hide-details
          :items="statusOptions"
          prepend-inner-icon="mdi-traffic-light"
          variant="outlined"
        />
      </v-col>
      <v-col cols="12" md="2">
        <v-select
          v-model="filtersValue.process"
          density="comfortable"
          hide-details
          :items="processFilterOptions"
          prepend-inner-icon="mdi-cog-outline"
          variant="outlined"
        />
      </v-col>
    </v-row>
    <div class="d-flex flex-wrap ga-2 mt-3 align-center">
      <v-chip
        :color="filtersValue.status === 'Ouverte' ? 'error' : undefined"
        :variant="filtersValue.status === 'Ouverte' ? 'flat' : 'tonal'"
        @click="filtersValue.status = 'Ouverte'"
      >
        Ouvertes {{ openCount }}
      </v-chip>
      <v-chip
        :color="filtersValue.status === 'En traitement' ? 'warning' : undefined"
        :variant="filtersValue.status === 'En traitement' ? 'flat' : 'tonal'"
        @click="filtersValue.status = 'En traitement'"
      >
        En traitement {{ inProgressCount }}
      </v-chip>
      <v-chip
        :color="filtersValue.status === 'Clôturée' ? 'success' : undefined"
        :variant="filtersValue.status === 'Clôturée' ? 'flat' : 'tonal'"
        @click="filtersValue.status = 'Clôturée'"
      >
        Clôturées {{ closedCount }}
      </v-chip>
      <v-spacer />
      <v-btn
        :disabled="!hasActiveFilters"
        prepend-icon="mdi-filter-off-outline"
        size="small"
        variant="text"
        @click="emit('reset')"
      >
        Réinitialiser les filtres
      </v-btn>
    </div>
  </FilterCard>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'

  const props = defineProps({
    filters: {
      type: Object as PropType<{ search: string, typeNc: string, status: string, process: string }>,
      required: true,
    },
    typeNcOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    statusOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    processFilterOptions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    hasActiveFilters: {
      type: Boolean,
      required: true,
    },
    openCount: {
      type: Number,
      required: true,
    },
    inProgressCount: {
      type: Number,
      required: true,
    },
    closedCount: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:filters', value: { search: string, typeNc: string, status: string, process: string }): void
    (event: 'reset'): void
  }>()

  const filtersValue = computed({
    get: () => props.filters,
    set: value => emit('update:filters', value),
  })
</script>
