<template>
  <div class="space-y-4">
    <div
      v-for="link in links"
      :key="link.id"
      class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
    >
      <div class="flex items-center gap-3">
        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded font-medium">
          {{ relationLabels[link.type_relation] }}
        </span>
        <div>
          <p class="font-medium text-sm">{{ link.document_cible?.nom }}</p>
          <p class="text-xs text-gray-500">{{ link.document_cible?.code }}</p>
        </div>
      </div>
      <button
        class="text-red-600 hover:text-red-700 text-sm"
        @click="$emit('unlink', link.id)"
      >
        Supprimer
      </button>
    </div>
    <p v-if="!links?.length" class="text-center text-gray-500 py-4">Aucun lien</p>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentLink } from '../../types/document-unified.types'

  defineProps<{
    links?: DocumentLink[]
  }>()

  defineEmits<{
    unlink: [id: number]
  }>()

  const relationLabels: Record<string, string> = {
    utilise: 'Utilise',
    reference: 'Référence',
    remplace: 'Remplace',
    complete: 'Complète',
    genere: 'Génère',
  }
</script>
