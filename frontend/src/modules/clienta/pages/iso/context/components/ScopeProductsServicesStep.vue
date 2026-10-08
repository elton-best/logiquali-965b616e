<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-6">
        <div class="d-flex align-center">
          <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
            <v-icon color="white" size="24">mdi-package-variant</v-icon>
          </v-avatar>
          <div>
            <h2 class="text-h5 font-weight-bold">Produits et services</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">Ex: L'accueil, le conseil, la vente...</p>
          </div>
        </div>
        <v-btn :color="step?.color" prepend-icon="mdi-plus" @click="emit('add')">
          Ajouter
        </v-btn>
      </div>

      <v-row>
        <v-col v-for="(item, index) in items" :key="index" cols="12" md="6">
          <v-card class="hover-lift" elevation="2" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center gap-3">
                <v-chip :color="step?.color" size="small">{{ index + 1 }}</v-chip>
                <v-text-field
                  :ref="el => setItemFieldRef(el, index)"
                  v-model="items[index]"
                  class="flex-grow-1"
                  density="compact"
                  flat
                  hide-details
                  placeholder="Produit ou service"
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
    items: {
      type: Array as PropType<string[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
    (event: 'remove', index: number): void
  }>()

  const itemFieldRefs = ref<any[]>([])

  function setItemFieldRef (element: any, index: number) {
    itemFieldRefs.value[index] = element
  }

  watch(() => props.items.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(itemFieldRefs.value, 0)
    }
  })
</script>
