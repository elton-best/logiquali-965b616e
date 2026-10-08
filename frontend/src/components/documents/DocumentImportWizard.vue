<template>
  <v-card>
    <!-- Header avec stepper -->
    <v-card-title class="pa-6 pb-0">
      <div class="d-flex align-center justify-space-between w-100 mb-4">
        <v-btn icon size="small" variant="text" @click="emit('close')">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </div>
      <v-stepper v-model="step" alt-labels class="w-100 bg-transparent" flat>
        <v-stepper-header>
          <v-stepper-item :complete="step > 1" title="Type" :value="1" />
          <v-divider />
          <v-stepper-item :complete="step > 2" title="Validation Visuelle" :value="2" />
          <v-divider />
          <v-stepper-item title="Upload" :value="3" />
        </v-stepper-header>
      </v-stepper>
    </v-card-title>

    <v-divider />

    <v-card-text class="pa-6" style="min-height: 380px;">

      <!-- Étape 1 : Upload -->
      <div v-if="step === 1">
        <v-btn-toggle v-model="importMode" class="mb-6 w-100" color="primary" mandatory variant="outlined">
          <v-btn class="flex-1" value="data">
            <v-icon start>mdi-table</v-icon> Données (Excel/CSV)
          </v-btn>
          <v-btn class="flex-1" value="files">
            <v-icon start>mdi-file-document-multiple</v-icon> Fichiers (PDF/Word)
          </v-btn>
        </v-btn-toggle>

        <p v-if="importMode === 'data'" class="text-body-2 text-medium-emphasis mb-4">
          Téléchargez le template, remplissez-le et importez-le ici.
        </p>
        <p v-else class="text-body-2 text-medium-emphasis mb-4">
          Sélectionnez plusieurs fichiers Word, PDF, etc. Le système créera un document pour chaque fichier.
        </p>

        <v-btn
          v-if="importMode === 'data'"
          class="mb-6"
          :loading="downloadingTemplate"
          prepend-icon="mdi-download"
          size="small"
          variant="outlined"
          @click="downloadTemplate"
        >
          Télécharger le template
        </v-btn>

        <v-select
          v-if="!props.siteId"
          v-model="selectedSiteId"
          class="mb-4"
          density="comfortable"
          item-title="name"
          item-value="id"
          :items="sites ?? []"
          label="Site *"
          :rules="[v => !!v || 'Site requis']"
          variant="outlined"
        />

        <v-select
          v-model="selectedDocumentTypeCatalogId"
          class="mb-4"
          density="comfortable"
          item-title="title"
          item-value="value"
          :items="documentTypeOptions"
          label="Type de document *"
          :rules="[v => !!v || 'Type requis']"
          variant="outlined"
        />

        <v-select
          v-model="selectedProcessId"
          class="mb-4"
          density="comfortable"
          item-title="title"
          item-value="value"
          :items="processOptions"
          label="Processus *"
          :rules="[v => !!v || 'Processus requis']"
          variant="outlined"
        />

        <v-file-input
          v-if="importMode === 'data'"
          v-model="selectedFile"
          accept=".xlsx,.xls,.csv"
          density="comfortable"
          label="Fichier Excel ou CSV *"
          prepend-icon="mdi-file-excel"
          :rules="[v => !!v || 'Fichier requis']"
          show-size
          variant="outlined"
          @update:model-value="onFileChange"
        />

        <v-file-input
          v-else
          v-model="selectedFiles"
          accept=".pdf,.doc,.docx"
          density="comfortable"
          label="Fichiers PDF ou Word *"
          multiple
          prepend-icon="mdi-file-document-multiple"
          :rules="[v => (v && v.length > 0) || 'Fichiers requis']"
          show-size
          variant="outlined"
          @update:model-value="onFileChange"
        />

        <v-progress-linear
          v-if="uploadProgress > 0 && uploadProgress < 100"
          class="mt-2"
          color="primary"
          height="6"
          :model-value="uploadProgress"
          rounded
        />



        <v-alert
          v-if="stepError"
          class="mt-4"
          density="compact"
          type="error"
          variant="tonal"
        >
          {{ stepError }}
        </v-alert>
      </div>

      <!-- Étape 2 : Validation -->
      <div v-if="step === 2">
        <div v-if="validating" class="d-flex flex-column align-center justify-center py-8 gap-4">
          <v-progress-circular color="primary" indeterminate size="48" />
          <span class="text-body-2 text-medium-emphasis">Validation en cours…</span>
        </div>

        <div v-else-if="validationResults">
          <v-alert
            v-if="uploadResult"
            class="mb-6"
            density="compact"
            type="success"
            variant="tonal"
          >
            <strong>{{ uploadResult.filename }}</strong> — {{ uploadResult.total_rows }} lignes détectées.
          </v-alert>
          <div class="d-flex gap-4 mb-6">
            <v-card class="flex-1 pa-4 text-center" color="success" variant="tonal">
              <div class="text-h4 font-weight-bold">{{ validationResults.valid_count }}</div>
              <div class="text-caption">Lignes valides</div>
            </v-card>
            <v-card class="flex-1 pa-4 text-center" :color="validationResults.invalid_count > 0 ? 'error' : 'success'" variant="tonal">
              <div class="text-h4 font-weight-bold">{{ validationResults.invalid_count }}</div>
              <div class="text-caption">Lignes invalides</div>
            </v-card>
          </div>

          <v-alert
            v-if="validationResults.invalid_count > 0"
            class="mb-4"
            density="compact"
            type="warning"
            variant="tonal"
          >
            Les lignes invalides seront ignorées lors de l'import.
          </v-alert>

          <!-- Tableau des erreurs -->
          <div v-if="invalidRows.length > 0">
            <p class="text-body-2 font-weight-medium mb-2">Détail des erreurs :</p>
            <v-table class="rounded border" density="compact">
              <thead>
                <tr>
                  <th>Ligne</th>
                  <th>Erreurs</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in invalidRows.slice(0, 10)" :key="row.row_number">
                  <td>{{ row.row_number }}</td>
                  <td>
                    <v-chip
                      v-for="(err, i) in row.errors"
                      :key="i"
                      class="mr-1"
                      color="error"
                      size="x-small"
                      variant="tonal"
                    >
                      {{ err }}
                    </v-chip>
                    <div
                      v-for="(detail, dIndex) in row.error_details ?? []"
                      :key="`detail-${row.row_number}-${dIndex}`"
                      class="text-caption text-medium-emphasis mt-1"
                    >
                      Correction attendue: {{ detail.correction_attendue }}
                    </div>
                  </td>
                </tr>
                <tr v-if="invalidRows.length > 10">
                  <td class="text-caption text-medium-emphasis" colspan="2">
                    … et {{ invalidRows.length - 10 }} autres erreurs
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </div>

        <v-alert
          v-if="stepError"
          class="mt-4"
          density="compact"
          type="error"
          variant="tonal"
        >
          {{ stepError }}
        </v-alert>
      </div>

      <!-- Étape 3 : Upload (Exécution et Résultats) -->
      <div v-if="step === 3">
        <!-- Configuration avant exécution -->
        <div v-if="!importing && !importStats">
          <v-alert class="mb-6" density="compact" type="info" variant="tonal">
            <strong>{{ validationResults?.valid_count ?? 0 }}</strong> documents vont être importés.
            Cette action est réversible via le rollback.
          </v-alert>

          <v-select
            v-model="conflictStrategy"
            class="mb-4"
            density="comfortable"
            :items="[
              { title: 'Mode partiel contrôlé (recommandé)', value: 'skip_invalid' },
              { title: 'Arrêter au premier échec', value: 'stop_on_error' },
            ]"
            item-title="title"
            item-value="value"
            label="Stratégie de conflit"
            variant="outlined"
          />

          <v-alert v-if="stepError" density="compact" type="error" variant="tonal">
            {{ stepError }}
          </v-alert>

          <v-checkbox
            v-model="autoSubmit"
            density="compact"
            hide-details
            label="Soumettre automatiquement les documents au workflow après import"
          />
        </div>

        <!-- Chargement pendant exécution -->
        <div v-if="importing" class="d-flex flex-column align-center justify-center py-8 gap-4">
          <v-progress-circular color="primary" indeterminate size="48" />
          <span class="text-body-2 text-medium-emphasis">Import en cours…</span>
        </div>

        <!-- Résultats après exécution -->
        <div v-if="importStats">
          <v-alert
            class="mb-6"
            :type="importStats.failed > 0 ? 'warning' : 'success'"
            variant="tonal"
          >
            <div class="text-body-1 font-weight-medium">Import terminé</div>
            <div class="mt-1">
              <strong>{{ importStats.imported }}</strong> documents importés
              <span v-if="(importStats.rejected ?? 0) > 0">
                · <strong>{{ importStats.rejected }}</strong> lignes rejetées
              </span>
              <span v-if="importStats.failed > 0">
                · <strong>{{ importStats.failed }}</strong> échecs
              </span>
            </div>
          </v-alert>

          <div v-if="importStats.errors.length > 0">
            <p class="text-body-2 font-weight-medium mb-2">Erreurs d'import :</p>
            <v-table class="rounded border" density="compact">
              <thead>
                <tr>
                  <th>Ligne</th>
                  <th>Erreur</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(err, i) in importStats.errors.slice(0, 10)" :key="i">
                  <td>{{ err.row }}</td>
                  <td>
                    <div>{{ err.error }}</div>
                    <div v-if="err.correction_attendue" class="text-caption text-medium-emphasis">
                      Correction attendue: {{ err.correction_attendue }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </div>

          <v-btn
            v-if="currentImportId && importStats.imported > 0"
            class="mt-4"
            color="warning"
            :loading="rollingBack"
            prepend-icon="mdi-undo"
            size="small"
            variant="outlined"
            @click="rollback"
          >
            Annuler l'import (rollback)
          </v-btn>

          <v-alert
            v-if="rollbackDone"
            class="mt-4"
            density="compact"
            type="success"
            variant="tonal"
          >
            Import annulé — {{ rollbackCount }} documents supprimés.
          </v-alert>
        </div>
      </div>

    </v-card-text>

    <v-divider />

    <!-- Actions -->
    <v-card-actions class="pa-4">
      <v-btn
        v-if="step > 1 && (!importStats || rollbackDone)"
        :disabled="loading"
        variant="text"
        @click="prevStep"
      >
        Retour
      </v-btn>

      <v-spacer />

      <template v-if="step === 1">
        <v-btn
          color="primary"
          :disabled="!canUpload"
          :loading="uploading"
          @click="doUpload"
        >
          Suivant
        </v-btn>
      </template>

      <template v-else-if="step === 2">
        <v-btn
          color="primary"
          :disabled="!validationResults || validationResults.valid_count === 0"
          @click="step = 3"
        >
          Continuer vers l'import
        </v-btn>
      </template>

      <template v-else-if="step === 3">
        <v-btn
          v-if="!importStats"
          color="success"
          :disabled="!validationResults || validationResults.valid_count === 0"
          :loading="importing"
          @click="doExecute"
        >
          Lancer l'import
        </v-btn>
        <v-btn
          v-else
          color="primary"
          @click="emit('completed'); emit('close')"
        >
          Terminer
        </v-btn>
      </template>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { documentImportApi, type ImportStats, type UploadResponse, type ValidationResults } from '@/api/documentImport'
  import { documentTypeCatalogsApi } from '@/api/documents'
  import { processesService } from '@/api/services/processes.service'

  interface Site { id: number, name: string }

  const props = defineProps<{ sites?: Site[], siteId?: number }>()
  const emit = defineEmits<{
    close: []
    completed: []
  }>()

  const step = ref(1)
  const loading = computed(() => uploading.value || validating.value || importing.value || rollingBack.value)

  // Étape 1
  const importMode = ref<'data' | 'files'>('data')
  const selectedFile = ref<File | null>(null)
  const selectedFiles = ref<File[]>([])
  const selectedSiteId = ref<number | null>(null)
  const selectedDocumentTypeCatalogId = ref<number | null>(null)
  const selectedProcessId = ref<number | null>(null)
  const documentTypeOptions = ref<Array<{ title: string, value: number }>>([])
  const processOptions = ref<Array<{ title: string, value: number }>>([])
  const uploading = ref(false)
  const uploadProgress = ref(0)
  const uploadResult = ref<UploadResponse | null>(null)
  const downloadingTemplate = ref(false)
  const stepError = ref<string | null>(null)

  // Étape 2
  const validating = ref(false)
  const validationResults = ref<ValidationResults | null>(null)
  const currentImportId = ref<number | null>(null)

  // Étape 3
  const importing = ref(false)
  const conflictStrategy = ref<'skip_invalid' | 'stop_on_error'>('skip_invalid')
  const autoSubmit = ref(false)

  // Étape 4
  const importStats = ref<ImportStats | null>(null)
  const rollingBack = ref(false)
  const rollbackDone = ref(false)
  const rollbackCount = ref(0)

  const canUpload = computed(() => {
    const hasContext = !!selectedSiteId.value && !!selectedDocumentTypeCatalogId.value && !!selectedProcessId.value
    if (!hasContext) return false
    return importMode.value === 'data'
      ? !!selectedFile.value
      : selectedFiles.value.length > 0
  })

  const invalidRows = computed(() =>
    validationResults.value?.rows.filter(r => !r.is_valid) ?? [],
  )

  function onFileChange () {
    uploadResult.value = null
    validationResults.value = null
    currentImportId.value = null
    importStats.value = null
    stepError.value = null
    uploadProgress.value = 0
  }

  async function downloadTemplate () {
    downloadingTemplate.value = true
    try {
      const res = await documentImportApi.downloadTemplate()
      window.open(res.data.data.download_url, '_blank')
    } catch {
    // erreur gérée par l'intercepteur global
    } finally {
      downloadingTemplate.value = false
    }
  }

  async function doUpload () {
    if (!selectedSiteId.value || !selectedDocumentTypeCatalogId.value || !selectedProcessId.value) return

    if (importMode.value === 'files') {
      if (!selectedFiles.value || selectedFiles.value.length === 0) {
        stepError.value = 'Veuillez sélectionner au moins un fichier.'
        return
      }
      uploading.value = true
      stepError.value = null
      uploadProgress.value = 0
      try {
        const res = await documentImportApi.uploadFiles(
          selectedFiles.value,
          selectedSiteId.value,
          p => {
            uploadProgress.value = p
          },
        )
        uploadResult.value = res.data.data
        currentImportId.value = res.data.data.import_id
        step.value = 2
        await doValidate()
      } catch (error: any) {
        stepError.value = error.response?.data?.message ?? 'Erreur lors de l\'upload des fichiers'
      } finally {
        uploading.value = false
      }
      return
    }

    if (!selectedFile.value) return
    uploading.value = true
    stepError.value = null
    uploadProgress.value = 0
    try {
      const res = await documentImportApi.upload(
        selectedFile.value,
        selectedSiteId.value,
        p => {
          uploadProgress.value = p
        },
      )
      uploadResult.value = res.data.data
      currentImportId.value = res.data.data.import_id
      step.value = 2
      await doValidate()
    } catch (error: any) {
      stepError.value = error.response?.data?.message ?? 'Erreur lors de l\'upload'
    } finally {
      uploading.value = false
    }
  }

  async function doValidate () {
    if (!currentImportId.value) return
    if (!selectedDocumentTypeCatalogId.value || !selectedProcessId.value) return
    validating.value = true
    stepError.value = null
    try {
      const res = await documentImportApi.validate(currentImportId.value, {
        document_type_catalog_id: selectedDocumentTypeCatalogId.value,
        process_id: selectedProcessId.value,
      })
      validationResults.value = res.data.data.validation_results
    } catch (error: any) {
      stepError.value = error.response?.data?.message ?? 'Erreur lors de la validation'
    } finally {
      validating.value = false
    }
  }

  async function doExecute () {
    if (!selectedDocumentTypeCatalogId.value || !selectedProcessId.value) return
    importing.value = true
    stepError.value = null

    if (!currentImportId.value) return
    try {
      const res = await documentImportApi.execute(currentImportId.value, {
        preview_confirmed: true,
        conflict_strategy: conflictStrategy.value,
        code_generation_mode: 'nomenclature_only',
        target_scope: 'site',
        document_type_catalog_id: selectedDocumentTypeCatalogId.value,
        process_id: selectedProcessId.value,
        auto_submit: autoSubmit.value,
      })
      importStats.value = res.data.data.stats
      emit('completed')
    } catch (error: any) {
      stepError.value = error.response?.data?.message ?? 'Erreur lors de l\'import'
    } finally {
      importing.value = false
    }
  }

  async function rollback () {
    if (!currentImportId.value) return
    rollingBack.value = true
    try {
      const res = await documentImportApi.rollback(currentImportId.value)
      rollbackCount.value = res.data.data.deleted_count
      rollbackDone.value = true
      importStats.value = null
      currentImportId.value = null
    } catch {
    // erreur gérée par l'intercepteur global
    } finally {
      rollingBack.value = false
    }
  }

  function prevStep () {
    if (step.value > 1) step.value--
  }

  async function loadDocumentTypes () {
    try {
      const res = await documentTypeCatalogsApi.getAll({ is_active: true, site_id: selectedSiteId.value ?? undefined })
      const items = Array.isArray(res.data) ? res.data : []
      documentTypeOptions.value = items.map((item: any) => ({
        title: `${item.name} (${item.abbreviation})`,
        value: Number(item.id),
      }))
      if (!selectedDocumentTypeCatalogId.value && documentTypeOptions.value.length > 0) {
        selectedDocumentTypeCatalogId.value = documentTypeOptions.value[0].value
      }
    } catch {
      documentTypeOptions.value = []
    }
  }

  async function loadProcesses () {
    try {
      const res = await processesService.getProcesses({ site_id: selectedSiteId.value ?? undefined, per_page: 200 })
      const raw = res as any
      const rows = Array.isArray(raw?.data?.data)
        ? raw.data.data
        : (Array.isArray(raw?.data) ? raw.data : [])
      processOptions.value = rows.map((p: any) => {
        const attrs = p?.attributes ?? {}
        const id = Number(p?.id ?? attrs?.id ?? 0)
        const title = p?.title ?? p?.name ?? attrs?.title ?? attrs?.name ?? 'Processus'
        return {
          title,
          value: id,
        }
      }).filter((p: { value: number }) => Number.isFinite(p.value) && p.value > 0)
      if (!selectedProcessId.value && processOptions.value.length > 0) {
        selectedProcessId.value = processOptions.value[0].value
      }
    } catch {
      processOptions.value = []
    }
  }

  watch(selectedSiteId, async () => {
    selectedDocumentTypeCatalogId.value = null
    selectedProcessId.value = null
    await Promise.all([loadDocumentTypes(), loadProcesses()])
  })

  onMounted(async () => {
    if (props.siteId) {
      selectedSiteId.value = Number(props.siteId)
    }
    await Promise.all([loadDocumentTypes(), loadProcesses()])
  })
</script>
