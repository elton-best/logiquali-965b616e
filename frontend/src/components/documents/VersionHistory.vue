/**
 * VersionHistory Component
 * Display and manage document versions
 */

<script setup lang="ts">
  import type { DocumentVersion } from '@/types/document'
  import { Calendar, Download, User } from 'lucide-vue-next'
  import { computed } from 'vue'

  interface Props {
    versions: DocumentVersion[]
    currentVersion?: string
    loading?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<{
    download: [versionId: number, filename: string]
  }>()

  const sortedVersions = computed(() => {
    return [...props.versions].toSorted((a: DocumentVersion, b: DocumentVersion) =>
      new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
    )
  })

  const normalizedVersions = computed(() => {
    return sortedVersions.value.map(version => {
      const fileName = version.file_original_name || version.file_path || (version as any).fichier || ''
      const fileSize = version.file_size || (version as any).file_size || 0
      const changeSummary = version.change_summary || (version as any).modifications || ''
      let creatorName = 'Utilisateur inconnu'
      if (version.created_by?.name) {
        creatorName = version.created_by.name
      } else if ((version as any).creator?.name) {
        creatorName = (version as any).creator.name
      } else if (typeof version.created_by === 'string') {
        creatorName = version.created_by
      } else if (typeof version.created_by === 'number') {
        creatorName = `Utilisateur #${version.created_by}`
      }

      return {
        raw: version,
        fileName,
        fileSize,
        changeSummary,
        creatorName,
      }
    })
  })

  function handleDownload (version: DocumentVersion) {
    const filename = version.file_original_name
      || (version as any).fichier
      || `version-${version.version}`
    emit('download', version.id, filename)
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function formatFileSize (bytes: number): string {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
  }
</script>

<template>
  <div class="space-y-4">
    <div v-if="loading" class="p-6 text-center">
      <div class="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto" />
    </div>

    <div v-else-if="versions.length === 0" class="p-12 text-center">
      <p class="text-neutral-500">Aucune version disponible</p>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="item in normalizedVersions"
        :key="item.raw.id"
        :class="[
          'card p-4 hover:shadow-md transition-shadow',
          item.raw.version === currentVersion && 'ring-2 ring-primary-500'
        ]"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="font-semibold text-neutral-900 dark:text-neutral-50">
                Version {{ item.raw.version }}
              </span>
              <span
                v-if="item.raw.version === currentVersion"
                class="badge text-primary-600 bg-primary-100 dark:bg-primary-900/30"
              >
                Version actuelle
              </span>
            </div>

            <div class="space-y-1 text-sm text-neutral-600 dark:text-neutral-400">
              <p class="flex items-center gap-2">
                <User class="w-4 h-4" />
                {{ item.creatorName }}
              </p>
              <p class="flex items-center gap-2">
                <Calendar class="w-4 h-4" />
                {{ formatDate(item.raw.created_at) }}
              </p>
              <p v-if="item.changeSummary" class="text-xs italic">
                "{{ item.changeSummary }}"
              </p>
            </div>

            <div v-if="item.fileName" class="mt-2 text-xs text-neutral-500">
              {{ item.fileName }}
              <span v-if="item.fileSize">• {{ formatFileSize(item.fileSize) }}</span>
            </div>
          </div>

          <button
            v-if="item.fileName"
            class="btn-secondary inline-flex items-center gap-2 flex-shrink-0"
            @click="handleDownload(item.raw)"
          >
            <Download class="w-4 h-4" />
            Télécharger
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
