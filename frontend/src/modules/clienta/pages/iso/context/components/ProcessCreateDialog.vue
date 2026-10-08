<template>
  <v-dialog v-model="modelValue" max-width="920" persistent scrollable>
    <v-card class="create-process-modal" rounded="xl">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center justify-space-between w-100">
          <div class="d-flex align-center" style="gap: 12px;">
            <v-avatar color="white" size="40">
              <v-icon color="primary">mdi-sitemap</v-icon>
            </v-avatar>
            <span class="text-h5 text-white">Créer un nouveau processus</span>
          </div>
          <v-btn icon="mdi-close" variant="text" @click="closeCreateModal" />
        </div>
      </v-card-title>

      <v-card-text class="pa-6">
        <div class="create-stepper mb-6">
          <v-btn
            v-for="step in createSteps"
            :key="step.value"
            class="create-stepper-item"
            :class="{ active: createStepValue === step.value, done: createStepValue > step.value }"
            rounded="pill"
            type="button"
            variant="text"
            @click="createStepValue = step.value"
          >
            <span class="create-step-number">{{ step.value }}</span>
            <span class="create-step-label">{{ step.label }}</span>
          </v-btn>
        </div>

        <v-window v-model="createStepValue">
          <v-window-item :value="1">
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="createForm.name"
                  density="comfortable"
                  label="Nom du processus *"
                  placeholder="Ex: Gestion des audits internes"
                  prepend-inner-icon="mdi-shape-outline"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model="createForm.category"
                  density="comfortable"
                  item-title="title"
                  item-value="value"
                  :items="createCategoryOptions"
                  label="Catégorie *"
                  prepend-inner-icon="mdi-format-list-bulleted-type"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="createForm.pilot_id"
                  clearable
                  density="comfortable"
                  item-title="name"
                  item-value="id"
                  :items="collaborators"
                  label="Pilote du processus"
                  prepend-inner-icon="mdi-account-tie-outline"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-autocomplete
                  v-model="createForm.copilot_ids"
                  chips
                  clearable
                  closable-chips
                  density="comfortable"
                  :disabled="collaborators.length === 0"
                  item-title="name"
                  item-value="id"
                  :items="collaborators"
                  label="Copilote(s) du processus"
                  multiple
                  placeholder="Sélectionnez un ou plusieurs copilotes"
                  prepend-inner-icon="mdi-account-multiple-check"
                  :return-object="false"
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="createForm.purpose"
                  auto-grow
                  density="comfortable"
                  label="Finalité / objectif du processus *"
                  placeholder="Décrivez la finalité, la valeur ajoutée et le périmètre du processus..."
                  prepend-inner-icon="mdi-bullseye-arrow"
                  rounded="lg"
                  rows="4"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-window-item>

          <v-window-item :value="2">
            <div class="section-title mb-3">
              <v-icon class="mr-2" color="primary">mdi-swap-horizontal</v-icon>
              Séquences du processus
            </div>
            <v-row class="mb-2">
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Séquences</div>
                      <div class="text-h5 font-weight-bold">{{ createForm.sequences.length }}</div>
                    </div>
                    <v-avatar color="primary" size="36">
                      <v-icon color="white">mdi-layers-triple</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Sous-activités</div>
                      <div class="text-h5 font-weight-bold">
                        {{ createForm.sequences.reduce((acc, seq) => acc + (seq.subActivities?.length || 0), 0) }}
                      </div>
                    </div>
                    <v-avatar color="indigo" size="36">
                      <v-icon color="white">mdi-format-list-bulleted-square</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="sequence-stat-card" elevation="1" rounded="lg">
                  <v-card-text class="py-3 d-flex align-center justify-space-between">
                    <div>
                      <div class="text-caption text-medium-emphasis">Flux documentés</div>
                      <div class="text-h5 font-weight-bold">
                        {{ createForm.sequences.filter(seq => (seq.inputs || seq.outputs || seq.activities)).length }}
                      </div>
                    </div>
                    <v-avatar color="teal" size="36">
                      <v-icon color="white">mdi-source-branch</v-icon>
                    </v-avatar>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-expansion-panels
              v-model="openedPanelsValue"
              class="mb-4"
              multiple
              variant="accordion"
            >
              <v-expansion-panel
                v-for="(sequence, index) in createForm.sequences"
                :key="sequence.uid"
                rounded="xl"
                :value="index"
              >
                <v-expansion-panel-title>
                  <div class="d-flex align-center justify-space-between w-100 pr-2">
                    <div class="d-flex align-center ga-3">
                      <v-avatar class="text-white font-weight-bold" color="primary" size="34">
                        {{ index + 1 }}
                      </v-avatar>
                      <div>
                        <div class="font-weight-bold text-body-1">Séquence {{ index + 1 }}</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ sequence.subActivities?.length || 0 }} sous-activité(s)
                        </div>
                      </div>
                    </div>
                    <div class="d-flex align-center ga-1">
                      <v-btn
                        :disabled="index === 0"
                        icon="mdi-arrow-up"
                        size="x-small"
                        variant="text"
                        @click.stop="moveSequence(index, 'up')"
                      />
                      <v-btn
                        :disabled="index === createForm.sequences.length - 1"
                        icon="mdi-arrow-down"
                        size="x-small"
                        variant="text"
                        @click.stop="moveSequence(index, 'down')"
                      />
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="x-small"
                        variant="tonal"
                        @click.stop="removeSequence(index)"
                      />
                    </div>
                  </div>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <div class="sequence-editor-grid">
                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-arrow-collapse-right</v-icon>
                        Processus fournisseurs
                      </div>
                      <v-select
                        v-model="sequence.supplierProcesses"
                        bg-color="white"
                        chips
                        closable-chips
                        density="comfortable"
                        hide-details
                        item-title="name"
                        item-value="name"
                        :items="getSequenceProcessItems(sequence)"
                        multiple
                        rounded="lg"
                        variant="solo"
                      >
                        <template #chip="{ item, props }">
                          <v-chip v-bind="props" color="info" size="small">
                            {{ item.raw?.name || item.title }}
                          </v-chip>
                        </template>
                      </v-select>
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-database-import</v-icon>
                        Entrées
                      </div>
                      <v-textarea
                        :ref="el => setSequenceInputRef(el, index)"
                        v-model="sequence.inputs"
                        bg-color="white"
                        density="comfortable"
                        hide-details
                        placeholder="Données, documents..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />
                    </div>

                    <div class="sequence-block sequence-block-activity">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-cog-transfer</v-icon>
                        Activité principale
                      </div>
                      <v-textarea
                        v-model="sequence.activities"
                        bg-color="white"
                        class="mb-3"
                        density="comfortable"
                        hide-details
                        placeholder="Action principale réalisée..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />

                      <div class="subactivity-box">
                        <div class="d-flex align-center justify-space-between mb-2">
                          <span class="text-caption font-weight-medium">Sous-activités</span>
                          <v-chip color="primary" size="x-small" variant="tonal">
                            {{ sequence.subActivities?.length || 0 }}
                          </v-chip>
                        </div>
                        <div class="d-flex ga-2 mb-2">
                          <v-text-field
                            v-model="sequence.newSubActivity"
                            density="compact"
                            hide-details
                            placeholder="Ajouter une sous-activité"
                            variant="outlined"
                            @keydown.enter.prevent="addSubActivity(sequence)"
                          />
                          <v-btn
                            color="primary"
                            prepend-icon="mdi-plus"
                            size="small"
                            variant="flat"
                            @click="addSubActivity(sequence)"
                          >
                            Ajouter
                          </v-btn>
                        </div>
                        <div class="d-flex flex-wrap ga-2">
                          <v-chip
                            v-for="(sub, subIndex) in sequence.subActivities"
                            :key="`${index}-sub-${subIndex}`"
                            closable
                            color="primary"
                            size="small"
                            @click:close="removeSubActivity(sequence, subIndex)"
                          >
                            {{ sub }}
                          </v-chip>
                        </div>
                      </div>
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-database-export</v-icon>
                        Sorties
                      </div>
                      <v-textarea
                        v-model="sequence.outputs"
                        bg-color="white"
                        density="comfortable"
                        hide-details
                        placeholder="Résultats, livrables..."
                        rounded="lg"
                        rows="3"
                        variant="solo"
                      />
                    </div>

                    <div class="sequence-block">
                      <div class="sequence-block-title">
                        <v-icon class="mr-1" size="16">mdi-arrow-collapse-left</v-icon>
                        Processus clients
                      </div>
                      <v-select
                        v-model="sequence.clientProcesses"
                        bg-color="white"
                        chips
                        closable-chips
                        density="comfortable"
                        hide-details
                        item-title="name"
                        item-value="name"
                        :items="getSequenceProcessItems(sequence)"
                        multiple
                        rounded="lg"
                        variant="solo"
                      >
                        <template #chip="{ item, props }">
                          <v-chip v-bind="props" color="success" size="small">
                            {{ item.raw?.name || item.title }}
                          </v-chip>
                        </template>
                      </v-select>
                    </div>
                  </div>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              size="small"
              variant="tonal"
              @click="addSequence"
            >
              Ajouter une séquence
            </v-btn>
          </v-window-item>

          <v-window-item :value="3">
            <div class="section-title mb-3">
              <v-icon class="mr-2" color="success">mdi-target-arrow</v-icon>
              Objectifs du processus
            </div>
            <div class="d-flex flex-column ga-3 mb-4">
              <v-row v-for="(objective, index) in createForm.objectives" :key="objective.uid">
                <v-col cols="12" md="5">
                  <v-text-field
                    :ref="el => setObjectiveNameRef(el, index)"
                    v-model="objective.name"
                    density="comfortable"
                    label="Objectif"
                    prepend-inner-icon="mdi-bullseye"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col cols="12" md="5">
                  <v-text-field
                    v-model="objective.indicator"
                    density="comfortable"
                    label="Indicateur"
                    prepend-inner-icon="mdi-finance"
                    rounded="lg"
                    variant="outlined"
                  />
                </v-col>
                <v-col class="d-flex align-center justify-end" cols="12" md="2">
                  <v-btn color="error" icon="mdi-delete-outline" variant="text" @click="removeObjective(index)" />
                </v-col>
              </v-row>
            </div>
            <v-btn
              color="success"
              prepend-icon="mdi-plus"
              size="small"
              variant="tonal"
              @click="addObjective"
            >
              Ajouter un objectif
            </v-btn>
          </v-window-item>

          <v-window-item :value="4">
            <div class="section-title mb-3">
              <v-icon class="mr-2" color="warning">mdi-cube-outline</v-icon>
              Ressources
            </div>
            <v-row>
              <v-col cols="12" md="6">
                <v-combobox
                  v-model="createForm.resources.human"
                  chips
                  density="comfortable"
                  label="Ressources humaines"
                  multiple
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-combobox
                  v-model="createForm.resources.technological"
                  chips
                  density="comfortable"
                  label="Ressources technologiques"
                  multiple
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-combobox
                  v-model="createForm.resources.material"
                  chips
                  density="comfortable"
                  label="Ressources matérielles"
                  multiple
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-combobox
                  v-model="createForm.resources.documentary"
                  chips
                  density="comfortable"
                  label="Ressources documentaires"
                  multiple
                  rounded="lg"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-window-item>

          <v-window-item :value="5">
            <v-row>
              <v-col cols="12" md="6">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h3 class="text-h5 font-weight-bold text-error d-flex align-center">
                    <v-icon class="mr-2">mdi-alert</v-icon>
                    Risques
                  </h3>
                  <v-btn color="error" prepend-icon="mdi-plus" @click="addRisk">
                    Ajouter
                  </v-btn>
                </div>

                <v-card
                  v-for="(risk, index) in createForm.risks"
                  :key="index"
                  class="mb-3"
                  elevation="2"
                  rounded="lg"
                  style="border-left: 4px solid #ef4444;"
                >
                  <v-card-text class="pa-4">
                    <div class="d-flex align-center justify-space-between mb-3">
                      <v-chip class="font-weight-bold" color="error" size="large">Risque {{ index + 1 }}</v-chip>
                      <v-btn
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="tonal"
                        @click="removeRisk(index)"
                      />
                    </div>
                    <v-textarea
                      :ref="el => setRiskDescriptionRef(el, index)"
                      v-model="risk.description"
                      bg-color="white"
                      class="mb-3"
                      label="Description du risque"
                      placeholder="Décrivez le risque..."
                      rounded="lg"
                      rows="3"
                      variant="solo"
                    />
                    <v-select
                      v-model="risk.norms"
                      bg-color="white"
                      chips
                      :disabled="availableNorms.length === 0"
                      :hint="availableNorms.length === 0 ? 'Aucune norme souscrite' : `${availableNorms.length} norme(s) disponible(s)`"
                      item-title="label"
                      item-value="value"
                      :items="availableNorms"
                      label="Normes liées"
                      :loading="availableNorms.length === 0"
                      multiple
                      persistent-hint
                      placeholder="Sélectionnez les normes ISO"
                      prepend-inner-icon="mdi-file-document"
                      rounded="lg"
                    >
                      <template #chip="{ item, props }">
                        <v-chip v-bind="props" color="error" size="small">
                          {{ item.title }}
                        </v-chip>
                      </template>
                    </v-select>
                  </v-card-text>
                </v-card>

                <v-alert v-if="createForm.risks.length === 0" rounded="lg" type="info" variant="tonal">
                  <v-icon size="small" start>mdi-information</v-icon>
                  Aucun risque identifié
                </v-alert>
              </v-col>

              <v-col cols="12" md="6">
                <div class="d-flex align-center justify-space-between mb-4">
                  <h3 class="text-h5 font-weight-bold text-success d-flex align-center">
                    <v-icon class="mr-2">mdi-lightbulb</v-icon>
                    Opportunités
                  </h3>
                  <v-btn color="success" prepend-icon="mdi-plus" @click="addOpportunity">
                    Ajouter
                  </v-btn>
                </div>

                <v-card
                  v-for="(opp, index) in createForm.opportunities"
                  :key="index"
                  class="mb-3"
                  elevation="2"
                  rounded="lg"
                  style="border-left: 4px solid #22c55e;"
                >
                  <v-card-text class="pa-4">
                    <div class="d-flex gap-2">
                      <v-chip class="mt-1" color="success" size="small">{{ index + 1 }}</v-chip>
                      <v-textarea
                        :ref="el => setOpportunityRef(el, index)"
                        v-model="createForm.opportunities[index]"
                        class="flex-grow-1"
                        flat
                        hide-details
                        placeholder="Décrivez l'opportunité..."
                        rows="2"
                        variant="solo"
                      />
                      <v-btn
                        class="mt-1"
                        color="error"
                        icon="mdi-delete"
                        size="small"
                        variant="text"
                        @click="removeOpportunity(index)"
                      />
                    </div>
                  </v-card-text>
                </v-card>

                <v-alert v-if="createForm.opportunities.length === 0" rounded="lg" type="info" variant="tonal">
                  <v-icon size="small" start>mdi-information</v-icon>
                  Aucune opportunité identifiée
                </v-alert>
              </v-col>
            </v-row>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-card-actions class="px-6 py-4">
        <v-btn
          :disabled="createStepValue <= 1 || creatingProcess"
          prepend-icon="mdi-chevron-left"
          variant="text"
          @click="prevCreateStep"
        >
          Précédent
        </v-btn>
        <v-spacer />
        <v-btn variant="text" @click="closeCreateModal">Annuler</v-btn>
        <v-btn
          v-if="createStepValue < createSteps.length"
          color="primary"
          prepend-icon="mdi-chevron-right"
          rounded="lg"
          variant="tonal"
          @click="nextCreateStep"
        >
          Suivant
        </v-btn>
        <v-btn
          v-else
          color="primary"
          :loading="creatingProcess"
          prepend-icon="mdi-check-circle-outline"
          rounded="lg"
          @click="submitCreateProcess"
        >
          Créer le processus
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, type PropType, ref, watch } from 'vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  type Step = { value: number, label: string }

  type Collaborator = { id: number, name: string }

  type NormOption = { value: string, label: string }

  type Sequence = {
    uid: string
    sequenceOrder: number
    supplierProcesses: string[]
    inputs: string
    activities: string
    subActivities: string[]
    newSubActivity: string
    outputs: string
    clientProcesses: string[]
  }

  type Objective = { uid: string, name: string, indicator: string }

  type Risk = { description: string, norms: string[] }

  type CreateForm = {
    name: string
    category: string
    pilot_id: number | null
    copilot_ids: number[]
    purpose: string
    sequences: Sequence[]
    objectives: Objective[]
    resources: {
      human: string[]
      technological: string[]
      material: string[]
      documentary: string[]
    }
    risks: Risk[]
    opportunities: string[]
  }

  type CategoryOption = { title: string, value: string }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    createStep: {
      type: Number,
      required: true,
    },
    createSteps: {
      type: Array as PropType<Step[]>,
      required: true,
    },
    createForm: {
      type: Object as PropType<CreateForm>,
      required: true,
    },
    createCategoryOptions: {
      type: Array as PropType<CategoryOption[]>,
      required: true,
    },
    collaborators: {
      type: Array as PropType<Collaborator[]>,
      required: true,
    },
    availableNorms: {
      type: Array as PropType<NormOption[]>,
      required: true,
    },
    openedSequencePanels: {
      type: Array as PropType<number[]>,
      required: true,
    },
    creatingProcess: {
      type: Boolean,
      required: true,
    },
    getSequenceProcessItems: {
      type: Function as PropType<
        (sequence: Sequence) => Array<{ id: string | number, name: string }>
      >,
      required: true,
    },
    addSequence: {
      type: Function as PropType<() => void>,
      required: true,
    },
    removeSequence: {
      type: Function as PropType<(index: number) => void>,
      required: true,
    },
    moveSequence: {
      type: Function as PropType<
        (index: number, direction: 'up' | 'down') => void
      >,
      required: true,
    },
    addSubActivity: {
      type: Function as PropType<(sequence: Sequence) => void>,
      required: true,
    },
    removeSubActivity: {
      type: Function as PropType<(sequence: Sequence, index: number) => void>,
      required: true,
    },
    addObjective: {
      type: Function as PropType<() => void>,
      required: true,
    },
    removeObjective: {
      type: Function as PropType<(index: number) => void>,
      required: true,
    },
    addRisk: {
      type: Function as PropType<() => void>,
      required: true,
    },
    removeRisk: {
      type: Function as PropType<(index: number) => void>,
      required: true,
    },
    addOpportunity: {
      type: Function as PropType<() => void>,
      required: true,
    },
    removeOpportunity: {
      type: Function as PropType<(index: number) => void>,
      required: true,
    },
    nextCreateStep: {
      type: Function as PropType<() => void>,
      required: true,
    },
    prevCreateStep: {
      type: Function as PropType<() => void>,
      required: true,
    },
    closeCreateModal: {
      type: Function as PropType<() => void>,
      required: true,
    },
    submitCreateProcess: {
      type: Function as PropType<() => void>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:modelValue', value: boolean): void
    (event: 'update:createStep', value: number): void
    (event: 'update:openedSequencePanels', value: number[]): void
  }>()

  const modelValue = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const createStepValue = computed({
    get: () => props.createStep,
    set: (value: number) => emit('update:createStep', value),
  })

  const openedPanelsValue = computed({
    get: () => props.openedSequencePanels,
    set: (value: number[]) => emit('update:openedSequencePanels', value),
  })

  const sequenceInputRefs = ref<any[]>([])
  const objectiveNameRefs = ref<any[]>([])
  const riskDescriptionRefs = ref<any[]>([])
  const opportunityRefs = ref<any[]>([])

  function setSequenceInputRef (element: any, index: number) {
    sequenceInputRefs.value[index] = element
  }

  function setObjectiveNameRef (element: any, index: number) {
    objectiveNameRefs.value[index] = element
  }

  function setRiskDescriptionRef (element: any, index: number) {
    riskDescriptionRefs.value[index] = element
  }

  function setOpportunityRef (element: any, index: number) {
    opportunityRefs.value[index] = element
  }

  watch(() => props.createForm.sequences.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      openedPanelsValue.value = [0]
      void focusTopInsertedField(sequenceInputRefs.value, 0)
    }
  })

  watch(() => props.createForm.objectives.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(objectiveNameRefs.value, 0)
    }
  })

  watch(() => props.createForm.risks.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(riskDescriptionRefs.value, 0)
    }
  })

  watch(() => props.createForm.opportunities.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      void focusTopInsertedField(opportunityRefs.value, 0)
    }
  })
</script>

<style scoped>
.create-process-modal {
  border: 1px solid rgba(148, 163, 184, 0.25);
  background:
    radial-gradient(circle at 0% 0%, rgba(59, 130, 246, 0.08) 0%, rgba(255, 255, 255, 0) 35%),
    radial-gradient(circle at 100% 100%, rgba(16, 185, 129, 0.08) 0%, rgba(255, 255, 255, 0) 30%),
    #fff;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 10;
}

.section-title {
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #0f172a;
  display: flex;
  align-items: center;
}

.create-stepper {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 8px;
}

.create-stepper-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  border-radius: 999px;
  background: #fff;
  padding: 8px 10px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.create-stepper-item:hover {
  border-color: rgb(59, 130, 246);
}

.create-stepper-item.active {
  background: rgb(59, 130, 246);
  border-color: rgb(59, 130, 246);
  color: #fff;
}

.create-stepper-item.done {
  background: rgb(37, 99, 235 / 0.12);
  border-color: rgb(37, 99, 235 / 0.45);
}

.create-step-number {
  width: 22px;
  height: 22px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  background: rgb(15, 23, 42 / 0.08);
}

.create-stepper-item.active .create-step-number {
  background: rgb(255, 255, 255 / 0.22);
}

.create-step-label {
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
}

.sequence-stat-card {
  border: 1px solid rgba(148, 163, 184, 0.24);
}

.sequence-editor-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.sequence-block {
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 12px;
  padding: 12px;
  background: #fff;
}

.sequence-block-activity {
  grid-column: span 2;
}

.sequence-block-title {
  display: flex;
  align-items: center;
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 8px;
}

.subactivity-box {
  border: 1px dashed rgba(59, 130, 246, 0.5);
  border-radius: 10px;
  padding: 10px;
  background: rgba(59, 130, 246, 0.04);
}

@media (max-width: 960px) {
  .create-stepper {
    grid-template-columns: 1fr 1fr;
  }

  .sequence-editor-grid {
    grid-template-columns: 1fr;
  }

  .sequence-block-activity {
    grid-column: auto;
  }
}
</style>
