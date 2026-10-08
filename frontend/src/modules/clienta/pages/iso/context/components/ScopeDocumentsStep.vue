<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-6">
        <div class="d-flex align-center">
          <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
            <v-icon color="white" size="24">mdi-file-multiple</v-icon>
          </v-avatar>
          <div>
            <h2 class="text-h5 font-weight-bold">Documents référencés</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">Norme ISO 9001:2015, clause 4.3</p>
          </div>
        </div>
        <v-btn :color="step?.color" prepend-icon="mdi-plus" @click="emit('add')">
          Ajouter
        </v-btn>
      </div>

      <v-row>
        <v-col v-for="(doc, index) in documents" :key="index" cols="12">
          <v-card class="hover-lift" elevation="2" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center gap-3">
                <v-chip :color="step?.color" size="small">{{ index + 1 }}</v-chip>
                <v-text-field
                  :ref="el => setDocumentFieldRef(el, index)"
                  v-model="documents[index]"
                  class="flex-grow-1"
                  density="compact"
                  flat
                  hide-details
                  placeholder="Référence et titre du document"
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

      <v-alert
        v-if="documents.length === 0"
        class="mt-4"
        rounded="lg"
        type="info"
        variant="tonal"
      >
        <v-icon start>mdi-information</v-icon>
        Aucun document référencé. Cliquez sur "Ajouter" pour commencer.
      </v-alert>
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
    documents: {
      type: Array as PropType<string[]>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
    (event: 'remove', index: number): void
  }>()

  const documentFieldRefs = ref<any[]>([])

  function setDocumentFieldRef (element: any, index: number) {
    documentFieldRefs.value[index] = element
  }

  watch(() => props.documents.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(documentFieldRefs.value, 0)
    }
  })
</script>
