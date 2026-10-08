<template>
  <v-dialog v-model="dialogModel" max-width="1200" scrollable>
    <v-card class="preview-dialog-card" rounded="xl">
      <v-card-title class="preview-header d-flex align-center justify-space-between">
        <div class="d-flex align-center ga-3">
          <v-avatar color="primary" size="38" variant="tonal">
            <v-icon>mdi-file-eye-outline</v-icon>
          </v-avatar>
          <div>
            <div class="text-subtitle-1 font-weight-bold text-truncate">{{ previewTitle || 'Aperçu du protocole' }}</div>
            <div class="text-caption text-medium-emphasis">Prévisualisation intégrée</div>
          </div>
        </div>
        <div class="d-flex align-center ga-2">
          <v-btn
            v-if="previewDownloadId"
            color="primary"
            prepend-icon="mdi-download"
            rounded="lg"
            variant="tonal"
            @click="emit('download')"
          >
            Télécharger
          </v-btn>
          <v-btn icon="mdi-close" variant="text" @click="emit('close')" />
        </div>
      </v-card-title>

      <v-divider />

      <v-card-text class="preview-body pa-0">
        <div v-if="previewLoading" class="preview-state">
          <v-progress-circular color="primary" indeterminate />
          <span>Chargement de l’aperçu...</span>
        </div>

        <div v-else-if="previewError" class="preview-state">
          <v-icon color="error" size="28">mdi-alert-circle-outline</v-icon>
          <span>{{ previewError }}</span>
        </div>

        <div v-else-if="previewFileUrl" class="preview-frame-wrap">
          <iframe
            v-if="isPdf"
            class="preview-frame"
            :src="previewFileUrl"
            title="Aperçu protocole"
          />
          <div v-else class="preview-state">
            <v-icon color="primary" size="28">mdi-file-download-outline</v-icon>
            <span>Ce fichier ne peut pas être prévisualisé ici. Utilisez le bouton Télécharger.</span>
          </div>
        </div>
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps({
    modelValue: {
      type: Boolean,
      required: true,
    },
    previewTitle: {
      type: String,
      default: '',
    },
    previewDownloadId: {
      type: Number,
      default: null,
    },
    previewLoading: {
      type: Boolean,
      default: false,
    },
    previewError: {
      type: String,
      default: '',
    },
    previewFileUrl: {
      type: String,
      default: '',
    },
    isPdf: {
      type: Boolean,
      default: false,
    },
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'download'): void
    (e: 'close'): void
  }>()

  const dialogModel = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
  })
</script>

<style scoped>
.preview-dialog-card {
  overflow: hidden;
  border: 1px solid rgba(15, 23, 42, 0.12);
  background:
    radial-gradient(900px 260px at 0% -20%, rgba(10, 132, 255, 0.08), transparent 60%),
    linear-gradient(180deg, #ffffff, #f8fafc);
}

.preview-header {
  padding: 14px 18px;
}

.preview-body {
  min-height: 68vh;
  max-height: 74vh;
}

.preview-frame-wrap {
  height: 74vh;
}

.preview-frame {
  width: 100%;
  height: 100%;
  border: 0;
  background: #f1f5f9;
}

.preview-state {
  height: 74vh;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 12px;
  color: rgba(15, 23, 42, 0.72);
  font-weight: 500;
  text-align: center;
  padding: 18px;
}
</style>
