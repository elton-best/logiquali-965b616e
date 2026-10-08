<template>
  <v-card class="filters-card" elevation="2" rounded="xl">
    <v-card-text class="pa-4">
      <div class="d-flex align-center gap-3 flex-wrap">
        <v-select
          v-model="localFilters.period"
          density="compact"
          hide-details
          :items="periodOptions"
          label="Période"
          prepend-inner-icon="mdi-calendar-range"
          rounded="lg"
          style="max-width: 200px"
          variant="outlined"
          @update:model-value="emitFilters"
        />

        <v-select
          v-model="localFilters.site"
          clearable
          density="compact"
          hide-details
          :items="siteOptions"
          label="Site"
          prepend-inner-icon="mdi-office-building"
          rounded="lg"
          style="max-width: 200px"
          variant="outlined"
          @update:model-value="emitFilters"
        />

        <v-checkbox
          v-model="localFilters.compareMode"
          density="compact"
          hide-details
          label="Comparer avec période précédente"
          @update:model-value="emitFilters"
        />

        <v-spacer />

        <v-btn
          color="primary"
          prepend-icon="mdi-download"
          rounded="lg"
          variant="tonal"
          @click="$emit('export')"
        >
          Exporter
        </v-btn>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue'

  interface Filters {
    period: string
    site: string | number | null
    compareMode: boolean
  }

  const props = defineProps<{
    filters: Filters
    sites?: Array<{ value: string | number, title: string, hasSubscription?: boolean }>
    allowGlobal?: boolean
  }>()

  const emit = defineEmits<{
    'update:filters': [filters: Filters]
    'export': []
  }>()

  const localFilters = ref<Filters>({ ...props.filters })

  const periodOptions = [
    { value: 'today', title: 'Aujourd\'hui' },
    { value: 'week', title: 'Cette semaine' },
    { value: 'month', title: 'Ce mois' },
    { value: 'quarter', title: 'Ce trimestre' },
    { value: 'year', title: 'Cette année' },
    { value: 'custom', title: 'Personnalisé' },
  ]

  const siteOptions = ref<Array<{ value: string | number, title: string }>>([])

  function rebuildSiteOptions () {
    siteOptions.value = [
      ...(props.allowGlobal === false ? [] : [{ value: 'all', title: 'Tous les sites' }]),
      ...((props.sites || []) as Array<{ value: string | number, title: string }>),
    ]
  }

  function emitFilters () {
    emit('update:filters', { ...localFilters.value })
  }

  watch(() => props.filters, newFilters => {
    localFilters.value = { ...newFilters }
  }, { deep: true })

  watch(() => [props.sites, props.allowGlobal], () => {
    rebuildSiteOptions()
  }, { deep: true, immediate: true })
</script>

<style scoped>
.filters-card {
  border: 1px solid rgba(148, 163, 184, 0.15);
}
</style>
