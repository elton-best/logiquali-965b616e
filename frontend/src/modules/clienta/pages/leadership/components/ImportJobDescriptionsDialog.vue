<template>
  <v-dialog
    max-width="800"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <!-- Stepper pour le workflow 2 étapes -->
      <v-stepper v-model="currentStepIndex" flat>
        <v-stepper-header>
          <v-stepper-item
            :complete="currentStepIndex > 1"
            title="Instructions"
            :value="1"
          />
          <v-divider />
          <v-stepper-item title="Upload" :value="2" />
        </v-stepper-header>

        <v-stepper-window>
          <!-- Étape 1 : Instructions -->
          <v-stepper-window-item :value="1">
            <v-card-text>
              <v-alert class="mb-4" type="info" variant="tonal">
                <template #title>Comment ça marche ?</template>
                <ol class="mt-2">
                  <li>Téléchargez le modèle Excel pré-rempli</li>
                  <li>Remplissez les lignes avec vos fiches de poste</li>
                  <li>Importez le fichier complété</li>
                </ol>
              </v-alert>

              <v-btn
                block
                class="mb-4"
                color="primary"
                :loading="isDownloadingTemplate"
                prepend-icon="mdi-download"
                variant="outlined"
                @click="downloadTemplate"
              >
                Télécharger le modèle Excel
              </v-btn>

              <v-divider class="my-4" />

              <div class="text-body-2 text-medium-emphasis">
                <strong>Colonnes obligatoires :</strong>
                <ul class="mt-2">
                  <li><code>titre_du_poste</code></li>
                  <li><code>mission</code></li>
                </ul>
              </div>
            </v-card-text>

            <v-card-actions>
              <v-spacer />
              <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
              <v-btn color="primary" @click="currentStepIndex = 2">
                Suivant
                <v-icon end>mdi-arrow-right</v-icon>
              </v-btn>
            </v-card-actions>
          </v-stepper-window-item>

          <!-- Étape 2 : Upload -->
          <v-stepper-window-item :value="2">
            <v-card-text>
              <!-- Mode de traitement -->
              <v-radio-group v-model="importMode" class="mb-4">
                <template #label>
                  <div class="text-subtitle-2">Mode de traitement</div>
                </template>
                <v-radio
                  label="Flexible (importer les lignes valides, ignorer les erreurs)"
                  value="flexible"
                />
                <v-radio label="Strict (tout ou rien)" value="strict" />
              </v-radio-group>

              <!-- Zone drag-drop -->
              <v-card
                class="pa-6 text-center"
                :class="{ 'border-primary': isDragging }"
                variant="outlined"
                @dragleave.prevent="isDragging = false"
                @dragover.prevent="isDragging = true"
                @drop.prevent="handleDrop"
              >
                <v-icon
                  color="grey-lighten-1"
                  size="64"
                >mdi-cloud-upload</v-icon>
                <div class="text-h6 mt-4">
                  Glissez-déposez votre fichier ici
                </div>
                <div class="text-body-2 text-medium-emphasis mt-2">ou</div>
                <v-file-input
                  v-model="selectedFile"
                  accept=".xlsx,.xls,.csv"
                  class="mt-4"
                  label="Choisir un fichier"
                  prepend-icon=""
                  prepend-inner-icon="mdi-paperclip"
                  :rules="[
                    (v) => !!v || v === null || 'Fichier requis',
                    (v) => !v || v.size < 50000000 || 'Max 50 MB',
                  ]"
                  variant="outlined"
                  @update:model-value="uploadError = null"
                />
              </v-card>

              <!-- Progress -->
              <v-progress-linear
                v-if="isUploading"
                class="mt-4"
                color="primary"
                height="6"
                :model-value="uploadProgress"
              />

              <!-- Erreur -->
              <v-alert
                v-if="uploadError"
                class="mt-4"
                closable
                type="error"
                variant="tonal"
                @click:close="uploadError = null"
              >
                {{ uploadError }}
              </v-alert>

              <!-- Warning >5000 lignes -->
              <v-alert
                v-if="uploadWarning"
                class="mt-4"
                type="warning"
                variant="tonal"
              >
                {{ uploadWarning }}
              </v-alert>
            </v-card-text>

            <v-card-actions>
              <v-btn variant="text" @click="currentStepIndex = 1">
                <v-icon start>mdi-arrow-left</v-icon>
                Retour
              </v-btn>
              <v-spacer />
              <v-btn
                :disabled="isUploading"
                variant="text"
                @click="closeDialog"
              >
                Annuler
              </v-btn>
              <v-btn
                color="primary"
                :disabled="!selectedFile || isUploading"
                :loading="isUploading"
                @click="handleUpload"
              >
                Lancer l'import
              </v-btn>
            </v-card-actions>
          </v-stepper-window-item>
        </v-stepper-window>
      </v-stepper>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { jobDescriptionImportService } from '@/services/jobDescriptionImportService'

  interface Props {
    modelValue: boolean
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'uploaded', importId: number): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()

  const currentStepIndex = ref(1) // 1 = instructions, 2 = upload
  const importMode = ref<'strict' | 'flexible'>('flexible')
  const selectedFile = ref<File[] | null>(null)
  const isDragging = ref(false)
  const isUploading = ref(false)
  const uploadProgress = ref(0)
  const uploadError = ref<string | null>(null)
  const uploadWarning = ref<string | null>(null)
  const isDownloadingTemplate = ref(false)

  function closeDialog () {
    if (!isUploading.value) {
      resetForm()
      emit('update:modelValue', false)
    }
  }

  function resetForm () {
    currentStepIndex.value = 1
    selectedFile.value = null
    isDragging.value = false
    uploadProgress.value = 0
    uploadError.value = null
    uploadWarning.value = null
  }

  function handleDrop (event: DragEvent) {
    isDragging.value = false

    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
      const file = event.dataTransfer.files[0]

      // Validate file type
      const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/csv',
      ]

      if (
        validTypes.includes(file.type)
        || /\.(xlsx|xls|csv)$/i.test(file.name)
      ) {
        selectedFile.value = [file]
        uploadError.value = null
      } else {
        uploadError.value
          = 'Format de fichier invalide. Veuillez uploader un fichier Excel (.xlsx, .xls) ou CSV.'
      }
    }
  }

  async function handleUpload () {
    if (!selectedFile.value || selectedFile.value.length === 0) return

    isUploading.value = true
    uploadError.value = null
    uploadWarning.value = null
    uploadProgress.value = 0

    try {
      const response = await jobDescriptionImportService.upload(
        selectedFile.value[0],
        importMode.value,
        (progressEvent: any) => {
          uploadProgress.value = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total,
          )
        },
      )

      uploadWarning.value = response.data.warning || null

      // Attendre 500ms pour que l'utilisateur voie 100%
      await new Promise(resolve => setTimeout(resolve, 500))

      emit('uploaded', response.data.import_id)
      closeDialog()
    } catch (error: any) {
      console.error('Upload error:', error)
      uploadError.value
        = error.response?.data?.message || 'Échec de l\'upload du fichier'
    } finally {
      isUploading.value = false
      uploadProgress.value = 0
    }
  }

  async function downloadTemplate () {
    isDownloadingTemplate.value = true

    try {
      const blob = await jobDescriptionImportService.downloadTemplate()
      jobDescriptionImportService.downloadFile(blob, 'modele_fiches_poste.xlsx')
    } catch (error) {
      console.error('Template download error:', error)
      uploadError.value = 'Échec du téléchargement du modèle'
    } finally {
      isDownloadingTemplate.value = false
    }
  }
</script>
