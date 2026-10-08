<template>
  <v-card-text class="d-flex flex-wrap ga-3 align-center">
    <v-text-field
      v-model="searchValue"
      class="flex-grow-1"
      clearable
      density="comfortable"
      hide-details
      label="Rechercher (nom, IFU, email, référence)"
      prepend-inner-icon="mdi-magnify"
      variant="outlined"
      @keyup.enter="emit('refresh')"
    />
    <v-btn
      :disabled="!searchValue.trim()"
      prepend-icon="mdi-filter-off"
      variant="outlined"
      @click="emit('reset')"
    >
      Réinitialiser
    </v-btn>
    <v-btn color="secondary" prepend-icon="mdi-refresh" variant="tonal" @click="emit('refresh')">
      Actualiser
    </v-btn>
    <v-btn color="info" prepend-icon="mdi-file-import" variant="tonal" @click="emit('import')">
      Importer Excel
    </v-btn>
    <v-btn
      color="success"
      :loading="loadingExport"
      prepend-icon="mdi-file-excel"
      variant="tonal"
      @click="emit('export')"
    >
      Exporter Excel
    </v-btn>
  </v-card-text>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps({
    search: {
      type: String,
      required: true,
    },
    loadingExport: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:search', value: string): void
    (event: 'refresh'): void
    (event: 'reset'): void
    (event: 'import'): void
    (event: 'export'): void
  }>()

  const searchValue = computed({
    get: () => props.search,
    set: value => emit('update:search', value),
  })
</script>
