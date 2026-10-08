<template>
  <v-dialog
    max-width="900"
    :model-value="modelValue"
    persistent
    scrollable
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Aperçu de l'import</span>
        <v-btn
          icon="mdi-close"
          size="small"
          variant="text"
          @click="closeDialog"
        />
      </v-card-title>

      <v-divider />

      <!-- Loading -->
      <v-card-text v-if="isLoading" class="text-center py-12">
        <v-progress-circular color="primary" indeterminate size="64" />
      </v-card-text>

      <!-- Content -->
      <v-card-text v-else-if="importData" class="pb-0">
        <!-- Informations fichier -->
        <v-card class="mb-4" variant="outlined">
          <v-card-title class="text-subtitle-1">Informations du fichier</v-card-title>
          <v-card-text>
            <v-row dense>
              <v-col cols="6">
                <div class="text-caption text-medium-emphasis">
                  Nom du fichier
                </div>
                <div class="text-body-2 font-weight-medium">
                  {{ importData.file_name }}
                </div>
              </v-col>
              <v-col cols="3">
                <div class="text-caption text-medium-emphasis">Taille</div>
                <div class="text-body-2 font-weight-medium">
                  {{ formatFileSize(importData.file_size) }}
                </div>
              </v-col>
              <v-col cols="3">
                <div class="text-caption text-medium-emphasis">Statut</div>
                <ImportStatusBadge :status="normalizedStatus" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Statistiques (si disponibles) -->
        <v-card v-if="hasStatistics" class="mb-4" variant="outlined">
          <v-card-title class="text-subtitle-1">Statistiques</v-card-title>
          <v-card-text>
            <!-- Progress bar -->
            <div class="mb-4">
              <div class="d-flex justify-space-between text-caption mb-2">
                <span>Progression</span>
                <span>{{ importData.processed_rows }} /
                  {{ importData.total_rows }} lignes</span>
              </div>
              <v-progress-linear
                color="primary"
                height="6"
                :model-value="progressPercentage"
              />
            </div>

            <!-- Stats cards -->
            <v-row>
              <v-col cols="4">
                <v-sheet class="pa-4 rounded text-center" color="success">
                  <div class="text-h4">{{ importData.successful_rows }}</div>
                  <div class="text-caption">Succès</div>
                </v-sheet>
              </v-col>
              <v-col cols="4">
                <v-sheet class="pa-4 rounded text-center" color="error">
                  <div class="text-h4">{{ importData.failed_rows }}</div>
                  <div class="text-caption">Échecs</div>
                </v-sheet>
              </v-col>
              <v-col cols="4">
                <v-sheet class="pa-4 rounded text-center" color="info">
                  <div class="text-h4">{{ importData.success_rate }}%</div>
                  <div class="text-caption">Taux de réussite</div>
                </v-sheet>
              </v-col>
            </v-row>

            <!-- Duration -->
            <div
              v-if="importData.duration_seconds"
              class="mt-4 text-center text-caption text-medium-emphasis"
            >
              Durée : {{ formatDuration(importData.duration_seconds) }}
            </div>
          </v-card-text>
        </v-card>

        <!-- Rapport d'erreurs -->
        <v-alert
          v-if="importData.has_errors && importData.error_report_available"
          class="mb-4"
          type="warning"
          variant="tonal"
        >
          <template #title>Erreurs détectées</template>
          <template #append>
            <v-btn
              :loading="isDownloadingErrors"
              size="small"
              variant="text"
              @click="downloadErrorReport"
            >
              Télécharger le rapport
              <v-icon end>mdi-download</v-icon>
            </v-btn>
          </template>
          {{ importData.failed_rows }} ligne(s) ont échoué. Vous pouvez
          télécharger le rapport d'erreurs pour voir les détails.
        </v-alert>

        <!-- Mode de traitement (si status=pending) -->
        <v-card v-if="importData.status === 'pending'" variant="outlined">
          <v-card-text>
            <v-radio-group v-model="importMode">
              <template #label>
                <div class="text-subtitle-2">Mode de traitement</div>
              </template>
              <v-radio
                label="Flexible (importer les lignes valides, ignorer les erreurs)"
                value="flexible"
              />
              <v-radio
                label="Strict (tout ou rien - annuler si erreurs)"
                value="strict"
              />
            </v-radio-group>
          </v-card-text>
        </v-card>
      </v-card-text>

      <!-- Error -->
      <v-card-text v-else-if="error">
        <v-alert type="error" variant="tonal">
          {{ error }}
        </v-alert>
      </v-card-text>

      <v-divider />

      <v-card-actions>
        <v-btn
          :disabled="isLoading"
          prepend-icon="mdi-refresh"
          variant="text"
          @click="refreshPreview"
        >
          Actualiser
        </v-btn>
        <v-spacer />
        <v-btn variant="text" @click="closeDialog">Fermer</v-btn>
        <v-btn
          v-if="importData && importData.status === 'pending'"
          color="success"
          :loading="isConfirming"
          @click="confirmImport"
        >
          Confirmer l'import
          <v-icon end>mdi-check</v-icon>
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import type { ImportPreviewResponse } from '@/services/jobDescriptionImportService'
  import { computed, ref, watch } from 'vue'
  import { jobDescriptionImportService } from '@/services/jobDescriptionImportService'
  import ImportStatusBadge from './ImportStatusBadge.vue'

  interface Props {
    modelValue: boolean
    importId: number | null
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'confirmed'): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()

  const isLoading = ref(false)
  const isConfirming = ref(false)
  const isDownloadingErrors = ref(false)
  const importData = ref<ImportPreviewResponse['data'] | null>(null)
  const error = ref<string | null>(null)
  const importMode = ref<'strict' | 'flexible'>('flexible')

  const hasStatistics = computed(() => {
    return importData.value && importData.value.processed_rows > 0
  })

  const progressPercentage = computed(() => {
    if (!importData.value || importData.value.total_rows === 0) return 0
    return Math.round(
      (importData.value.processed_rows / importData.value.total_rows) * 100,
    )
  })

  const normalizedStatus = computed<
    'completed' | 'pending' | 'failed' | 'validating' | 'processing'
  >(() => {
    const status = importData.value?.status
    if (
      status === 'completed'
      || status === 'pending'
      || status === 'failed'
      || status === 'validating'
      || status === 'processing'
    ) {
      return status
    }
    return 'pending'
  })

  async function loadPreview () {
    if (!props.importId) return

    isLoading.value = true
    error.value = null

    try {
      const response = await jobDescriptionImportService.preview(props.importId)
      importData.value = response.data
    } catch (error_: any) {
      console.error('Preview error:', error_)
      error.value
        = error_.response?.data?.message || 'Impossible de charger l\'aperçu'
    } finally {
      isLoading.value = false
    }
  }

  function refreshPreview () {
    loadPreview()
  }

  async function confirmImport () {
    if (!props.importId) return

    isConfirming.value = true

    try {
      await jobDescriptionImportService.confirm(props.importId, importMode.value)
      emit('confirmed')
      closeDialog()
    } catch (error_: any) {
      console.error('Confirm error:', error_)
      error.value
        = error_.response?.data?.message || 'Échec de la confirmation de l\'import'
    } finally {
      isConfirming.value = false
    }
  }

  async function downloadErrorReport () {
    if (!props.importId) return

    isDownloadingErrors.value = true

    try {
      const blob = await jobDescriptionImportService.downloadErrorReport(
        props.importId,
      )
      jobDescriptionImportService.downloadFile(
        blob,
        `rapport_erreurs_${props.importId}.xlsx`,
      )
    } catch (error_: any) {
      console.error('Download error report error:', error_)
      error.value
        = error_.response?.data?.message || 'Échec du téléchargement du rapport'
    } finally {
      isDownloadingErrors.value = false
    }
  }

  function closeDialog () {
    importData.value = null
    error.value = null
    emit('update:modelValue', false)
  }

  function formatFileSize (bytes: number): string {
    return jobDescriptionImportService.formatFileSize(bytes)
  }

  function formatDuration (seconds: number): string {
    return jobDescriptionImportService.formatDuration(seconds)
  }

  watch(
    () => props.modelValue,
    newVal => {
      if (newVal && props.importId) {
        loadPreview()
      }
    },
  )
</script>
