<template>
  <div class="excel-upload">
    <div
      :class="['upload-zone', { 'drag-over': isDragging, 'has-file': file }]"
      @click="triggerFileInput"
      @dragleave.prevent="isDragging = false"
      @dragover.prevent="isDragging = true"
      @drop.prevent="handleDrop"
    >
      <input
        ref="fileInput"
        accept=".xlsx,.xls"
        class="hidden"
        type="file"
        @change="handleFileChange"
      >

      <div v-if="!file" class="upload-empty">
        <v-icon color="#22c55e" size="48">mdi-file-excel</v-icon>
        <div class="upload-title">Importer un fichier Excel</div>
        <div class="upload-subtitle">Glissez-déposez ou cliquez pour parcourir</div>
        <div class="upload-formats">.xlsx, .xls • Max 10 MB</div>
      </div>

      <div v-else class="upload-preview">
        <v-icon color="#22c55e" size="40">mdi-file-excel</v-icon>
        <div class="file-info">
          <div class="file-name">{{ file.name }}</div>
          <div class="file-size">{{ formatFileSize(file.size) }}</div>
        </div>
        <button class="remove-btn" @click.stop="clearFile">
          <v-icon size="18">mdi-close</v-icon>
        </button>
      </div>
    </div>

    <div v-if="downloadTemplate" class="template-download">
      <v-icon color="#5b8dd9" size="20">mdi-download</v-icon>
      <a href="#" @click.prevent="$emit('download-template')">Télécharger le canevas Excel</a>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'

  withDefaults(defineProps<{
    downloadTemplate?: boolean
  }>(), {
    downloadTemplate: true,
  })

  const emit = defineEmits<{
    'file-selected': [file: File]
    'download-template': []
  }>()

  const file = ref<File | null>(null)
  const fileInput = ref<HTMLInputElement>()
  const isDragging = ref(false)

  function triggerFileInput () {
    fileInput.value?.click()
  }

  function handleFileChange (event: Event) {
    const target = event.target as HTMLInputElement
    const selectedFile = target.files?.item(0)
    if (selectedFile) {
      file.value = selectedFile
      emit('file-selected', selectedFile)
    }
  }

  function handleDrop (event: DragEvent) {
    isDragging.value = false
    const files = event.dataTransfer?.files
    const droppedFile = files?.item(0)
    if (droppedFile) {
      file.value = droppedFile
      emit('file-selected', droppedFile)
    }
  }

  function clearFile () {
    file.value = null
    if (fileInput.value) fileInput.value.value = ''
  }

  function formatFileSize (bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  }
</script>

<style scoped>
.excel-upload {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 32px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: #f8fafc;
}

.upload-zone:hover {
  border-color: #22c55e;
  background: rgba(34, 197, 94, 0.05);
}

.upload-zone.drag-over {
  border-color: #22c55e;
  background: rgba(34, 197, 94, 0.1);
  transform: scale(1.02);
}

.upload-zone.has-file {
  border-style: solid;
  border-color: #e2e8f0;
  padding: 20px;
}

.upload-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.upload-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
}

.upload-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}

.upload-formats {
  font-size: 0.75rem;
  color: #94a3b8;
}

.upload-preview {
  display: flex;
  align-items: center;
  gap: 16px;
}

.file-info {
  flex: 1;
  text-align: left;
}

.file-name {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
}

.file-size {
  font-size: 0.75rem;
  color: #64748b;
}

.remove-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.remove-btn:hover {
  background: rgba(239, 68, 68, 0.2);
}

.template-download {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(91, 141, 217, 0.05);
  border-radius: 8px;
}

.template-download a {
  font-size: 0.875rem;
  font-weight: 600;
  color: #5b8dd9;
  text-decoration: none;
}

.template-download a:hover {
  text-decoration: underline;
}

.hidden {
  display: none;
}
</style>
