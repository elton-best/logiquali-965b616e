<template>
  <div class="action-timeline">
    <div v-if="title" class="timeline-header">
      <h3 class="timeline-title">{{ title }}</h3>
      <div class="timeline-filters">
        <select v-model="statusFilter" class="filter-select">
          <option value="">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="in_progress">En cours</option>
          <option value="completed">Terminé</option>
        </select>
      </div>
    </div>

    <div class="timeline-container">
      <div v-if="filteredActions.length === 0" class="timeline-empty">
        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <p class="text-gray-500">Aucune action</p>
      </div>

      <div v-else class="timeline-items">
        <div
          v-for="(action, index) in filteredActions"
          :key="action.id"
          class="timeline-item"
          :class="{ 'timeline-overdue': isOverdue(action) }"
        >
          <!-- Timeline connector -->
          <div class="timeline-connector">
            <div class="connector-dot" :class="getStatusClass(getActionStatus(action))" />
            <div v-if="index < filteredActions.length - 1" class="connector-line" />
          </div>

          <!-- Action card -->
          <div class="action-card" @click="$emit('action-click', action)">
            <div class="card-header">
              <div class="action-type-badge" :class="`badge-${action.type}`">
                {{ getTypeLabel(action.type) }}
              </div>
              <div class="action-status-badge" :class="getStatusClass(getActionStatus(action))">
                {{ getStatusLabel(getActionStatus(action)) }}
              </div>
            </div>

            <div class="card-body">
              <h4 class="action-title">{{ action.description }}</h4>

              <div class="action-meta">
                <div class="meta-item">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                  <span>{{ formatDate(action.deadline) }}</span>
                </div>

                <div v-if="action.responsible" class="meta-item">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                  <span>{{ action.responsible.name }}</span>
                </div>

                <div v-if="action.progress !== undefined" class="meta-item">
                  <div class="progress-bar-container">
                    <div class="progress-bar" :style="{ width: `${action.progress}%` }" />
                  </div>
                  <span class="text-xs">{{ action.progress }}%</span>
                </div>
              </div>
            </div>

            <div v-if="isOverdue(action)" class="card-footer">
              <div class="alert-warning">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path clip-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" fill-rule="evenodd" />
                </svg>
                <span>Action en retard</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Action } from '@/types/improvement'
  import { computed, ref } from 'vue'

  interface Props {
    actions: Action[]
    title?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Timeline des Actions',
  })

  defineEmits<{
    'action-click': [action: Action]
  }>()

  const statusFilter = ref('')

  const filteredActions = computed(() => {
    let filtered = props.actions

    if (statusFilter.value) {
      filtered = filtered.filter(a => getActionStatus(a) === statusFilter.value)
    }

    // Sort by due date
    return [...filtered].toSorted((a: Action, b: Action) => {
      if (!a.deadline) return 1
      if (!b.deadline) return -1
      return new Date(a.deadline).getTime() - new Date(b.deadline).getTime()
    })
  })

  function isOverdue (action: Action) {
    if (!action.deadline || getActionStatus(action) === 'completed') return false
    return new Date(action.deadline) < new Date()
  }

  function getActionStatus (action: Action): 'pending' | 'in_progress' | 'completed' | 'cancelled' {
    const slug = action.workflow_state?.slug
    if (slug === 'cancelled') return 'cancelled'
    if (slug === 'completed' || Boolean(action.completed_at) || action.progress >= 100) return 'completed'
    if (slug === 'in_progress' || action.progress > 0) return 'in_progress'
    return 'pending'
  }

  function getStatusClass (status: string) {
    const classes: Record<string, string> = {
      pending: 'status-pending',
      in_progress: 'status-progress',
      completed: 'status-completed',
      cancelled: 'status-cancelled',
    }
    return classes[status] || 'status-pending'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      pending: 'En attente',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
    }
    return labels[status] || status
  }

  function getTypeLabel (type: string) {
    const labels: Record<string, string> = {
      corrective: 'Corrective',
      preventive: 'Préventive',
      improvement: 'Amélioration',
    }
    return labels[type] || type
  }

  function formatDate (date?: string) {
    if (!date) return 'Non définie'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>

<style scoped>
.action-timeline {
  @apply bg-white rounded-lg border border-gray-200 p-6;
}

.timeline-header {
  @apply flex items-center justify-between mb-6;
}

.timeline-title {
  @apply text-lg font-semibold text-gray-900;
}

.timeline-filters {
  @apply flex gap-2;
}

.filter-select {
  @apply px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500;
}

.timeline-container {
  @apply relative;
}

.timeline-empty {
  @apply flex flex-col items-center justify-center py-12 text-center;
}

.timeline-items {
  @apply space-y-6;
}

.timeline-item {
  @apply relative flex gap-4;
}

.timeline-connector {
  @apply relative flex flex-col items-center;
}

.connector-dot {
  @apply w-3 h-3 rounded-full border-2 border-white z-10;
}

.status-pending .connector-dot {
  @apply bg-gray-400;
}

.status-progress .connector-dot {
  @apply bg-blue-500;
}

.status-completed .connector-dot {
  @apply bg-green-500;
}

.status-cancelled .connector-dot {
  @apply bg-red-500;
}

.connector-line {
  @apply w-0.5 h-full bg-gray-200 absolute top-3;
}

.action-card {
  @apply flex-1 border border-gray-200 rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow;
}

.timeline-overdue .action-card {
  @apply border-red-300 bg-red-50;
}

.card-header {
  @apply flex items-center gap-2 mb-3;
}

.action-type-badge {
  @apply px-2 py-1 rounded text-xs font-medium;
}

.badge-corrective {
  @apply bg-orange-100 text-orange-800;
}

.badge-preventive {
  @apply bg-blue-100 text-blue-800;
}

.badge-improvement {
  @apply bg-green-100 text-green-800;
}

.action-status-badge {
  @apply px-2 py-1 rounded text-xs font-medium ml-auto;
}

.status-pending {
  @apply bg-gray-100 text-gray-800;
}

.status-progress {
  @apply bg-blue-100 text-blue-800;
}

.status-completed {
  @apply bg-green-100 text-green-800;
}

.card-body {
  @apply space-y-2;
}

.action-title {
  @apply text-sm font-semibold text-gray-900;
}

.action-description {
  @apply text-sm text-gray-600;
}

.action-meta {
  @apply flex flex-wrap gap-3 mt-2;
}

.meta-item {
  @apply flex items-center gap-1.5 text-xs text-gray-600;
}

.progress-bar-container {
  @apply w-20 h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-bar {
  @apply h-full bg-blue-500 transition-all;
}

.card-footer {
  @apply mt-3 pt-3 border-t border-gray-200;
}

.alert-warning {
  @apply flex items-center gap-2 text-xs text-orange-700 bg-orange-100 px-2 py-1 rounded;
}
</style>
