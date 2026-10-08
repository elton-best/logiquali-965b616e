<template>
  <div class="nc-list">
    <div class="list-header">
      <h2 class="text-2xl font-bold text-gray-900">Non-Conformités</h2>
      <button class="btn-primary" @click="$emit('create')">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        Nouvelle NC
      </button>
    </div>

    <div class="list-filters">
      <AxeSelector v-model="filters.axes" label="Axes" :multiple="true" />
      <select v-model="filters.status" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="draft">Brouillon</option>
        <option value="pending">En attente</option>
        <option value="in_analysis">En analyse</option>
        <option value="resolved">Résolu</option>
        <option value="closed">Clôturé</option>
      </select>
      <select v-model="filters.severity" class="filter-select">
        <option value="">Toutes les gravités</option>
        <option value="minor">Faible</option>
        <option value="major">Majeur</option>
        <option value="critical">Critique</option>
      </select>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner" />
      <p>Chargement...</p>
    </div>

    <div v-else-if="nonConformities.length === 0" class="empty-state">
      <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
      </svg>
      <p class="text-gray-600">Aucune non-conformité trouvée</p>
    </div>

    <div v-else class="nc-table">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="table-header">Référence</th>
            <th class="table-header">Description</th>
            <th class="table-header">Gravité</th>
            <th class="table-header">Axes</th>
            <th class="table-header">Statut</th>
            <th class="table-header">Date</th>
            <th class="table-header">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="nc in nonConformities" :key="nc.id" class="hover:bg-gray-50">
            <td class="table-cell font-medium text-blue-600">{{ nc.ref }}</td>
            <td class="table-cell">
              <div class="truncate max-w-xs">{{ nc.description }}</div>
            </td>
            <td class="table-cell">
              <SeverityBadge :severity="getSeverityLevel(nc.severity)" size="sm" />
            </td>
            <td class="table-cell">
              <div class="flex gap-1">
                <span v-for="axe in nc.axes" :key="axe.id" class="axe-badge">{{ axe.code }}</span>
              </div>
            </td>
            <td class="table-cell">
              <span class="status-badge" :class="`status-${nc.workflow_state?.slug || 'pending'}`">
                {{ getStatusLabel(nc.workflow_state?.slug || 'pending') }}
              </span>
            </td>
            <td class="table-cell text-sm text-gray-500">
              {{ formatDate(nc.created_at) }}
            </td>
            <td class="table-cell">
              <div class="flex gap-2">
                <button class="btn-icon" title="Voir" @click="$emit('view', nc)">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                    <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>
                <button class="btn-icon" title="Modifier" @click="$emit('edit', nc)">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { NonConformity } from '@/types/improvement'
  import { reactive, ref, watch } from 'vue'
  import { useNonConformityStore } from '@/stores/improvement/nonConformityStore'
  import AxeSelector from '../shared/AxeSelector.vue'
  import SeverityBadge from '../shared/SeverityBadge.vue'

  defineEmits<{
    create: []
    view: [nc: NonConformity]
    edit: [nc: NonConformity]
  }>()

  const ncStore = useNonConformityStore()
  const loading = ref(false)
  const filters = reactive({
    axes: [] as string[],
    status: '',
    severity: '',
  })

  const nonConformities = ref<NonConformity[]>([])

  async function loadNonConformities () {
    loading.value = true
    try {
      await ncStore.fetchAll(filters)
      nonConformities.value = ncStore.nonConformities
    } finally {
      loading.value = false
    }
  }

  watch(filters, () => loadNonConformities(), { deep: true })

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      pending: 'En attente',
      in_analysis: 'En analyse',
      resolved: 'Résolu',
      closed: 'Clôturé',
    }
    return labels[status] || status
  }

  function getSeverityLevel (severity: 'minor' | 'major' | 'critical'): 'low' | 'medium' | 'high' | 'critical' {
    if (severity === 'minor') return 'low'
    if (severity === 'major') return 'high'
    return 'critical'
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }

  loadNonConformities()
</script>

<style scoped>
.nc-list { @apply space-y-6; }
.list-header { @apply flex items-center justify-between mb-6; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center; }
.list-filters { @apply flex gap-4 mb-6; }
.filter-select { @apply px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.loading-state { @apply flex flex-col items-center justify-center py-12; }
.spinner { @apply w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin; }
.empty-state { @apply flex flex-col items-center justify-center py-12 text-center; }
.nc-table { @apply overflow-x-auto rounded-lg border border-gray-200; }
.table-header { @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider; }
.table-cell { @apply px-6 py-4 whitespace-nowrap; }
.axe-badge { @apply px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded; }
.status-badge { @apply px-2 py-1 rounded text-xs font-medium; }
.status-draft { @apply bg-gray-100 text-gray-800; }
.status-pending { @apply bg-yellow-100 text-yellow-800; }
.status-in_analysis { @apply bg-blue-100 text-blue-800; }
.status-resolved { @apply bg-green-100 text-green-800; }
.status-closed { @apply bg-gray-200 text-gray-600; }
.btn-icon { @apply p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded; }
</style>
