<template>
  <v-dialog v-model="dialogModel" max-width="1200" persistent scrollable>
    <v-card class="dialog-card" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="primary">mdi-gavel</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">{{ isEditing ? editDialogTitle : createDialogTitle }}</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="handleClose" />
      </v-card-title>

      <v-card-text class="pa-8 form-scrollable">
        <v-row>
          <v-col cols="12" md="8">
            <v-select
              v-model="localForm.aspect_id"
              clearable
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="aspectOptions"
              label="Volet / Aspect *"
              prepend-inner-icon="mdi-shape-outline"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col class="d-flex align-center" cols="12" md="4">
            <v-btn
              block
              :color="localForm.aspect_id ? 'primary' : 'secondary'"
              :prepend-icon="localForm.aspect_id ? 'mdi-pencil' : 'mdi-shape-plus'"
              rounded="lg"
              variant="tonal"
              @click="emit('open-aspect', localForm.aspect_id || undefined)"
            >
              {{ localForm.aspect_id ? 'Modifier le volet/aspect' : 'Nouveau volet/aspect' }}
            </v-btn>
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="localForm.regulatory_reference"
              density="comfortable"
              :label="referenceFieldLabel"
              rounded="lg"
              rows="3"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="localForm.description"
              density="comfortable"
              label="Description / Libellé"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="localForm.applicable_requirement"
              density="comfortable"
              label="Exigence(s) applicable(s)"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-text-field
              v-model="localForm.watch_source"
              density="comfortable"
              label="Source de veille"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="localForm.entry_into_force_date"
              density="comfortable"
              label="Date d'entrée en vigueur"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="localForm.regulatory_change_status"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="changeStatusOptions"
              label="Statut réglementaire"
              rounded="lg"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="4">
            <v-select
              v-model="localForm.validity_status"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="validityOptions"
              label="En vigueur / Obsolète"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="localForm.compliance_status"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="complianceOptions"
              label="État de conformité"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="localForm.evaluation_frequency"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="frequencyOptions"
              label="Fréquence d'évaluation"
              rounded="lg"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <v-textarea
              v-model="localForm.actions_corrective_preventive"
              density="comfortable"
              label="Actions correctives / préventives (globales)"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="localForm.deadline"
              density="comfortable"
              label="Échéance globale"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="localForm.responsible_id"
              density="comfortable"
              item-title="title"
              item-value="value"
              :items="userOptions"
              label="Responsable global"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-textarea
              v-model="localForm.comments"
              density="comfortable"
              label="Commentaires"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12">
            <div class="d-flex align-center justify-space-between mb-2">
              <div class="text-subtitle-1 font-weight-bold">Actions liées au texte</div>
              <v-btn color="primary" prepend-icon="mdi-plus" variant="tonal" @click="emit('add-action')">
                Ajouter action
              </v-btn>
            </div>
            <v-expansion-panels v-model="openedActionPanel" variant="accordion">
              <v-expansion-panel
                v-for="(action, index) in localForm.actions"
                :key="`action-${index}`"
                class="mb-2"
                :value="index"
              >
                <v-expansion-panel-title>
                  {{ action.title || `Action ${Number(index) + 1}` }}
                </v-expansion-panel-title>
                <v-expansion-panel-text>
                  <v-row>
                    <v-col cols="12">
                      <v-textarea
                        :ref="el => setActionTitleRef(el, Number(index))"
                        v-model="action.title"
                        density="comfortable"
                        label="Action *"
                        rounded="lg"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-select
                        v-model="action.responsible_id"
                        density="comfortable"
                        item-title="title"
                        item-value="value"
                        :items="userOptions"
                        label="Responsable"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field
                        v-model="action.due_date"
                        density="comfortable"
                        label="Délai (facultatif)"
                        rounded="lg"
                        type="date"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-select
                        v-model="action.status"
                        density="comfortable"
                        item-title="title"
                        item-value="value"
                        :items="actionStatusOptions"
                        label="Statut"
                        rounded="lg"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea
                        v-model="action.comments"
                        density="comfortable"
                        label="Commentaires action"
                        rounded="lg"
                        rows="2"
                        variant="outlined"
                      />
                    </v-col>
                    <v-col class="d-flex justify-end" cols="12">
                      <v-btn
                        color="error"
                        prepend-icon="mdi-delete"
                        variant="text"
                        @click="emit('remove-action', Number(index))"
                      >
                        Supprimer
                      </v-btn>
                    </v-col>
                  </v-row>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-col>
        </v-row>
      </v-card-text>

      <v-divider />
      <v-card-actions class="pa-6 bg-grey-lighten-5">
        <v-spacer />
        <v-btn size="large" variant="text" @click="handleClose">Annuler</v-btn>
        <v-btn
          color="primary"
          :loading="saving"
          prepend-icon="mdi-content-save"
          size="large"
          variant="elevated"
          @click="emit('save')"
        >
          Enregistrer
        </v-btn>
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
      default: false,
    },
    isEditing: {
      type: Boolean,
      default: false,
    },
    createDialogTitle: {
      type: String,
      required: true,
    },
    editDialogTitle: {
      type: String,
      required: true,
    },
    referenceFieldLabel: {
      type: String,
      required: true,
    },
    form: {
      type: Object as PropType<Record<string, any>>,
      required: true,
    },
    aspectOptions: {
      type: Array as PropType<Array<{ title: string, value: number }>>,
      required: true,
    },
    changeStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    validityOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    complianceOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    frequencyOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    actionStatusOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    userOptions: {
      type: Array as PropType<Array<{ title: string, value: number }>>,
      required: true,
    },
    saving: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'update:form', value: Record<string, any>): void
    (e: 'open-aspect', aspectId?: number): void
    (e: 'add-action'): void
    (e: 'remove-action', index: number): void
    (e: 'save'): void
    (e: 'close'): void
  }>()

  const localForm = ref<Record<string, any>>({ ...props.form })

  watch(() => props.form, newForm => {
    localForm.value = { ...newForm }
  }, { deep: true })

  watch(localForm, newForm => {
    emit('update:form', { ...newForm })
  }, { deep: true })

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  const openedActionPanel = ref<number | null>(null)
  const actionTitleRefs = ref<any[]>([])

  function setActionTitleRef (element: any, index: number) {
    actionTitleRefs.value[index] = element
  }

  watch(() => localForm.value.actions.length, (nextValue, previousValue) => {
    if (nextValue > previousValue) {
      openedActionPanel.value = 0
      void focusTopInsertedField(actionTitleRefs.value, 0)
    }
  })

  watch(dialogModel, isOpen => {
    if (isOpen) {
      openedActionPanel.value = localForm.value.actions.length > 0 ? 0 : null
    }
  })

  function handleClose () {
    emit('update:modelValue', false)
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

.form-scrollable {
  overflow-y: auto;
  max-height: calc(90vh - 170px);
}
</style>
