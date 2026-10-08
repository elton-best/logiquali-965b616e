<template>
  <v-card class="mb-4" elevation="0" rounded="lg">
    <v-card-text>
      <div class="d-flex align-center flex-wrap ga-2 mb-4">
        <v-chip
          :color="selectedAspectId === null ? 'primary' : 'default'"
          variant="tonal"
          @click="emit('update:selectedAspectId', null)"
        >
          Tous les volets
        </v-chip>
        <v-chip
          v-for="aspect in aspects"
          :key="`aspect-chip-${aspect.id}`"
          :color="selectedAspectId === aspect.id ? 'primary' : 'default'"
          variant="tonal"
          @click="emit('update:selectedAspectId', aspect.id)"
        >
          {{ aspect.name }}
        </v-chip>
      </div>
      <v-row>
        <v-col cols="12" md="4">
          <v-text-field
            v-model="searchModel"
            density="comfortable"
            hide-details
            label="Recherche"
            prepend-inner-icon="mdi-magnify"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filterComplianceModel"
            clearable
            density="comfortable"
            hide-details
            item-title="title"
            item-value="value"
            :items="complianceOptions"
            label="État de conformité"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="filterValidityModel"
            clearable
            density="comfortable"
            hide-details
            item-title="title"
            item-value="value"
            :items="validityOptions"
            label="Validité"
            rounded="lg"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
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
        <v-col cols="12" md="2">
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
  import { computed } from 'vue'

  const props = defineProps({
    aspects: {
      type: Array as PropType<Array<{ id: number, name: string }>>,
      required: true,
    },
    selectedAspectId: {
      type: Number as PropType<number | null>,
      default: null,
    },
    search: {
      type: String,
      required: true,
    },
    filterCompliance: {
      type: String as PropType<string | null>,
      default: null,
    },
    filterValidity: {
      type: String as PropType<string | null>,
      default: null,
    },
    complianceOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    validityOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
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
  })

  const emit = defineEmits<{
    (e: 'update:search', value: string): void
    (e: 'update:filterCompliance', value: string | null): void
    (e: 'update:filterValidity', value: string | null): void
    (e: 'update:selectedAspectId', value: number | null): void
    (e: 'reset'): void
    (e: 'refresh'): void
  }>()

  const searchModel = computed({
    get: () => props.search,
    set: (value: string) => emit('update:search', value),
  })

  const filterComplianceModel = computed({
    get: () => props.filterCompliance,
    set: (value: string | null) => emit('update:filterCompliance', value),
  })

  const filterValidityModel = computed({
    get: () => props.filterValidity,
    set: (value: string | null) => emit('update:filterValidity', value),
  })
</script>
