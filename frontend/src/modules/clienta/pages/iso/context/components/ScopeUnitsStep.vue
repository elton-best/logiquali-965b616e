<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-6">
        <div class="d-flex align-center">
          <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
            <v-icon color="white" size="24">mdi-sitemap</v-icon>
          </v-avatar>
          <div>
            <h2 class="text-h5 font-weight-bold">Unités organisationnelles</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">Ex: Le service accueil-vente</p>
          </div>
        </div>
        <v-btn :color="step?.color" prepend-icon="mdi-plus" @click="emit('add')">
          Ajouter
        </v-btn>
      </div>

      <v-row>
        <v-col v-for="(unit, index) in units" :key="index" cols="12" md="6">
          <v-card class="hover-lift" elevation="2" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center gap-3">
                <v-chip :color="step?.color" size="small">{{ index + 1 }}</v-chip>
                <v-text-field
                  :ref="el => setUnitFieldRef(el, index)"
                  v-model="units[index]"
                  class="flex-grow-1"
                  density="compact"
                  flat
                  hide-details
                  placeholder="Unité organisationnelle"
                  variant="solo"
                />
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="emit('remove', index)"
                />
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { ref, watch } from 'vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  const props = defineProps({
    step: {
      type: Object as PropType<{ label: string, color: string, gradient: string }>,
      required: true,
    },
    units: {
      type: Array as PropType<string[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
    (event: 'remove', index: number): void
  }>()

  const unitFieldRefs = ref<any[]>([])

  function setUnitFieldRef (element: any, index: number) {
    unitFieldRefs.value[index] = element
  }

  watch(() => props.units.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(unitFieldRefs.value, 0)
    }
  })
</script>
