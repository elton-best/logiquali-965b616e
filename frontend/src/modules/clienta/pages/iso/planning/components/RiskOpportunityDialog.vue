<template>
  <v-dialog v-model="dialogModel" max-width="980" persistent>
    <v-card class="ro-dialog" rounded="xl">
      <v-card-title class="pa-5 pb-3">
        <div class="d-flex align-center justify-space-between flex-wrap ga-2">
          <div>
            <div class="text-overline ro-kicker mb-1">Gestion des {{ viewMode === 'risque' ? 'risques' : 'opportunités' }}</div>
            <div class="text-h6 font-weight-bold">
              {{ isEditing ? 'Modifier' : 'Créer' }} {{ viewMode === 'risque' ? 'un risque' : 'une opportunité' }}
            </div>
          </div>
          <v-btn icon="mdi-close" size="small" variant="text" @click="dialogModel = false" />
        </div>

        <v-stepper v-model="step" alt-labels class="mt-4 ro-stepper" flat>
          <v-stepper-header>
            <v-stepper-item subtitle="Données de base" title="Informations" :value="1" />
            <v-divider />
            <v-stepper-item subtitle="Plan d'actions" title="Actions" :value="2" />
          </v-stepper-header>
        </v-stepper>
      </v-card-title>

      <v-card-text class="px-5 pb-2">
        <v-window v-model="step">
          <v-window-item :value="1">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.process_id"
                  item-title="title"
                  item-value="value"
                  :items="processOptions"
                  label="Processus *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.status"
                  item-title="label"
                  item-value="value"
                  :items="statusItems"
                  label="Statut *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-if="viewMode === 'risque'"
                  v-model="form.cause"
                  label="Causes profondes"
                  rows="2"
                  variant="outlined"
                />
                <v-textarea
                  v-model="form.description"
                  :hint="viewMode === 'risque' ? 'Optionnelle si les causes profondes sont déjà renseignées.' : undefined"
                  :label="viewMode === 'risque' ? 'Description du risque' : 'Description de l\'opportunité *'"
                  persistent-hint
                  rows="3"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="form.probabilite"
                  :items="[1, 2, 3, 4]"
                  label="Probabilité *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="4">
                <v-select
                  v-model.number="form.gravite"
                  :items="[1, 2, 3, 4]"
                  :label="viewMode === 'risque' ? 'Gravité *' : 'Pertinence *'"
                  variant="outlined"
                />
              </v-col>
              <v-col class="d-flex align-center" cols="12" md="4">
                <v-alert class="w-100" :type="scoreType(form.probabilite * form.gravite)" variant="tonal">
                  Score: <strong>{{ form.probabilite * form.gravite }}</strong>
                </v-alert>
              </v-col>
            </v-row>
          </v-window-item>

          <v-window-item :value="2">
            <div class="d-flex justify-space-between align-center mb-3">
              <div class="text-subtitle-1 font-weight-bold">Actions planifiées</div>
              <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                size="small"
                variant="flat"
                @click="emit('add-action')"
              >Ajouter une action</v-btn>
            </div>

            <v-expansion-panels v-if="form.actions.length > 0" v-model="openedActionPanel" variant="accordion">
              <v-expansion-panel
                v-for="(action, index) in form.actions"
                :key="index"
                class="mb-2 rounded-lg ro-action-panel"
                :value="index"
              >
                <v-expansion-panel-title>
                  <div class="d-flex align-center justify-space-between w-100">
                    <span class="font-weight-medium">Action {{ Number(index) + 1 }}</span>
                    <v-btn
                      color="error"
                      icon="mdi-delete"
                      size="x-small"
                      variant="text"
                      @click.stop="emit('remove-action', Number(index))"
                    />
                  </div>
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <v-row>
                    <v-col cols="12">
                      <v-textarea
                        :ref="el => setActionDescriptionRef(el, Number(index))"
                        v-model="action.description"
                        label="Description de l'action"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="action.responsible_user_id"
                        clearable
                        item-title="title"
                        item-value="value"
                        :items="userOptions"
                        label="Responsable"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-select
                        v-model="action.implicated_user_ids"
                        chips
                        clearable
                        item-title="title"
                        item-value="value"
                        :items="userOptions"
                        label="Responsables impliqués"
                        multiple
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <div class="d-flex align-center ga-2 mb-2">
                        <v-select
                          v-if="!action.showDatePicker"
                          v-model="action.deadline_frequency"
                          clearable
                          item-title="label"
                          item-value="value"
                          :items="frequencyOptions"
                          label="Fréquence"
                          variant="outlined"
                        />
                        <v-text-field
                          v-else
                          v-model="action.deadline_frequency"
                          label="Date limite"
                          placeholder="YYYY-MM-DD"
                          type="date"
                          variant="outlined"
                        />
                        <v-btn
                          :icon="action.showDatePicker ? 'mdi-clock-outline' : 'mdi-calendar'"
                          size="small"
                          variant="tonal"
                          @click="action.showDatePicker = !action.showDatePicker"
                        />
                      </div>
                    </v-col>
                  </v-row>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>

            <v-alert v-else class="mt-2" type="info" variant="tonal">
              Aucune action planifiée. Cliquez sur "Ajouter une action" pour commencer.
            </v-alert>
          </v-window-item>
        </v-window>
      </v-card-text>

      <v-card-actions class="px-5 pb-5">
        <v-btn v-if="step > 1" variant="text" @click="step--">Retour</v-btn>
        <v-spacer />
        <v-btn v-if="step < 2" color="primary" variant="tonal" @click="step++">Continuer</v-btn>
        <v-btn variant="text" @click="dialogModel = false">Annuler</v-btn>
        <v-btn v-if="step === 2" color="primary" :loading="saving" @click="emit('save')">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed, ref, watch } from 'vue'
  import { focusTopInsertedField } from '@/composables/useDynamicTopInsertFocus'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    viewMode: {
      type: String as PropType<'risque' | 'opportunite'>,
      required: true,
    },
    isEditing: {
      type: Boolean,
      default: false,
    },
    form: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    statusItems: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    userOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    frequencyOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    scoreType: {
      type: Function as PropType<(score: number) => 'info' | 'success' | 'error' | 'warning'>,
      required: true,
    },
    saving: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'add-action'): void
    (e: 'remove-action', index: number): void
    (e: 'save'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  const step = ref(1)
  const openedActionPanel = ref<number | null>(null)
  const actionDescriptionRefs = ref<any[]>([])

  function setActionDescriptionRef (element: any, index: number) {
    actionDescriptionRefs.value[index] = element
  }

  watch(dialogModel, isOpen => {
    if (isOpen) {
      step.value = 1
      openedActionPanel.value = props.form.actions.length > 0 ? 0 : null
    }
  })

  watch(() => props.form.actions.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      step.value = 2
      openedActionPanel.value = 0
      void focusTopInsertedField(actionDescriptionRefs.value, 0)
    }
  })
</script>

<style scoped>
.ro-dialog {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(1100px 260px at 0% 0%, rgba(10, 132, 255, 0.12), transparent 65%),
    radial-gradient(900px 220px at 100% 0%, rgba(5, 150, 105, 0.1), transparent 60%),
    rgba(255, 255, 255, 0.98);
}

.ro-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.ro-stepper {
  background: transparent;
}

.ro-action-panel {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.94));
}
</style>
