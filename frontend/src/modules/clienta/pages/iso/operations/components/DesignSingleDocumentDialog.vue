<template>
  <v-dialog v-model="dialogModel" max-width="860" persistent scrollable>
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Ajouter un document ISO 8.3</span>
        <v-btn icon="mdi-close" variant="text" @click="dialogModel = false" />
      </v-card-title>
      <v-divider />
      <v-card-text class="pt-6">
        <v-row>
          <v-col cols="12" md="8">
            <v-text-field v-model="form.nom" label="Nom du document *" rounded="lg" variant="outlined" />
          </v-col>
          <v-col cols="12" md="4">
            <v-text-field v-model="form.code" label="Code document (optionnel)" rounded="lg" variant="outlined" />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.type"
              :items="typeOptions"
              label="Type *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="8">
            <v-select
              v-model="form.processus"
              clearable
              item-title="title"
              item-value="value"
              :items="processOptions"
              :label="form.type === 'PRC' || form.type === 'PRD' ? 'Processus lié *' : 'Processus lié'"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.statut"
              :items="statutOptions"
              label="Statut *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="form.etat"
              :items="etatOptions"
              label="État *"
              rounded="lg"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.date_creation"
              label="Date de création *"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="form.validated_at"
              :disabled="form.statut !== 'valide'"
              label="Date de validation"
              rounded="lg"
              type="date"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-text-field
              v-model.number="form.periodicite_revision"
              hint="Nombre de mois avant prochaine révision"
              label="Périodicité de révision"
              persistent-hint
              rounded="lg"
              type="number"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-file-input
              accept=".pdf,.doc,.docx,.xls,.xlsx"
              label="Fichier (PDF, Word, Excel)"
              prepend-icon="mdi-paperclip"
              rounded="lg"
              show-size
              variant="outlined"
              @update:model-value="emit('file-selected', $event)"
            />
          </v-col>
          <v-col cols="12">
            <v-textarea
              v-model="form.description"
              label="Description"
              rounded="lg"
              rows="2"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="dialogModel = false">Annuler</v-btn>
        <v-btn color="primary" :loading="submitting" @click="emit('submit')">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'

  interface SingleForm {
    nom: string
    code: string
    type: string
    processus: string | null
    statut: string
    etat: string
    date_creation: string
    validated_at: string
    periodicite_revision: number | null
    description: string
  }

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    form: {
      type: Object as PropType<SingleForm>,
      required: true,
    },
    typeOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    statutOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    etatOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    processOptions: {
      type: Array as PropType<Array<{ title: string, value: string }>>,
      required: true,
    },
    submitting: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'file-selected', value: File | File[] | null): void
    (e: 'submit'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })
</script>
