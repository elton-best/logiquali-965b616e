<template>
  <div class="space-y-3">
    <div
      v-for="version in versions"
      :key="version.id"
      class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
    >
      <div class="flex-shrink-0 w-16 h-16 bg-blue-100 rounded flex items-center justify-center">
        <span class="text-blue-700 font-bold text-sm">v{{ version.version }}</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
          <p class="font-medium text-gray-900">Version {{ version.version }}</p>
          <span class="text-xs text-gray-500">{{ formatDate(version.created_at) }}</span>
        </div>
        <p v-if="version.modifications" class="text-sm text-gray-600 mt-1">{{ version.modifications }}</p>
        <p class="text-xs text-gray-500 mt-1">Par {{ version.creator?.name || 'Inconnu' }}</p>
        <a
          v-if="version.fichier"
          class="text-xs text-blue-600 hover:underline mt-1 inline-block"
          :href="version.fichier"
          target="_blank"
        >
          Télécharger
        </a>
      </div>
    </div>
    <p v-if="!versions?.length" class="text-center text-gray-500 py-4">Aucune version</p>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentVersion } from '../../types/document.types'

  defineProps<{
    versions?: DocumentVersion[]
  }>()

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>
