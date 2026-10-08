<template>
  <v-dialog v-model="dialogModel" max-width="800" persistent>
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-secondary">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="secondary">mdi-download</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">Importer les objectifs des processus</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="dialogModel = false" />
      </v-card-title>

      <v-card-text class="pa-6">
        <v-alert class="mb-4" type="info" variant="tonal">
          <div class="d-flex align-center">
            <v-icon class="mr-2">mdi-information</v-icon>
            <div>
              <div class="font-weight-bold">Importation des objectifs</div>
              <div class="text-caption">Sélectionnez les processus dont vous souhaitez importer les objectifs. Les objectifs seront ajoutés au tableau de bord.</div>
            </div>
          </div>
        </v-alert>

        <v-text-field
          v-model="searchProcessModel"
          class="mb-4"
          density="comfortable"
          hide-details
          placeholder="Rechercher un processus..."
          prepend-inner-icon="mdi-magnify"
          rounded="lg"
          variant="outlined"
        />

        <div class="process-list">
          <v-card
            v-for="process in filteredProcesses"
            :key="process.id"
            class="mb-3 process-card"
            :class="{ 'selected': selectedProcesses.includes(process.id) }"
            elevation="1"
            rounded="lg"
            @click="emit('toggle-process', process.id)"
          >
            <v-card-text class="pa-4">
              <div class="d-flex align-center justify-space-between">
                <div class="d-flex align-center" style="gap: 12px;">
                  <v-checkbox
                    color="primary"
                    hide-details
                    :model-value="selectedProcesses.includes(process.id)"
                    @click.stop="emit('toggle-process', process.id)"
                  />
                  <div>
                    <div class="font-weight-bold">{{ process.name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ process.objectivesCount }} objectif(s)</div>
                  </div>
                </div>
                <v-chip color="primary" size="small" variant="tonal">
                  {{ process.type }}
                </v-chip>
              </div>
            </v-card-text>
          </v-card>
        </div>
      </v-card-text>

      <v-divider />

      <v-card-actions class="pa-6">
        <v-chip color="primary" variant="tonal">
          {{ selectedProcesses.length }} processus sélectionné(s)
        </v-chip>
        <v-spacer />
        <v-btn variant="text" @click="dialogModel = false">Annuler</v-btn>
        <v-btn
          color="primary"
          :disabled="selectedProcesses.length === 0"
          variant="flat"
          @click="emit('import')"
        >
          Importer {{ selectedProcesses.length }} processus
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface ProcessItem {
    id: number
    name: string
    type: string
    objectivesCount: number
  }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    searchProcess: {
      type: String,
      required: true,
    },
    filteredProcesses: {
      type: Array as () => ProcessItem[],
      required: true,
    },
    selectedProcesses: {
      type: Array as () => number[],
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:search-process', value: string): void
    (e: 'toggle-process', id: number): void
    (e: 'import'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  const searchProcessModel = computed({
    get: () => props.searchProcess,
    set: (value: string) => emit('update:search-process', value),
  })
</script>

<style scoped>
  .process-list {
    max-height: 400px;
    overflow-y: auto;
  }

  .process-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
  }

  .process-card:hover {
    border-color: #5b8dd9;
    transform: translateX(4px);
  }

  .process-card.selected {
    border-color: #5b8dd9;
    background-color: rgba(91, 141, 217, 0.05);
  }
</style>
