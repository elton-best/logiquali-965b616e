<template>
  <BaseCard class="audit-finding-card">
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-mono text-gray-500">{{ finding.reference }}</span>
          <AuditFindingSeverityBadge :severity="finding.severity" size="xs" />
        </div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
          {{ finding.title }}
        </h4>
      </div>
      <span
        :class="['px-2 py-1 rounded text-xs font-medium', statusColor]"
      >
        {{ statusLabel }}
      </span>
    </div>

    <p v-if="finding.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
      {{ finding.description }}
    </p>

    <div class="grid grid-cols-2 gap-3 text-xs">
      <!-- Clause ISO -->
      <div v-if="finding.clause_iso">
        <span class="text-gray-500 dark:text-gray-400">Clause ISO:</span>
        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ finding.clause_iso }}</span>
      </div>

      <!-- Process -->
      <div v-if="finding.process">
        <span class="text-gray-500 dark:text-gray-400">Processus:</span>
        <span class="ml-1 font-medium text-gray-900 dark:text-white truncate">{{ finding.process.name }}</span>
      </div>

      <!-- Detected At -->
      <div>
        <span class="text-gray-500 dark:text-gray-400">Détecté le:</span>
        <span class="ml-1 font-medium text-gray-900 dark:text-white">{{ formatDate(finding.detected_at) }}</span>
      </div>

      <!-- Responsible -->
      <div v-if="finding.responsible">
        <span class="text-gray-500 dark:text-gray-400">Responsable:</span>
        <span class="ml-1 font-medium text-gray-900 dark:text-white truncate">{{ finding.responsible.name }}</span>
      </div>
    </div>

    <!-- Flags -->
    <div class="flex items-center gap-2 mt-3">
      <span
        v-if="finding.nc_generated"
        class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded text-xs font-medium"
      >
        NC générée
      </span>
      <span
        v-if="finding.action_required"
        class="px-2 py-1 bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300 rounded text-xs font-medium"
      >
        Action requise
      </span>
      <span
        v-if="finding.attachments && finding.attachments.length > 0"
        class="px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded text-xs font-medium"
      >
        📎 {{ finding.attachments.length }}
      </span>
    </div>

    <!-- Actions Slot -->
    <div v-if="$slots.actions" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
      <slot :finding="finding" name="actions" />
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { AuditFinding } from '@/types/audit'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import AuditFindingSeverityBadge from './AuditFindingSeverityBadge.vue'

  interface Props {
    finding: AuditFinding
  }

  const props = defineProps<Props>()

  const statusLabels: Record<string, string> = {
    open: 'Ouvert',
    action_planned: 'Action planifiée',
    in_progress: 'En cours',
    resolved: 'Résolu',
    verified: 'Vérifié',
    closed: 'Clôturé',
  }

  const statusLabel = computed(() => statusLabels[props.finding.status] || props.finding.status)

  const statusColor = computed(() => {
    const colors: Record<string, string> = {
      open: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
      action_planned: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
      in_progress: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
      resolved: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
      verified: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
      closed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    }
    return colors[props.finding.status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  })

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>
