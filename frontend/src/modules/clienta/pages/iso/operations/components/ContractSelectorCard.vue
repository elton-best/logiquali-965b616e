<template>
  <v-card class="mb-4" rounded="xl" variant="tonal">
    <v-card-text>
      <v-row dense>
        <v-col cols="12" md="6">
          <v-select
            v-model="providerValue"
            clearable
            item-title="title"
            item-value="value"
            :items="providerItems"
            label="Choisir un prestataire *"
            prepend-inner-icon="mdi-account-tie-hat"
            variant="outlined"
            @update:model-value="emit('provider-change', $event)"
          />
        </v-col>
        <v-col class="d-flex align-center" cols="12" md="6">
          <v-btn-toggle v-model="modeValue" color="primary" mandatory variant="outlined">
            <v-btn value="upload">
              <v-icon class="mr-1" size="16">mdi-upload</v-icon>
              Importer PDF existant
            </v-btn>
            <v-btn value="generate">
              <v-icon class="mr-1" size="16">mdi-file-document-edit-outline</v-icon>
              Générer depuis formulaire
            </v-btn>
          </v-btn-toggle>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  type ToggleMode = 'upload' | 'generate'

  type ProviderItem = {
    title: string
    value: number
  }

  const props = defineProps({
    providerId: {
      type: Number as PropType<number | null>,
      required: true,
    },
    providerItems: {
      type: Array as PropType<ProviderItem[]>,
      required: true,
    },
    mode: {
      type: String as PropType<ToggleMode>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:providerId', value: number | null): void
    (event: 'update:mode', value: ToggleMode): void
    (event: 'provider-change', value: number | null): void
  }>()

  const providerValue = computed({
    get: () => props.providerId,
    set: value => emit('update:providerId', value),
  })

  const modeValue = computed({
    get: () => props.mode,
    set: value => emit('update:mode', value),
  })
</script>
