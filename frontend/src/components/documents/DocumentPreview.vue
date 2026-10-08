<template>
  <div class="document-preview">
    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="inline-block w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4" />
        <p class="text-gray-600 dark:text-gray-400">Chargement de la prévisualisation...</p>
      </div>
    </div>

    <div v-else-if="error" class="flex items-center justify-center h-96">
      <div class="text-center text-red-600">
        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <p>{{ error }}</p>
      </div>
    </div>

    <div v-else-if="previewUrl" class="relative">
      <!-- PDF Preview -->
      <iframe
        v-if="isPdf"
        class="w-full border-0 rounded"
        :src="previewUrl"
        :style="{ height: height + 'px' }"
      />

      <!-- Image Preview -->
      <img
        v-else-if="isImage"
        :alt="document?.title"
        class="w-full h-auto max-h-screen object-contain rounded"
        :src="previewUrl"
      >

      <!-- Unsupported Preview -->
      <div v-else class="flex flex-col items-center justify-center h-96 bg-gray-50 dark:bg-gray-800 rounded">
        <svg class="w-20 h-20 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <p class="text-gray-600 dark:text-gray-400 mb-4">
          Prévisualisation non disponible pour ce type de fichier
        </p>
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          @click="$emit('download')"
        >
          Télécharger le fichier
        </button>
      </div>
    </div>

    <div v-else class="flex items-center justify-center h-96 bg-gray-50 dark:bg-gray-800 rounded">
      <p class="text-gray-600 dark:text-gray-400">Aucun aperçu disponible</p>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
  import { computed, onMounted, ref, watch } from 'vue'
  import { documentsApi } from '@/api/documents'

  interface Props {
    document?: UnifiedDocument
    documentId?: number
    versionId?: number
    height?: number
  }

  const props = withDefaults(defineProps<Props>(), {
    height: 600,
  })

  defineEmits<{
    download: []
  }>()

  const loading = ref(false)
  const error = ref<string | null>(null)
  const previewUrl = ref<string | null>(null)

  const fileExtension = computed(() => {
    if (props.document?.file_extension) {
      return props.document.file_extension.toLowerCase()
    }
    const path = props.document?.file_path || props.document?.fichier || ''
    if (path) {
      const match = path.match(/\.([a-zA-Z0-9]+)(?:[\?#]|$)/)
      if (match) {
        return match[1].toLowerCase()
      }
    }
    return ''
  })

  const isPdf = computed(() => {
    return fileExtension.value === 'pdf'
  })

  const isImage = computed(() => {
    return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(fileExtension.value)
  })

  async function loadPreview () {
    if (!props.documentId && !props.document?.id) {
      error.value = 'Document ID manquant'
      return
    }

    loading.value = true
    error.value = null

    try {
      const id = props.documentId || props.document!.id
      const response = await documentsApi.previewFile(id, props.versionId)
      previewUrl.value = window.URL.createObjectURL(response.data as Blob)
    } catch (error_: any) {
      error.value = error_.message || 'Erreur lors du chargement de la prévisualisation'
      console.error('[DocumentPreview] Load error:', error_)
    } finally {
      loading.value = false
    }
  }

  watch(() => [props.documentId, props.versionId], () => {
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value)
      previewUrl.value = null
    }
    loadPreview()
  })

  onMounted(() => {
    loadPreview()
  })
</script>
