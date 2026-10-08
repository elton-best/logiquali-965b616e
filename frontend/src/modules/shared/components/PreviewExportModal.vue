<template>
  <v-dialog
    :model-value="modelValue"
    max-width="960"
    persistent
    scrollable
    transition="dialog-bottom-transition"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-card class="preview-export-card" rounded="xl">
      <!-- En-tête -->
      <v-card-title class="d-flex align-center justify-space-between pa-4 bg-primary text-white">
        <div class="d-flex align-center gap-3">
          <v-avatar color="white" size="36" variant="tonal">
            <v-icon color="white">{{ fileIcon }}</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold text-truncate" style="max-width: 580px;">
              {{ title || 'Prévisualisation avant téléchargement' }}
            </div>
            <div class="text-caption opacity-80">
              Format : {{ fileType.toUpperCase() }} — {{ filename }}
            </div>
          </div>
        </div>

        <v-btn
          color="white"
          density="comfortable"
          icon="mdi-close"
          variant="text"
          @click="handleClose"
        />
      </v-card-title>

      <!-- Corps : Prévisualisation -->
      <v-card-text class="pa-4 preview-body" style="min-height: 480px; max-height: 75vh;">
        <!-- Chargement -->
        <div v-if="loading" class="d-flex flex-column align-center justify-center fill-height py-12">
          <v-progress-circular color="primary" indeterminate size="56" />
          <div class="text-body-1 font-weight-medium mt-4 text-medium-emphasis">
            Génération de la prévisualisation en cours...
          </div>
        </div>

        <!-- Visionneuse PDF -->
        <div v-else-if="fileType === 'pdf' && effectiveBlobUrl" class="pdf-viewer-container">
          <iframe
            class="pdf-iframe"
            :src="effectiveBlobUrl"
            title="Prévisualisation du document"
          />
        </div>

        <!-- Document Word ou Excel : fiche de synthèse & structure -->
        <div v-else class="office-preview-container pa-4">
          <v-alert
            class="mb-4"
            color="info"
            icon="mdi-information-outline"
            variant="tonal"
          >
            <strong>Aperçu du document :</strong> Vérifiez les paramètres et les métadonnées ci-dessous avant de confirmer le téléchargement final du fichier <code>{{ filename }}</code>.
          </v-alert>

          <!-- Carte des Métadonnées -->
          <v-card class="mb-4" rounded="lg" variant="outlined">
            <v-card-title class="text-subtitle-2 font-weight-bold bg-grey-lighten-4 py-2">
              <v-icon class="mr-2" size="18">mdi-file-document-outline</v-icon>
              Informations du document
            </v-card-title>
            <v-card-text class="pa-4">
              <v-row dense>
                <v-col cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis">Nom du fichier</div>
                  <div class="text-body-2 font-weight-bold">{{ filename }}</div>
                </v-col>
                <v-col cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis">Format</div>
                  <v-chip class="font-weight-bold" color="primary" size="small">
                    {{ fileType.toUpperCase() }}
                  </v-chip>
                </v-col>
                <v-col v-if="metadata?.version" cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis">Version</div>
                  <div class="text-body-2 font-weight-bold">{{ metadata.version }}</div>
                </v-col>
                <v-col v-if="metadata?.date" cols="12" sm="6">
                  <div class="text-caption text-medium-emphasis">Date d'effet / Génération</div>
                  <div class="text-body-2 font-weight-bold">{{ metadata.date }}</div>
                </v-col>
              </v-row>

              <!-- Liste des items récapitulatifs personnalisés -->
              <v-divider v-if="metadata?.summaryItems && metadata.summaryItems.length > 0" class="my-3" />
              <div v-if="metadata?.summaryItems && metadata.summaryItems.length > 0">
                <div class="text-caption font-weight-bold mb-2 text-medium-emphasis">Éléments inclus dans l'export :</div>
                <v-chip
                  v-for="(item, idx) in metadata.summaryItems"
                  :key="idx"
                  class="mr-2 mb-2"
                  color="grey-darken-1"
                  size="small"
                  variant="outlined"
                >
                  <strong>{{ item.label }}:</strong>&nbsp;{{ item.value }}
                </v-chip>
              </div>
            </v-card-text>
          </v-card>

          <!-- Conseils et conformité RT-01 -->
          <div class="d-flex align-center gap-2 text-caption text-medium-emphasis mt-2">
            <v-icon color="success" size="16">mdi-check-decagram</v-icon>
            Document conforme aux règles de gestion documentaire BESTQHSE.
          </div>
        </div>
      </v-card-text>

      <v-divider />

      <!-- Barre d'action inférieure -->
      <v-card-actions class="pa-4 justify-space-between bg-grey-lighten-5">
        <v-btn
          color="grey-darken-1"
          variant="text"
          @click="handleClose"
        >
          Annuler
        </v-btn>

        <v-btn
          color="primary"
          :disabled="loading"
          :loading="downloading"
          prepend-icon="mdi-download"
          size="large"
          variant="elevated"
          @click="handleDownload"
        >
          Confirmer et télécharger ({{ fileType.toUpperCase() }})
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed, ref, watch } from 'vue'

  export interface PreviewMetadata {
    version?: string
    date?: string
    summaryItems?: Array<{ label: string, value: string | number }>
  }

  const props = withDefaults(
    defineProps<{
      modelValue: boolean
      title?: string
      filename?: string
      fileType?: 'pdf' | 'docx' | 'xlsx' | 'csv'
      blob?: Blob | null
      blobUrl?: string | null
      loading?: boolean
      metadata?: PreviewMetadata
    }>(),
    {
      title: 'Prévisualisation avant téléchargement',
      filename: 'document',
      fileType: 'pdf',
      blob: null,
      blobUrl: null,
      loading: false,
      metadata: undefined,
    },
  )

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'download'): void
    (e: 'close'): void
  }>()

  const downloading = ref(false)
  const localBlobUrl = ref<string | null>(null)

  // Calcule ou crée le blobUrl local si un Blob est fourni directement
  watch(
    () => props.blob,
    newBlob => {
      if (localBlobUrl.value) {
        URL.revokeObjectURL(localBlobUrl.value)
        localBlobUrl.value = null
      }
      if (newBlob) {
        localBlobUrl.value = URL.createObjectURL(newBlob)
      }
    },
    { immediate: true },
  )

  const effectiveBlobUrl = computed(() => props.blobUrl || localBlobUrl.value)

  const fileIcon = computed(() => {
    switch (props.fileType) {
      case 'docx':
        return 'mdi-file-word'
      case 'xlsx':
      case 'csv':
        return 'mdi-file-excel'
      case 'pdf':
      default:
        return 'mdi-file-pdf-box'
    }
  })

  function handleClose () {
    emit('update:modelValue', false)
    emit('close')
  }

  function handleDownload () {
    downloading.value = true
    try {
      emit('download')

      // Si un blob ou blobUrl existe, déclencher le téléchargement navigateur natif
      if (effectiveBlobUrl.value) {
        const link = document.createElement('a')
        link.href = effectiveBlobUrl.value
        link.download = props.filename.endsWith(`.${props.fileType}`)
          ? props.filename
          : `${props.filename}.${props.fileType}`
        document.body.append(link)
        link.click()
        link.remove()
      }
    } finally {
      downloading.value = false
      handleClose()
    }
  }
</script>

<style scoped>
.preview-export-card {
  overflow: hidden;
}

.pdf-viewer-container {
  width: 100%;
  height: 600px;
}

.pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 8px;
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}

.opacity-80 {
  opacity: 0.8;
}
</style>
