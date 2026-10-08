<template>
  <div class="reclamation-list">
    <h3 class="text-lg font-semibold mb-4">Réclamations</h3>

    <div class="space-y-3">
      <div v-for="rec in reclamations" :key="rec.id" class="reclamation-card" @click="$emit('view', rec)">
        <div class="flex justify-between items-start">
          <div>
            <h4 class="font-semibold">{{ rec.ref }} - {{ rec.description }}</h4>
            <p class="text-sm text-gray-600 mt-1">{{ rec.source }} - {{ formatDate(rec.created_at) }}</p>
          </div>
          <SeverityBadge :severity="toBadgeSeverity(rec.severity)" size="sm" />
        </div>
        <div class="flex gap-2 mt-2">
          <span class="status-badge" :class="`status-${rec.status}`">{{ getStatusLabel(rec.status) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Reclamation } from '@/types/improvement'
  import { computed } from 'vue'
  import { useReclamationStore } from '@/stores/improvement/reclamationStore'
  import SeverityBadge from '../shared/SeverityBadge.vue'

  defineEmits(['view'])
  const reclamationStore = useReclamationStore()
  const reclamations = computed<Reclamation[]>(() => reclamationStore.reclamations)

  reclamationStore.fetchReclamations()

  function getStatusLabel (status?: Reclamation['status']) {
    if (!status) return '-'
    const labels: Record<NonNullable<Reclamation['status']>, string> = {
      pending: 'En attente',
      in_progress: 'En cours',
      closed: 'Fermé',
      cancelled: 'Annulé',
    }
    return labels[status] || status
  }

  function toBadgeSeverity (severity: Reclamation['severity']): 'low' | 'medium' | 'high' | 'critical' {
    if (severity === 'minor') return 'low'
    if (severity === 'major') return 'high'
    return 'critical'
  }

  const formatDate = (date: string) => new Date(date).toLocaleDateString('fr-FR')
</script>

<style scoped>
.reclamation-card { @apply p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:shadow-md transition-shadow; }
.status-badge { @apply px-2 py-1 rounded text-xs font-medium; }
.status-pending { @apply bg-yellow-100 text-yellow-800; }
.status-in_progress { @apply bg-blue-100 text-blue-800; }
.status-closed { @apply bg-green-100 text-green-800; }
</style>
