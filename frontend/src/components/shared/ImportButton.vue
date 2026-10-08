<template>
  <v-dialog v-model="dialog" max-width="600px">
    <template #activator="{ props: activatorProps }">
      <v-btn
        v-bind="activatorProps"
        :color="color"
        :prepend-icon="icon"
        :variant="variant"
      >
        {{ label }}
      </v-btn>
    </template>

    <v-card>
      <v-card-title class="text-h6">
        {{ title }}
      </v-card-title>

      <v-card-text>
        <v-file-input
          v-model="file"
          accept=".xlsx,.xls"
          clearable
          :error-messages="errorMessage"
          label="Fichier Excel (XLSX)"
          prepend-icon="mdi-file-excel"
        />

        <v-alert
          v-if="downloadTemplateUrl"
          class="mt-4"
          type="info"
          variant="tonal"
        >
          <div class="d-flex align-center justify-space-between">
            <span>Besoin d'un template ?</span>
            <export-button
              color="info"
              :endpoint="downloadTemplateUrl"
              :filename="templateFilename"
              label="Télécharger"
              variant="text"
            />
          </div>
        </v-alert>

        <v-alert
          v-if="importResult"
          class="mt-4"
          :type="importResult.errors > 0 ? 'warning' : 'success'"
          variant="tonal"
        >
          <strong>Import terminé:</strong>
          <ul class="mt-2">
            <li>✅ {{ importResult.success }} lignes importées</li>
            <li v-if="importResult.errors > 0">
              ❌ {{ importResult.errors }} erreurs
            </li>
          </ul>

          <v-expansion-panels v-if="importResult.error_details?.length > 0" class="mt-3">
            <v-expansion-panel>
              <v-expansion-panel-title>Détails erreurs</v-expansion-panel-title>
              <v-expansion-panel-text>
                <div
                  v-for="(error, idx) in importResult.error_details"
                  :key="idx"
                  class="mb-2"
                >
                  <strong>Ligne {{ error.row }}:</strong>
                  <ul>
                    <li v-for="(msg, i) in error.errors" :key="i">{{ msg }}</li>
                  </ul>
                </div>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-alert>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn
          variant="text"
          @click="dialog = false"
        >
          Annuler
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!file"
          :loading="loading"
          @click="handleImport"
        >
          Importer
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import apiClient from '@/api/client'
  import ExportButton from './ExportButton.vue'

  type ButtonVariant = 'elevated' | 'flat' | 'tonal' | 'outlined' | 'text' | 'plain'

  interface Props {
    endpoint: string
    downloadTemplateUrl?: string
    templateFilename?: string
    title?: string
    label?: string
    color?: string
    variant?: ButtonVariant
    icon?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Importer fichier Excel',
    label: 'Importer',
    templateFilename: 'template.xlsx',
    color: 'success',
    variant: 'elevated',
    icon: 'mdi-upload',
  })

  const emit = defineEmits<{
    success: [result: any]
    error: [error: Error]
  }>()

  const dialog = ref(false)
  const file = ref<File | null>(null)
  const loading = ref(false)
  const errorMessage = ref('')
  const importResult = ref<any>(null)

  async function handleImport () {
    if (!file.value) {
      errorMessage.value = 'Veuillez sélectionner un fichier'
      return
    }

    loading.value = true
    errorMessage.value = ''
    importResult.value = null

    try {
      const formData = new FormData()
      formData.append('file', file.value)

      const response = await apiClient.post(props.endpoint, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })

      importResult.value = response.data

      if (response.data.errors === 0) {
        emit('success', response.data)
        // Auto-close after 2 seconds if no errors
        setTimeout(() => {
          dialog.value = false
          file.value = null
          importResult.value = null
        }, 2000)
      } else {
        emit('error', new Error(`${response.data.errors} erreurs lors de l'import`))
      }
    } catch (error: any) {
      errorMessage.value = error.response?.data?.message || 'Erreur lors de l\'import'
      emit('error', error)
    } finally {
      loading.value = false
    }
  }
</script>
