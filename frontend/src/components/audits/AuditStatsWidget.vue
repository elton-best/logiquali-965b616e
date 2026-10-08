<template>
  <BaseCard class="audit-stats-widget">
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <button
          v-if="showRefresh"
          class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          :disabled="loading"
          @click="$emit('refresh')"
        >
          <svg
            :class="['w-5 h-5', loading && 'animate-spin']"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </button>
      </div>
    </template>

    <div v-if="loading" class="py-8 text-center">
      <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin" />
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Chargement...</p>
    </div>

    <div v-else-if="statistics" class="space-y-6">
      <!-- Main Metrics Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Audits -->
        <div class="text-center">
          <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
            {{ statistics.total }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Total audits
          </div>
        </div>

        <!-- In Progress -->
        <div class="text-center">
          <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
            {{ statistics.in_progress }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            En cours
          </div>
        </div>

        <!-- Completed -->
        <div class="text-center">
          <div class="text-3xl font-bold text-green-600 dark:text-green-400">
            {{ statistics.completed }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Terminés
          </div>
        </div>

        <!-- Overdue -->
        <div class="text-center">
          <div class="text-3xl font-bold text-red-600 dark:text-red-400">
            {{ statistics.overdue }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            En retard
          </div>
        </div>
      </div>

      <!-- Conformity Rate -->
      <div v-if="statistics.average_conformity_rate !== undefined">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Taux de conformité moyen
          </span>
          <span :class="['text-lg font-bold', conformityColor]">
            {{ statistics.average_conformity_rate }}%
          </span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
          <div
            :class="['h-2.5 rounded-full transition-all', conformityBarColor]"
            :style="{ width: statistics.average_conformity_rate + '%' }"
          />
        </div>
      </div>

      <!-- Findings Summary -->
      <div v-if="statistics.total_findings > 0">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
          Constats ({{ statistics.total_findings }})
        </h4>
        <div class="space-y-2">
          <!-- Major Findings -->
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-red-500 rounded-full" />
              <span class="text-gray-700 dark:text-gray-300">Majeurs</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white">
              {{ statistics.findings_by_severity.majeur || 0 }}
            </span>
          </div>

          <!-- Minor Findings -->
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-orange-500 rounded-full" />
              <span class="text-gray-700 dark:text-gray-300">Mineurs</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white">
              {{ statistics.findings_by_severity.mineur || 0 }}
            </span>
          </div>

          <!-- Observations -->
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-blue-500 rounded-full" />
              <span class="text-gray-700 dark:text-gray-300">Observations</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white">
              {{ statistics.findings_by_severity.observation || 0 }}
            </span>
          </div>

          <!-- Opportunities -->
          <div class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 bg-green-500 rounded-full" />
              <span class="text-gray-700 dark:text-gray-300">Opportunités</span>
            </div>
            <span class="font-semibold text-gray-900 dark:text-white">
              {{ statistics.findings_by_severity.opportunite || 0 }}
            </span>
          </div>
        </div>
      </div>

      <!-- Rates -->
      <div class="grid grid-cols-2 gap-4">
        <!-- Completion Rate -->
        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ statistics.completion_rate }}%
          </div>
          <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
            Taux de réalisation
          </div>
        </div>

        <!-- On-Time Rate -->
        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
          <div class="text-2xl font-bold text-green-600 dark:text-green-400">
            {{ statistics.on_time_rate }}%
          </div>
          <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
            Respect des délais
          </div>
        </div>
      </div>

      <!-- Audits by Type (if provided) -->
      <div v-if="statistics.by_type && Object.keys(statistics.by_type).length > 0">
        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
          Par type
        </h4>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(count, type) in statistics.by_type"
            :key="type"
            class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium"
          >
            {{ formatType(type) }}: {{ count }}
          </span>
        </div>
      </div>
    </div>

    <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
      Aucune statistique disponible
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { AuditStatistics } from '@/types/audit'
  import type { AuditType } from '@/types/shared'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'

  interface Props {
    statistics: AuditStatistics | null
    loading?: boolean
    title?: string
    showRefresh?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    loading: false,
    title: 'Statistiques Audits',
    showRefresh: true,
  })

  defineEmits<{
    refresh: []
  }>()

  const conformityColor = computed(() => {
    if (!props.statistics) return ''
    const rate = props.statistics.average_conformity_rate
    if (rate >= 90) return 'text-green-600 dark:text-green-400'
    if (rate >= 70) return 'text-orange-600 dark:text-orange-400'
    return 'text-red-600 dark:text-red-400'
  })

  const conformityBarColor = computed(() => {
    if (!props.statistics) return 'bg-gray-400'
    const rate = props.statistics.average_conformity_rate
    if (rate >= 90) return 'bg-green-500'
    if (rate >= 70) return 'bg-orange-500'
    return 'bg-red-500'
  })

  function formatType (type: string): string {
    const labels: Record<AuditType, string> = {
      internal_process: 'Processus',
      internal_system: 'Système',
      internal_product: 'Produit',
      internal_thematic: 'Thématique',
      supplier: 'Fournisseur',
      external_certification: 'Certification',
      external_surveillance: 'Surveillance',
      external_supplier: 'Fournisseur',
      thematic: 'Thématique',
    }
    return labels[type as AuditType] || type
  }
</script>
