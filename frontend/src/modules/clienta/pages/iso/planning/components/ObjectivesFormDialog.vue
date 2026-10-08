<template>
  <v-dialog v-model="dialogModel" max-width="1200" persistent scrollable>
    <v-card class="dialog-card" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="primary">mdi-target</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">{{ editMode ? 'Modifier l\'objectif' : 'Nouvel objectif' }}</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="handleClose" />
      </v-card-title>

      <v-stepper v-model="currentStepModel" alt-labels class="elevation-0 stepper-container" flat>
        <v-stepper-header class="px-6 pt-6 pb-2 sticky-stepper">
          <v-stepper-item
            color="primary"
            :complete="currentStepModel > 1"
            complete-icon="mdi-check-circle"
            :value="1"
          >
            <template #title>
              <span class="text-subtitle-2 font-weight-bold">Informations</span>
            </template>
            <template #subtitle>
              <span class="text-caption">Détails généraux</span>
            </template>
          </v-stepper-item>
          <v-divider class="mx-2" />
          <v-stepper-item
            color="primary"
            :complete="currentStepModel > 2"
            complete-icon="mdi-check-circle"
            :value="2"
          >
            <template #title>
              <span class="text-subtitle-2 font-weight-bold">Indicateurs</span>
            </template>
            <template #subtitle>
              <span class="text-caption">Mesure & calcul</span>
            </template>
          </v-stepper-item>
          <v-divider class="mx-2" />
          <v-stepper-item
            color="primary"
            :complete="currentStepModel > 3"
            complete-icon="mdi-check-circle"
            :value="3"
          >
            <template #title>
              <span class="text-subtitle-2 font-weight-bold">Réalisation</span>
            </template>
            <template #subtitle>
              <span class="text-caption">Taux d'atteinte</span>
            </template>
          </v-stepper-item>
          <v-divider class="mx-2" />
          <v-stepper-item
            color="primary"
            complete-icon="mdi-check-circle"
            :value="4"
          >
            <template #title>
              <span class="text-subtitle-2 font-weight-bold">Actions</span>
            </template>
            <template #subtitle>
              <span class="text-caption">Plan d'action</span>
            </template>
          </v-stepper-item>
        </v-stepper-header>

        <v-stepper-window class="stepper-window-scrollable">
          <v-stepper-window-item :value="1">
            <v-card-text class="pa-8">
              <div class="mb-6">
                <div class="d-flex align-center mb-4">
                  <v-avatar class="mr-3" color="primary" size="48">
                    <v-icon color="white" size="28">mdi-information</v-icon>
                  </v-avatar>
                  <div>
                    <h3 class="text-h6 font-weight-bold">Informations générales</h3>
                    <p class="text-caption text-medium-emphasis mb-0">Définissez les informations de base de l'objectif</p>
                  </div>
                </div>
                <v-divider class="mb-6" />
              </div>
              <v-row>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.processus"
                    density="comfortable"
                    :disabled="loading"
                    :items="processNameOptions"
                    label="Processus *"
                    prepend-inner-icon="mdi-cog"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="formData.frequence"
                    density="comfortable"
                    :items="['Mensuel', 'Trimestriel', 'Semestriel', 'Annuel']"
                    label="Échéance / Fréquence *"
                    prepend-inner-icon="mdi-calendar-clock"
                    rounded="lg"
                    variant="outlined"
                    @update:model-value="emit('update-realisation')"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.titre"
                    density="comfortable"
                    label="Objectif *"
                    prepend-inner-icon="mdi-target"
                    rounded="lg"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <div class="text-subtitle-2 mb-3">Axe stratégique *</div>
                  <v-select
                    v-model="formData.axesStrategiques"
                    chips
                    closable-chips
                    :items="axisOptions"
                    label="Axes stratégiques"
                    multiple
                    prepend-inner-icon="mdi-sitemap"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <div class="text-subtitle-2 mb-2">Norme(s) concernée(s) (REQ-6.2-01)</div>
                  <v-select
                    v-model="formData.normes"
                    chips
                    closable-chips
                    :items="normOptions || ['ISO 9001 (Qualité)', 'ISO 14001 (Environnement)', 'ISO 45001 (Santé & Sécurité)']"
                    label="Norme(s) rattachée(s)"
                    multiple
                    prepend-inner-icon="mdi-shield-check"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.ressources"
                    density="comfortable"
                    label="Ressources particulières"
                    prepend-inner-icon="mdi-package-variant"
                    rounded="lg"
                    rows="2"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-stepper-window-item>

          <v-stepper-window-item :value="2">
            <v-card-text class="pa-8">
              <div class="mb-6">
                <div class="d-flex align-center mb-4">
                  <v-avatar class="mr-3" color="primary" size="48">
                    <v-icon color="white" size="28">mdi-chart-line</v-icon>
                  </v-avatar>
                  <div>
                    <h3 class="text-h6 font-weight-bold">Indicateurs de performance</h3>
                    <p class="text-caption text-medium-emphasis mb-0">Définissez comment mesurer l'objectif</p>
                  </div>
                </div>
                <v-divider class="mb-6" />
              </div>
              <v-row>
                <v-col cols="12">
                  <v-text-field
                    v-model="formData.indicateur"
                    density="comfortable"
                    label="Indicateur *"
                    placeholder="Ex: Taux de conformité documentaire"
                    prepend-inner-icon="mdi-chart-line"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.modeCalcul"
                    density="comfortable"
                    hint="Décrivez la formule de calcul de l'indicateur"
                    label="Mode de calcul *"
                    persistent-hint
                    prepend-inner-icon="mdi-calculator"
                    rounded="lg"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12">
                  <v-textarea
                    v-model="formData.observation"
                    density="comfortable"
                    label="Observation"
                    prepend-inner-icon="mdi-comment-text"
                    rounded="lg"
                    rows="3"
                    variant="outlined"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-stepper-window-item>

          <v-stepper-window-item :value="3">
            <v-card-text class="pa-8">
              <div class="mb-6">
                <div class="d-flex align-center mb-4">
                  <v-avatar class="mr-3" color="primary" size="48">
                    <v-icon color="white" size="28">mdi-chart-bar</v-icon>
                  </v-avatar>
                  <div>
                    <h3 class="text-h6 font-weight-bold">Taux de réalisation</h3>
                    <p class="text-caption text-medium-emphasis mb-0">Saisissez les performances par période ({{ getPeriodLabel() }})</p>
                  </div>
                </div>
                <v-divider class="mb-6" />
              </div>
              <v-row>
                <v-col
                  v-for="(val, idx) in formData.realisationMensuelle"
                  :key="idx"
                  :cols="getColSize()"
                >
                  <v-text-field
                    v-model.number="formData.realisationMensuelle[idx]"
                    :color="getPerformanceColor(Number(formData.realisationMensuelle[idx]))"
                    density="comfortable"
                    hide-details
                    :label="getPeriodName(idx)"
                    max="100"
                    min="0"
                    suffix="%"
                    type="number"
                    variant="outlined"
                    @update:model-value="clampPeriodValue(idx)"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-stepper-window-item>

          <v-stepper-window-item :value="4">
            <v-card-text class="pa-8">
              <div class="mb-6">
                <div class="d-flex align-center mb-4">
                  <v-avatar class="mr-3" color="primary" size="48">
                    <v-icon color="white" size="28">mdi-playlist-check</v-icon>
                  </v-avatar>
                  <div class="flex-grow-1">
                    <h3 class="text-h6 font-weight-bold">Plan d'action</h3>
                    <p class="text-caption text-medium-emphasis mb-0">Définissez les actions avec leurs responsables</p>
                  </div>
                  <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    variant="elevated"
                    @click="emit('add-action')"
                  >
                    Ajouter
                  </v-btn>
                </div>
                <v-divider class="mb-6" />
              </div>

              <v-alert v-if="formData.actions.length === 0" icon="mdi-information-outline" type="info" variant="tonal">
                Aucune action définie. Cliquez sur "Ajouter" pour créer votre première action.
              </v-alert>

              <v-expansion-panels v-if="formData.actions.length > 0" v-model="openedActionPanel" variant="accordion">
                <v-expansion-panel
                  v-for="(action, index) in formData.actions"
                  :key="index"
                  class="mb-3"
                  rounded="lg"
                  :value="index"
                >
                  <v-expansion-panel-title class="bg-grey-lighten-5">
                    <div class="d-flex align-center justify-space-between w-100">
                      <div class="d-flex align-center" style="gap: 12px;">
                        <v-avatar color="primary" size="32">
                          <span class="text-white">{{ index + 1 }}</span>
                        </v-avatar>
                        <span class="font-weight-medium">{{ action.description || 'Action sans description' }}</span>
                      </div>
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="text"
                        @click.stop="emit('remove-action', index)"
                      />
                    </div>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <v-row class="mt-2">
                      <v-col cols="12">
                        <v-textarea
                          :ref="el => setActionDescriptionRef(el, index)"
                          v-model="action.description"
                          density="comfortable"
                          label="Description de l'action *"
                          rounded="lg"
                          rows="2"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="action.responsableUserId"
                          density="comfortable"
                          item-title="title"
                          item-value="value"
                          :items="collaboratorOptions"
                          label="Responsable"
                          prepend-inner-icon="mdi-account"
                          rounded="lg"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="action.responsablesImpliquesIds"
                          chips
                          closable-chips
                          density="comfortable"
                          item-title="title"
                          item-value="value"
                          :items="collaboratorOptions"
                          label="Responsable(s) impliqué(s)"
                          multiple
                          prepend-inner-icon="mdi-account-multiple"
                          rounded="lg"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="action.delai"
                          density="comfortable"
                          label="Délai (facultatif)"
                          prepend-inner-icon="mdi-calendar"
                          rounded="lg"
                          type="date"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="3">
                        <v-text-field
                          v-model.number="action.progressRate"
                          :disabled="action.statut === 'À faire' || action.statut === 'Terminé'"
                          density="comfortable"
                          label="Taux (%)"
                          max="99"
                          min="0"
                          rounded="lg"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="action.statut"
                          density="comfortable"
                          :items="['À faire', 'En cours', 'Terminé']"
                          label="Statut *"
                          prepend-inner-icon="mdi-flag"
                          rounded="lg"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-card-text>
          </v-stepper-window-item>
        </v-stepper-window>
      </v-stepper>

      <v-divider />

      <v-card-actions class="pa-6 bg-grey-lighten-5">
        <v-btn
          v-if="currentStepModel > 1"
          prepend-icon="mdi-chevron-left"
          size="large"
          variant="outlined"
          @click="currentStepModel = Math.max(1, currentStepModel - 1)"
        >
          Précédent
        </v-btn>
        <v-spacer />
        <v-btn size="large" variant="text" @click="handleClose">Annuler</v-btn>
        <v-btn
          v-if="currentStepModel < 4"
          append-icon="mdi-chevron-right"
          color="primary"
          size="large"
          variant="elevated"
          @click="currentStepModel = Math.min(4, currentStepModel + 1)"
        >
          Suivant
        </v-btn>
        <v-btn
          v-else
          color="success"
          prepend-icon="mdi-check-circle"
          size="large"
          variant="elevated"
          @click="emit('save')"
        >
          {{ editMode ? 'Enregistrer' : 'Créer' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed, ref, watch } from 'vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  interface ActionItem {
    description: string
    responsableUserId?: number
    responsablesImpliquesIds: number[]
    delai: string
    progressRate: number
    statut: string
  }

  interface FormData {
    processus: string
    titre: string
    axeStrategique: string
    axesStrategiques: string[]
    normes?: string[]
    indicateur: string
    modeCalcul: string
    frequence: string
    realisationMensuelle: number[]
    observation: string
    ressources: string
    actionsAMettreEnOeuvre: string
    actions: ActionItem[]
  }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    currentStep: {
      type: Number,
      required: true,
    },
    editMode: {
      type: Boolean,
      required: true,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    normOptions: {
      type: Array as () => string[],
      default: () => ['ISO 9001 (Qualité)', 'ISO 14001 (Environnement)', 'ISO 45001 (Santé & Sécurité)'],
    },
    formData: {
      type: Object as () => FormData,
      required: true,
    },
    axisOptions: {
      type: Array as () => string[],
      required: true,
    },
    processNameOptions: {
      type: Array as () => string[],
      required: true,
    },
    collaboratorOptions: {
      type: Array as () => Array<{ title: string, value: number }>,
      required: true,
    },
    getPerformanceColor: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
    getPeriodLabel: {
      type: Function as PropType<() => string>,
      required: true,
    },
    getPeriodName: {
      type: Function as PropType<(index: number) => string>,
      required: true,
    },
    getColSize: {
      type: Function as PropType<() => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:current-step', value: number): void
    (e: 'update-realisation'): void
    (e: 'add-action'): void
    (e: 'remove-action', index: number): void
    (e: 'save'): void
    (e: 'close'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  const currentStepModel = computed({
    get: () => props.currentStep,
    set: (value: number) => emit('update:current-step', value),
  })

  const openedActionPanel = ref<number | null>(null)
  const actionDescriptionRefs = ref<any[]>([])

  function setActionDescriptionRef (element: any, index: number) {
    actionDescriptionRefs.value[index] = element
  }

  watch(() => props.formData.actions.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      openedActionPanel.value = 0
      void focusTopInsertedField(actionDescriptionRefs.value, 0)
    }
  })

  watch(dialogModel, isOpen => {
    if (isOpen) {
      openedActionPanel.value = props.formData.actions.length > 0 ? 0 : null
    }
  })

  function clampPeriodValue (idx: number) {
    const val = Number(props.formData.realisationMensuelle[idx])
    if (val > 100) {
      props.formData.realisationMensuelle[idx] = 100
    } else if (val < 0) {
      props.formData.realisationMensuelle[idx] = 0
    }
  }

  function handleClose () {
    dialogModel.value = false
    emit('close')
  }
</script>

<style scoped>
  .dialog-card {
    display: flex;
    flex-direction: column;
    max-height: 90vh;
  }

  .sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .stepper-container {
    display: flex;
    flex-direction: column;
    flex: 1;
    overflow: hidden;
  }

  .sticky-stepper {
    position: sticky;
    top: 0;
    z-index: 9;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }

  .stepper-window-scrollable {
    overflow-y: auto;
    max-height: calc(90vh - 280px);
  }

  .stepper-window-scrollable::-webkit-scrollbar {
    width: 8px;
  }

  .stepper-window-scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
  }

  .stepper-window-scrollable::-webkit-scrollbar-thumb {
    background: #5b8dd9;
    border-radius: 4px;
  }

  .stepper-window-scrollable::-webkit-scrollbar-thumb:hover {
    background: #4a7bc8;
  }
</style>
