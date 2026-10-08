<template>
  <div class="proof-uploader">
    <v-file-input
      v-model="files"
      accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
      chips
      density="comfortable"
      :disabled="uploading"
      label="Ajouter des preuves"
      multiple
      prepend-icon="mdi-paperclip"
      show-size
      variant="outlined"
      @update:model-value="handleFileSelect"
    >
      <template #selection="{ fileNames }">
        <v-chip
          v-for="fileName in fileNames"
          :key="fileName"
          class="me-2"
          closable
          color="primary"
          size="small"
          variant="tonal"
        >
          {{ fileName }}
        </v-chip>
      </template>
    </v-file-input>

    <v-progress-linear
      v-if="uploading"
      class="mt-2"
      color="primary"
      height="4"
      :model-value="uploadProgress"
      rounded
    />

    <div v-if="existingProofs.length > 0" class="mt-4">
      <h4 class="text-subtitle-2 mb-2">Preuves existantes</h4>
      <v-list class="proof-list" density="compact">
        <v-list-item
          v-for="proof in existingProofs"
          :key="proof.id"
          class="proof-item"
        >
          <template #prepend>
            <v-icon :color="getFileIcon(proof.filename).color">
              {{ getFileIcon(proof.filename).icon }}
            </v-icon>
          </template>

          <v-list-item-title>{{ proof.filename }}</v-list-item-title>
          <v-list-item-subtitle>
            Ajouté le {{ formatDate(proof.uploadedAt) }} par {{ proof.uploadedBy }}
          </v-list-item-subtitle>

          <template #append>
            <v-btn
              icon="mdi-download"
              size="small"
              variant="text"
              @click="downloadProof(proof)"
            />
            <v-btn
              color="error"
              icon="mdi-delete"
              size="small"
              variant="text"
              @click="$emit('delete', proof.id)"
            />
          </template>
        </v-list-item>
      </v-list>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { FormationProof } from '../../types/formation.types'
  import { ref } from 'vue'

  interface Props {
    existingProofs?: FormationProof[]
  }

  withDefaults(defineProps<Props>(), {
    existingProofs: () => [],
  })

  const emit = defineEmits<{
    upload: [files: File[]]
    delete: [proofId: string]
  }>()

  const files = ref<File[]>([])
  const uploading = ref(false)
  const uploadProgress = ref(0)

  function handleFileSelect (selectedFiles: File | File[] | null) {
    const normalized = Array.isArray(selectedFiles) ? selectedFiles : (selectedFiles ? [selectedFiles] : [])
    if (normalized.length > 0) {
      emit('upload', normalized)
    }
  }

  function getFileIcon (filename: string) {
    const ext = filename.split('.').pop()?.toLowerCase()
    const icons: Record<string, { icon: string, color: string }> = {
      pdf: { icon: 'mdi-file-pdf-box', color: 'error' },
      jpg: { icon: 'mdi-file-image', color: 'info' },
      jpeg: { icon: 'mdi-file-image', color: 'info' },
      png: { icon: 'mdi-file-image', color: 'info' },
      doc: { icon: 'mdi-file-word', color: 'primary' },
      docx: { icon: 'mdi-file-word', color: 'primary' },
    }
    return icons[ext || ''] || { icon: 'mdi-file', color: 'grey' }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function downloadProof (proof: FormationProof) {
    window.open(proof.url, '_blank')
  }
</script>

<style scoped>
.proof-uploader {
  width: 100%;
}

.proof-list {
  border: 1px solid rgba(0, 0, 0, 0.12);
  border-radius: 8px;
}

.proof-item {
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.proof-item:last-child {
  border-bottom: none;
}
</style>
