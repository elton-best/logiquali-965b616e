<template>
  <v-dialog v-model="dialogModel" max-width="1200">
    <v-card rounded="lg">
      <v-card-title class="d-flex align-center justify-space-between">
        <span>Visualisation interne - {{ previewDocumentName }}</span>
        <v-btn icon="mdi-close" variant="text" @click="emit('close')" />
      </v-card-title>
      <v-divider />
      <v-card-text class="pa-0">
        <div v-if="previewLoading" class="pa-8 d-flex align-center justify-center">
          <UnifiedLoader
            class="mx-auto"
            description="Préparation de la visualisation interne du document..."
            title="Chargement de l'aperçu..."
            variant="local"
          />
        </div>
        <div v-else-if="previewObjectUrl">
          <iframe
            v-if="isPreviewPdf"
            :src="previewObjectUrl"
            style="width: 100%; height: 78vh; border: 0;"
            title="Prévisualisation document"
          />
          <div v-else-if="isPreviewImage" class="pa-4 text-center">
            <img alt="Prévisualisation document" :src="previewObjectUrl" style="max-width: 100%; max-height: 75vh;">
          </div>
          <div v-else class="pa-8 text-center">
            <v-icon class="mb-3" color="grey" size="48">mdi-file-document-outline</v-icon>
            <p class="text-body-2 text-medium-emphasis mb-4">
              Prévisualisation non disponible pour ce type de fichier.
            </p>
            <v-btn color="primary" prepend-icon="mdi-download" @click="emit('download')">
              Télécharger le fichier
            </v-btn>
          </div>
        </div>
        <div v-else-if="isPreviewExcel" class="pa-4">
          <v-alert class="mb-3" type="info" variant="tonal">
            Prévisualisation Excel limitée aux 200 premières lignes.
          </v-alert>
          <div style="overflow: auto; max-height: 70vh;">
            <table class="excel-preview-table">
              <tbody>
                <tr v-for="(row, rowIndex) in previewExcelRows" :key="`row-${rowIndex}`">
                  <td
                    v-for="(cell, cellIndex) in row"
                    :key="`cell-${rowIndex}-${cellIndex}`"
                    :class="{ 'excel-head-cell': rowIndex === 0 }"
                  >
                    {{ cell ?? '' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-else class="pa-8 text-center text-medium-emphasis">
          Impossible de charger la prévisualisation.
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    previewDocumentName: {
      type: String,
      required: true,
    },
    previewLoading: {
      type: Boolean,
      default: false,
    },
    previewObjectUrl: {
      type: String,
      default: null,
    },
    isPreviewPdf: {
      type: Boolean,
      default: false,
    },
    isPreviewImage: {
      type: Boolean,
      default: false,
    },
    isPreviewExcel: {
      type: Boolean,
      default: false,
    },
    previewExcelRows: {
      type: Array as () => Array<Array<string | number | boolean | null>>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'close'): void
    (e: 'download'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })
</script>

<style scoped>
  .excel-preview-table {
    border-collapse: collapse;
    width: 100%;
    min-width: 700px;
  }

  .excel-preview-table td {
    border: 1px solid #e2e8f0;
    font-size: 13px;
    padding: 6px 8px;
    white-space: nowrap;
  }

  .excel-head-cell {
    background: #f8fafc;
    font-weight: 700;
  }
</style>
