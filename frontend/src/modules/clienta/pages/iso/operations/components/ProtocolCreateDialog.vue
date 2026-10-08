<template>
  <v-dialog v-model="dialogModel" max-width="1100" persistent scrollable>
    <v-card class="dialog-card" rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between pa-6 bg-primary sticky-header">
        <div class="d-flex align-center" style="gap: 12px;">
          <v-avatar color="white" size="40">
            <v-icon color="primary">mdi-file-document-plus-outline</v-icon>
          </v-avatar>
          <span class="text-h5 text-white">Nouveau protocole</span>
        </div>
        <v-btn color="white" icon="mdi-close" variant="text" @click="emit('close')" />
      </v-card-title>

      <v-card-text class="pa-8 form-scrollable">
        <v-row>
          <v-col cols="12" md="6">
            <v-select
              v-model="form.processId"
              item-title="title"
              item-value="value"
              :items="processOptions"
              label="Processus *"
              prepend-inner-icon="mdi-source-branch"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.protocolName"
              label="Nom du protocole *"
              prepend-inner-icon="mdi-shield-check-outline"
              rounded="lg"
              variant="outlined"
            />
          </v-col>

          <v-col cols="12" md="6">
            <v-select
              v-model="form.procedureKey"
              :disabled="!form.processId"
              item-title="title"
              item-value="value"
              :items="proceduresForSelectedProcess"
              label="Procédure (optionnel)"
              prepend-inner-icon="mdi-file-document-multiple-outline"
              rounded="lg"
              variant="outlined"
            />
            <div class="d-flex align-center justify-space-between mt-1">
              <span class="text-caption text-medium-emphasis">Procédure absente ? Téléversez-la ici.</span>
              <v-btn
                density="comfortable"
                prepend-icon="mdi-upload"
                size="small"
                variant="text"
                @click="emit('open-procedure', form.processId)"
              >
                Ajouter une procédure
              </v-btn>
            </div>
          </v-col>
          <v-col cols="12" md="6">
            <v-select
              v-model="form.activityId"
              :disabled="!form.processId"
              item-title="title"
              item-value="value"
              :items="activitiesForSelectedProcess"
              label="Activité (optionnel)"
              prepend-inner-icon="mdi-format-list-bulleted-square"
              rounded="lg"
              variant="outlined"
            />
            <div v-if="form.processId" class="text-caption text-medium-emphasis mt-1">
              {{ activitiesForSelectedProcess.length }} activité(s) disponible(s) pour ce processus.
            </div>
          </v-col>

          <v-col cols="12">
            <v-alert density="comfortable" icon="mdi-information-outline" type="info" variant="tonal">
              Règle métier: le protocole doit être lié soit à une procédure, soit à une activité (ou les deux).
            </v-alert>
          </v-col>

          <v-col cols="12">
            <v-file-input
              v-model="form.file"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png"
              label="Fichier protocole *"
              prepend-icon="mdi-paperclip"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions class="pa-6 sticky-footer">
        <v-spacer />
        <v-btn rounded="lg" variant="text" @click="emit('close')">Annuler</v-btn>
        <v-btn
          color="primary"
          :loading="uploading"
          prepend-icon="mdi-content-save"
          rounded="lg"
          @click="emit('submit')"
        >
          Enregistrer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  interface ProtocolForm {
    processId: number | null
    procedureKey: string | null
    activityId: number | null
    protocolName: string
    file: File | null
  }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    form: {
      type: Object as PropType<ProtocolForm>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    proceduresForSelectedProcess: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    activitiesForSelectedProcess: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    uploading: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'submit'): void
    (e: 'close'): void
    (e: 'open-procedure', processId: number | null): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })

  void props
</script>

<style scoped>
.dialog-card {
  max-height: 92vh;
  display: flex;
  flex-direction: column;
}

.sticky-header {
  position: sticky;
  top: 0;
  z-index: 3;
}

.form-scrollable {
  overflow-y: auto;
}

.sticky-footer {
  position: sticky;
  bottom: 0;
  z-index: 2;
  background: white;
  border-top: 1px solid rgba(15, 23, 42, 0.08);
}
</style>
