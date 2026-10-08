<template>
  <ClientALayout>
    <div class="leadership-page">
      <div class="content-shell">
        <!-- Hero Card -->
        <div class="hero-card glass-card animate-fade-in">
          <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-start gap-5">
              <div class="hero-icon">
                <v-icon color="white" size="40">mdi-sitemap</v-icon>
              </div>
              <div>
                <h1 class="hero-title">Organigramme</h1>
                <p class="hero-subtitle">Gérez la structure organisationnelle de votre entreprise</p>
                <div class="meta-badges">
                  <span class="badge badge-primary">
                    <v-icon size="14">mdi-file-document</v-icon>
                    Document officiel
                  </span>
                  <span class="badge badge-secondary">
                    <v-icon size="14">mdi-update</v-icon>
                    Mise à jour rapide
                  </span>
                </div>
              </div>
            </div>
            <button
              v-if="activeFile"
              class="btn-primary"
              @click="handleDownload"
            >
              <v-icon size="20">mdi-download</v-icon>
              Télécharger
            </button>
          </div>
        </div>

        <!-- Status Card -->
        <div class="status-card glass-card animate-slide-up">
          <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
              <div :class="['status-icon', activeFile ? 'success' : 'warning']">
                <v-icon color="white" size="32">
                  {{ activeFile ? 'mdi-check-circle' : 'mdi-alert-circle' }}
                </v-icon>
              </div>
              <div>
                <div class="status-label">Statut du document</div>
                <div class="status-value">{{ activeFile ? 'Document chargé' : 'Aucun document' }}</div>
              </div>
            </div>
            <div class="format-info">
              <v-icon class="mr-1" size="16">mdi-information</v-icon>
              PDF, Word, PNG, JPG acceptés
            </div>
          </div>
        </div>

        <!-- Upload Card -->
        <div class="content-card glass-card">
          <h2 class="section-title">
            <span class="section-number">01</span>
            Document d'organigramme
          </h2>

          <div
            :class="['upload-zone', { 'has-file': activeFile, 'drag-over': isDragging }]"
            @click="triggerFileInput"
            @dragleave.prevent="isDragging = false"
            @dragover.prevent="isDragging = true"
            @drop.prevent="handleDrop"
          >
            <input
              ref="fileInput"
              accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
              class="hidden"
              type="file"
              @change="handleFileChange"
            >

            <div v-if="!activeFile" class="upload-empty">
              <div class="upload-icon">
                <v-icon color="primary" size="64">mdi-cloud-upload</v-icon>
              </div>
              <div class="upload-title">Glissez-déposez votre fichier ici</div>
              <div class="upload-subtitle">ou cliquez pour parcourir</div>
              <div class="upload-formats">PDF, Word, PNG, JPG • Max 10 MB</div>
            </div>

            <div v-else class="upload-preview">
              <div class="preview-icon">
                <v-icon :color="getFileColor(activeFile.name)" size="48">
                  {{ getFileIcon(activeFile.name) }}
                </v-icon>
              </div>
              <div class="preview-info">
                <div class="preview-name">{{ activeFile.name }}</div>
                <div class="preview-size">{{ formatFileSize(activeFile.size) }}</div>
              </div>
              <button class="preview-remove" @click.stop="clearFile">
                <v-icon size="20">mdi-close</v-icon>
              </button>
            </div>
          </div>

          <div v-if="activeFile" class="file-actions">
            <button class="btn-secondary" @click="clearFile">
              <v-icon size="20">mdi-delete</v-icon>
              Supprimer
            </button>
            <button class="btn-primary" @click="handleDownload">
              <v-icon size="20">mdi-download</v-icon>
              Télécharger
            </button>
          </div>
        </div>
      </div>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'

  const uploadedFile = ref<File[]>([])
  const fileInput = ref<HTMLInputElement>()
  const isDragging = ref(false)

  const activeFile = computed(() => uploadedFile.value?.[0] ?? null)

  function triggerFileInput () {
    fileInput.value?.click()
  }

  function handleFileChange (event: Event) {
    const target = event.target as HTMLInputElement
    const selectedFile = target.files?.item(0)
    if (selectedFile) {
      uploadedFile.value = [selectedFile]
    }
  }

  function handleDrop (event: DragEvent) {
    isDragging.value = false
    const files = event.dataTransfer?.files
    const droppedFile = files?.item(0)
    if (droppedFile) {
      uploadedFile.value = [droppedFile]
    }
  }

  function clearFile () {
    uploadedFile.value = []
    if (fileInput.value) fileInput.value.value = ''
  }

  function handleDownload () {
    if (!activeFile.value) return
    const url = URL.createObjectURL(activeFile.value)
    const a = document.createElement('a')
    a.href = url
    a.download = activeFile.value.name
    a.click()
    URL.revokeObjectURL(url)
  }

  function formatFileSize (bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  }

  function getFileIcon (filename: string): string {
    const ext = filename.split('.').pop()?.toLowerCase()
    if (ext === 'pdf') return 'mdi-file-pdf-box'
    if (['doc', 'docx'].includes(ext || '')) return 'mdi-file-word'
    if (['png', 'jpg', 'jpeg'].includes(ext || '')) return 'mdi-file-image'
    return 'mdi-file'
  }

  function getFileColor (filename: string): string {
    const ext = filename.split('.').pop()?.toLowerCase()
    if (ext === 'pdf') return '#ef4444'
    if (['doc', 'docx'].includes(ext || '')) return '#2563eb'
    if (['png', 'jpg', 'jpeg'].includes(ext || '')) return '#10b981'
    return '#64748b'
  }
</script>

<style scoped>
.leadership-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 32px 24px;
}

.content-shell {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.glass-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.glass-card:hover {
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.hero-card {
  padding: 32px;
}

.hero-icon {
  width: 80px;
  height: 80px;
  border-radius: 20px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  display: grid;
  place-items: center;
  box-shadow: 0 8px 24px rgba(20, 184, 166, 0.3);
}

.hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 8px;
}

.hero-subtitle {
  font-size: 1rem;
  color: #64748b;
  margin-bottom: 16px;
}

.meta-badges {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-primary {
  background: rgba(20, 184, 166, 0.15);
  color: #0d9488;
}

.badge-secondary {
  background: rgba(100, 116, 139, 0.15);
  color: #475569;
}

.status-card {
  padding: 24px 32px;
}

.status-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: grid;
  place-items: center;
}

.status-icon.success {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
}

.status-icon.warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
}

.status-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 4px;
}

.status-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.format-info {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: #64748b;
}

.content-card {
  padding: 40px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 32px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
  font-size: 1.25rem;
  font-weight: 700;
}

.upload-zone {
  border: 3px dashed #cbd5e1;
  border-radius: 20px;
  padding: 48px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
  background: rgba(248, 250, 252, 0.5);
}

.upload-zone:hover {
  border-color: #14b8a6;
  background: rgba(20, 184, 166, 0.05);
}

.upload-zone.drag-over {
  border-color: #14b8a6;
  background: rgba(20, 184, 166, 0.1);
  transform: scale(1.02);
}

.upload-zone.has-file {
  border-style: solid;
  border-color: #e2e8f0;
  padding: 32px;
}

.upload-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.upload-icon {
  width: 120px;
  height: 120px;
  border-radius: 24px;
  background: rgba(20, 184, 166, 0.1);
  display: grid;
  place-items: center;
  margin-bottom: 8px;
}

.upload-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
}

.upload-subtitle {
  font-size: 1rem;
  color: #64748b;
}

.upload-formats {
  font-size: 0.875rem;
  color: #94a3b8;
  margin-top: 8px;
}

.upload-preview {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.preview-icon {
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: rgba(20, 184, 166, 0.1);
  display: grid;
  place-items: center;
}

.preview-info {
  flex: 1;
}

.preview-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
}

.preview-size {
  font-size: 0.875rem;
  color: #64748b;
}

.preview-remove {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: none;
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.preview-remove:hover {
  background: rgba(239, 68, 68, 0.2);
  transform: scale(1.1);
}

.file-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.btn-primary,
.btn-secondary {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9375rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
}

.btn-primary:hover {
  box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
  transform: translateY(-2px);
}

.btn-secondary {
  background: white;
  color: #64748b;
  border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.hidden {
  display: none;
}

.animate-fade-in {
  animation: fadeIn 0.5s ease;
}

.animate-slide-up {
  animation: slideUp 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .leadership-page { padding: 16px; }
  .hero-card, .content-card { padding: 24px; }
  .upload-zone { padding: 32px 16px; }
  .upload-preview { flex-direction: column; text-align: center; }
  .file-actions { flex-direction: column; }
}
</style>
