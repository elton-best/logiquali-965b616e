<template>
  <div class="space-y-3">
    <div
      v-for="review in reviews"
      :key="review.id"
      class="p-3 border rounded-lg"
      :class="reviewClass(review.statut)"
    >
      <div class="flex items-center justify-between mb-2">
        <span class="px-2 py-1 rounded text-xs font-medium" :class="statusClass(review.statut)">
          {{ statusLabels[review.statut] }}
        </span>
        <span class="text-xs text-gray-500">{{ formatDate(review.date_prevue) }}</span>
      </div>
      <p class="text-sm font-medium">Responsable: {{ review.responsable?.name }}</p>
      <p v-if="review.commentaire" class="text-xs text-gray-600 mt-1">{{ review.commentaire }}</p>
      <p v-if="review.date_realisee" class="text-xs text-green-600 mt-1">
        Réalisée le {{ formatDate(review.date_realisee) }}
      </p>
    </div>
    <p v-if="!reviews?.length" class="text-center text-gray-500 py-4">Aucune révision planifiée</p>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentReview } from '../../types/document.types'

  defineProps<{
    reviews?: DocumentReview[]
  }>()

  const statusLabels: Record<string, string> = {
    planifiee: 'Planifiée',
    en_cours: 'En cours',
    terminee: 'Terminée',
    reportee: 'Reportée',
  }

  function statusClass (statut: string) {
    const classes: Record<string, string> = {
      planifiee: 'bg-blue-100 text-blue-700',
      en_cours: 'bg-orange-100 text-orange-700',
      terminee: 'bg-green-100 text-green-700',
      reportee: 'bg-gray-100 text-gray-700',
    }
    return classes[statut] || ''
  }

  function reviewClass (statut: string) {
    const classes: Record<string, string> = {
      planifiee: 'border-blue-200 bg-blue-50',
      en_cours: 'border-orange-200 bg-orange-50',
      terminee: 'border-green-200 bg-green-50',
      reportee: 'border-gray-200 bg-gray-50',
    }
    return classes[statut] || ''
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }
</script>
