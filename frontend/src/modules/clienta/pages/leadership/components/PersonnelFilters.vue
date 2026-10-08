<template>
  <v-card class="mb-4" rounded="xl" variant="tonal">
    <v-card-text class="pa-4">
      <v-row dense>
        <v-col cols="12" md="5">
          <v-text-field
            v-model="searchValue"
            clearable
            hide-details
            label="Rechercher un collaborateur"
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="3">
          <v-select
            v-model="roleValue"
            hide-details
            item-title="title"
            item-value="value"
            :items="roleOptions"
            label="Rôle / Poste"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="2">
          <v-select
            v-model="siteValue"
            hide-details
            item-title="title"
            item-value="value"
            :items="siteOptions"
            label="Site"
            variant="outlined"
          />
        </v-col>
        <v-col class="d-flex align-center justify-end" cols="12" md="2">
          <v-btn
            prepend-icon="mdi-refresh"
            variant="outlined"
            @click="emit('reset')"
          >
            Réinitialiser
          </v-btn>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  type FilterOption = { title: string, value: string }

  const props = defineProps<{
    search: string
    role: string
    site: string
    roleOptions: FilterOption[]
    siteOptions: FilterOption[]
  }>()

  const emit = defineEmits<{
    (event: 'update:search', value: string): void
    (event: 'update:role', value: string): void
    (event: 'update:site', value: string): void
    (event: 'reset'): void
  }>()

  const searchValue = computed({
    get: () => props.search,
    set: value => emit('update:search', value || ''),
  })

  const roleValue = computed({
    get: () => props.role,
    set: value => emit('update:role', value || 'all'),
  })

  const siteValue = computed({
    get: () => props.site,
    set: value => emit('update:site', value || 'all'),
  })
</script>
