<template>
  <LeadershipLayout>
    <HeroProgressRow>
      <HeroCard
        :badges="[
          {
            icon: 'mdi-file-document',
            text: activeFile ? 'Document chargé' : 'Aucun document',
            variant: 'primary',
          },
          {
            icon: 'mdi-update',
            text: 'Mise à jour rapide',
            variant: 'secondary',
          },
        ]"
        icon="mdi-sitemap"
        icon-color="teal"
        subtitle="Gérez la structure organisationnelle de votre entreprise"
        title="Organigramme"
      />

      <ProgressCard
        :completion="completion"
        label="Statut"
        :last-saved="lastUpdatedLabel"
        :value="activeFile ? 'Document chargé' : 'Aucun document'"
      />
    </HeroProgressRow>

    <GlassCard>
      <h2 class="section-title">
        <span class="section-number">01</span>
        Document d'organigramme
      </h2>

      <div class="section-actions">
        <v-btn
          v-if="activeFile"
          color="teal"
          :loading="loading"
          prepend-icon="mdi-open-in-new"
          variant="tonal"
          @click="openFile"
        >
          Voir
        </v-btn>
        <v-btn
          v-if="activeFile"
          color="teal"
          :loading="loading"
          prepend-icon="mdi-download"
          variant="flat"
          @click="downloadFile"
        >
          Télécharger
        </v-btn>
      </div>

      <div
        :class="[
          'upload-zone',
          { 'has-file': activeFile, 'drag-over': isDragging },
        ]"
        :data-loading="loading"
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
          <v-icon color="#5b8dd9" size="44">mdi-cloud-upload</v-icon>
          <div class="upload-title">Glissez-déposez votre fichier ici</div>
          <div class="upload-subtitle">ou cliquez pour parcourir</div>
          <div class="upload-formats">PDF, Word, PNG, JPG • Max 10 MB</div>
        </div>

        <div v-else class="upload-preview">
          <v-icon :color="getFileColor(activeFile.name)" size="32">
            {{ getFileIcon(activeFile.name) }}
          </v-icon>
          <div class="file-info">
            <div class="file-name">{{ activeFile.name }}</div>
            <div class="file-size">{{ formatFileSize(activeFile.size) }}</div>
          </div>
          <v-btn
            color="error"
            density="comfortable"
            icon="mdi-close"
            :loading="loading"
            variant="tonal"
            @click.stop="clearFile"
          />
        </div>
      </div>

      <div v-if="activeFile" class="file-actions">
        <v-btn
          color="error"
          :loading="loading"
          prepend-icon="mdi-delete"
          variant="outlined"
          @click="clearFile"
        >
          Supprimer
        </v-btn>
        <v-btn
          color="teal"
          :loading="loading"
          prepend-icon="mdi-download"
          variant="flat"
          @click="downloadFile"
        >
          Télécharger
        </v-btn>
      </div>

      <div v-if="currentOrgChart && previewUrl" class="preview-panel">
        <h3 class="preview-title">Aperçu du document</h3>

        <iframe
          v-if="previewType === 'pdf'"
          class="file-frame"
          :src="previewUrl"
          title="Aperçu organigramme"
        />

        <img
          v-else-if="previewType === 'image'"
          alt="Aperçu organigramme"
          class="file-image"
          :src="previewUrl"
        >

        <div v-else class="preview-fallback">
          <v-icon color="#64748b" size="26">mdi-file-outline</v-icon>
          <p>
            Aperçu indisponible pour ce format. Utilise le bouton
            <strong>Voir</strong> ou <strong>Télécharger</strong>.
          </p>
        </div>
      </div>
    </GlassCard>
  </LeadershipLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import GlassCard from '@/components/leadership/GlassCard.vue'
  import HeroCard from '@/components/leadership/HeroCard.vue'
  import HeroProgressRow from '@/components/leadership/HeroProgressRow.vue'
  import LeadershipLayout from '@/components/leadership/LeadershipLayout.vue'
  import ProgressCard from '@/components/leadership/ProgressCard.vue'
  import { leadershipService } from '@/services/leadershipService'
  import { useSiteContextStore } from '@/stores/siteContext'

  const uploadedFile = ref<File | null>(null)
  const currentOrgChart
    = ref<Awaited<ReturnType<typeof leadershipService.getCurrentOrgChart>>>(null)
  const fileInput = ref<HTMLInputElement>()
  const isDragging = ref(false)
  const loading = ref(false)
  const siteContextStore = useSiteContextStore()
  let siteContextTimer: ReturnType<typeof setTimeout> | null = null

  const activeFile = computed(
    () =>
      uploadedFile.value
      || (currentOrgChart.value
        ? {
          name: currentOrgChart.value.file_name,
          size: currentOrgChart.value.file_size,
        }
        : null),
  )
  const completion = computed(() => (activeFile.value ? 100 : 0))
  const lastUpdatedLabel = computed(() => {
    if (!currentOrgChart.value?.updated_at) {
      return 'Jamais'
    }

    return new Date(currentOrgChart.value.updated_at).toLocaleString()
  })

  const currentScopedSiteId = computed<number | null>(() => {
    if (
      siteContextStore.activeScope !== 'site'
      || !siteContextStore.activeSiteId
    ) {
      return null
    }

    const numeric = Number(siteContextStore.activeSiteId)
    return Number.isFinite(numeric) && numeric > 0 ? numeric : null
  })

  const previewUrl = computed(() => {
    if (
      !currentOrgChart.value?.download_url
      && !currentOrgChart.value?.file_path
    ) {
      return ''
    }

    const apiBase = (
      import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1'
    ).replace(/\/api\/v1\/?$/, '')

    const rawUrl
      = currentOrgChart.value.download_url
        || `/storage/${String(currentOrgChart.value.file_path).replace(/^\/?/, '')}`

    return /^https?:\/\//i.test(rawUrl)
      ? rawUrl
      : `${apiBase}${rawUrl.startsWith('/') ? '' : '/'}${rawUrl}`
  })

  const previewType = computed<'pdf' | 'image' | 'other'>(() => {
    const name = String(currentOrgChart.value?.file_name || '').toLowerCase()
    const mime = String(currentOrgChart.value?.file_type || '').toLowerCase()

    if (name.endsWith('.pdf') || mime.includes('application/pdf')) {
      return 'pdf'
    }

    if (
      name.endsWith('.png')
      || name.endsWith('.jpg')
      || name.endsWith('.jpeg')
      || mime.includes('image/')
    ) {
      return 'image'
    }

    return 'other'
  })

  async function loadCurrentOrgChart () {
    try {
      currentOrgChart.value = await leadershipService.getCurrentOrgChart(
        currentScopedSiteId.value,
      )
    } catch (error) {
      console.error('Erreur chargement:', error)
    }
  }

  function triggerFileInput () {
    if (loading.value) {
      return
    }
    fileInput.value?.click()
  }

  async function handleFileChange (event: Event) {
    const target = event.target as HTMLInputElement
    const selectedFile = target.files?.item(0)
    if (selectedFile) {
      await uploadFile(selectedFile)
    }
  }

  async function handleDrop (event: DragEvent) {
    if (loading.value) {
      return
    }

    isDragging.value = false
    const files = event.dataTransfer?.files
    const droppedFile = files?.item(0)
    if (droppedFile) {
      await uploadFile(droppedFile)
    }
  }

  async function uploadFile (file: File) {
    loading.value = true
    try {
      const result = await leadershipService.uploadOrgChart(
        file,
        currentScopedSiteId.value,
      )
      if (!result?.id) {
        throw new Error('Réponse invalide du serveur après upload')
      }
      currentOrgChart.value = result
      uploadedFile.value = null
      alert('✅ Organigramme téléversé avec succès')
    } catch (error) {
      console.error('Erreur upload:', error)
      alert('❌ Erreur lors du téléversement')
    } finally {
      loading.value = false
    }
  }

  async function clearFile () {
    if (currentOrgChart.value && confirm('Supprimer cet organigramme ?')) {
      try {
        if (!currentOrgChart.value.id) {
          throw new Error('Identifiant organigramme introuvable')
        }

        await leadershipService.deleteOrgChart(currentOrgChart.value.id)
        currentOrgChart.value = null
        alert('✅ Organigramme supprimé')
      } catch (error) {
        console.error('Erreur suppression organigramme:', error)
        alert(
          '❌ Suppression impossible: identifiant invalide ou document déjà supprimé',
        )
      }
    }
    uploadedFile.value = null
    if (fileInput.value) fileInput.value.value = ''
  }

  function downloadFile () {
    if (previewUrl.value) {
      window.open(previewUrl.value, '_blank')
    } else if (uploadedFile.value) {
      const url = URL.createObjectURL(uploadedFile.value)
      const a = document.createElement('a')
      a.href = url
      a.download = uploadedFile.value.name
      a.click()
      URL.revokeObjectURL(url)
    }
  }

  function openFile () {
    if (!previewUrl.value) {
      return
    }
    window.open(previewUrl.value, '_blank')
  }

  function formatFileSize (bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  }

  function getFileIcon (filename: string | undefined): string {
    if (!filename) return 'mdi-file'
    const ext = filename.split('.').pop()?.toLowerCase()
    if (ext === 'pdf') return 'mdi-file-pdf-box'
    if (['doc', 'docx'].includes(ext || '')) return 'mdi-file-word'
    if (['png', 'jpg', 'jpeg'].includes(ext || '')) return 'mdi-file-image'
    return 'mdi-file'
  }

  function getFileColor (filename: string | undefined): string {
    if (!filename) return '#64748b'
    const ext = filename.split('.').pop()?.toLowerCase()
    if (ext === 'pdf') return '#ef4444'
    if (['doc', 'docx'].includes(ext || '')) return '#2563eb'
    if (['png', 'jpg', 'jpeg'].includes(ext || '')) return '#10b981'
    return '#64748b'
  }

  function handleSiteContextChanged () {
    if (siteContextTimer) {
      clearTimeout(siteContextTimer)
    }

    siteContextTimer = setTimeout(() => {
      void loadCurrentOrgChart()
    }, 200)
  }

  watch(
    () => [siteContextStore.activeScope, siteContextStore.activeSiteId],
    () => {
      handleSiteContextChanged()
    },
  )

  onMounted(async () => {
    await loadCurrentOrgChart()
    window.addEventListener('site-context-changed', handleSiteContextChanged)
  })

  onUnmounted(() => {
    if (siteContextTimer) {
      clearTimeout(siteContextTimer)
      siteContextTimer = null
    }
    window.removeEventListener('site-context-changed', handleSiteContextChanged)
  })
</script>

<style scoped>
.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
}

.section-actions {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}

.upload-zone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 28px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: #f8fafc;
}

.upload-zone[data-loading="true"] {
  opacity: 0.7;
  pointer-events: none;
}

.upload-zone:hover {
  border-color: #14b8a6;
  background: rgba(20, 184, 166, 0.05);
}

.upload-zone.drag-over {
  border-color: #14b8a6;
  background: rgba(20, 184, 166, 0.1);
  transform: scale(1.01);
}

.upload-zone.has-file {
  border-style: solid;
  border-color: #e2e8f0;
  padding: 18px;
}

.upload-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
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
  margin-top: 4px;
}

.upload-preview {
  display: flex;
  align-items: center;
  gap: 12px;
}

.file-info {
  flex: 1;
  text-align: left;
}

.file-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #1e293b;
}

.file-size {
  font-size: 0.75rem;
  color: #64748b;
}

.file-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.preview-panel {
  margin-top: 18px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  padding: 14px;
}

.preview-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 10px;
}

.file-frame {
  width: 100%;
  min-height: 460px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.file-image {
  width: 100%;
  max-height: 520px;
  object-fit: contain;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
}

.preview-fallback {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 14px;
  border: 1px dashed #cbd5e1;
  border-radius: 10px;
  background: #f8fafc;
  color: #475569;
}

.hidden {
  display: none;
}

@media (max-width: 768px) {
  .upload-zone {
    padding: 32px 16px;
  }
  .file-actions {
    flex-direction: column;
  }
}
</style>
