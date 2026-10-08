<template>
  <v-card elevation="0" rounded="xl" :style="`background: ${step?.gradient}`">
    <v-card-text class="pa-6">
      <div class="d-flex align-center justify-space-between mb-6">
        <div class="d-flex align-center">
          <v-avatar class="mr-4 elevation-4" :color="step?.color" size="48">
            <v-icon color="white" size="24">mdi-cogs</v-icon>
          </v-avatar>
          <div>
            <h2 class="text-h5 font-weight-bold">Processus du système</h2>
            <p class="text-body-2 text-medium-emphasis mb-0">Liste de tous les processus couverts</p>
          </div>
        </div>
        <v-btn :color="step?.color" prepend-icon="mdi-plus" @click="emit('add')">
          Ajouter
        </v-btn>
      </div>

      <v-row>
        <v-col v-for="({ process, index }, displayIndex) in orderedProcesses" :key="process._id || index" cols="12">
          <v-card class="hover-lift" elevation="2" rounded="lg">
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between mb-3">
                <v-chip :color="step?.color" size="small">Processus #{{ displayIndex + 1 }}</v-chip>
                <v-btn
                  color="error"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="emit('remove', index)"
                />
              </div>
              <v-row dense>
                <v-col cols="12" md="5">
                  <v-text-field
                    :ref="el => setProcessNameFieldRef(el, displayIndex)"
                    v-model="process.name"
                    density="comfortable"
                    hide-details
                    label="Nom du processus"
                    placeholder="Ex: Ressources Humaines"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="process.abbreviation"
                    density="comfortable"
                    hide-details
                    label="Abréviation"
                    maxlength="10"
                    placeholder="Ex: RH"
                    variant="outlined"
                    @input="process.abbreviation = process.abbreviation.toUpperCase()"
                  >
                    <template #append-inner>
                      <v-tooltip text="Sera utilisée pour la codification automatique de vos documents">
                        <template #activator="{ props: tp }">
                          <v-icon v-bind="tp" color="grey" size="small">mdi-information-outline</v-icon>
                        </template>
                      </v-tooltip>
                    </template>
                  </v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="process.type"
                    density="comfortable"
                    hide-details
                    :items="processTypes"
                    label="Type de processus"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-alert
        v-if="processes.length === 0"
        class="mt-4"
        rounded="lg"
        type="warning"
        variant="tonal"
      >
        <v-icon start>mdi-alert</v-icon>
        Aucun processus ajouté. Cliquez sur "Ajouter" pour commencer.
      </v-alert>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed, ref, watch } from 'vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  type ScopeProcess = {
    _id?: string
    name: string
    type: string
    abbreviation?: string
  }

  const props = defineProps({
    step: {
      type: Object as PropType<{ label: string, color: string, gradient: string }>,
      required: true,
    },
    processes: {
      type: Array as PropType<ScopeProcess[]>,
      required: true,
    },
    processTypes: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    getProcessTypeColor: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
    getProcessTypeLabel: {
      type: Function as PropType<(type: string) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'add'): void
    (event: 'remove', index: number): void
  }>()

  const processNameFieldRefs = ref<any[]>([])

  function setProcessNameFieldRef (element: any, index: number) {
    processNameFieldRefs.value[index] = element
  }

  function getProcessOrder (type: string): number {
    if (type === 'management') return 0
    if (type === 'realization') return 1
    if (type === 'support') return 2
    return 99
  }

  const orderedProcesses = computed(() => {
    return props.processes.map((process, index) => {
      if (!process._id) {
        process._id = Math.random().toString(36).substr(2, 9)
      }
      return { process, index }
    })
  })

  watch(() => props.processes.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(processNameFieldRefs.value, 0)
    }
  })
</script>
