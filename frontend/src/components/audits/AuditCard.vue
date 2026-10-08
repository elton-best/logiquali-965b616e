<template>
  <BaseCard
    class="audit-card"
    :clickable="clickable"
    :hoverable="hoverable"
    :variant="cardVariant"
    @click="$emit('click', audit)"
  >
    <template #header>
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-mono text-gray-500">{{ audit.code }}</span>
            <AuditTypeBadge size="xs" :type="audit.audit_type" />
          </div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">
            {{ audit.title }}
          </h3>
        </div>
        <StatusBadge
          module="audit"
          size="sm"
          :status="audit.status"
        />
      </div>
    </template>

    <!-- Info Grid -->
    <div class="grid grid-cols-2 gap-4 text-sm">
      <!-- Lead Auditor -->
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Auditeur principal</div>
        <div v-if="audit.lead_auditor" class="flex items-center gap-2">
          <div
            v-if="audit.lead_auditor.avatar"
            class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0"
          >
            <img :alt="audit.lead_auditor.name" class="w-full h-full object-cover" :src="audit.lead_auditor.avatar">
          </div>
          <div
            v-else
            class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-semibold"
          >
            {{ getInitials(audit.lead_auditor.name) }}
          </div>
          <span class="text-gray-900 dark:text-white font-medium truncate">
            {{ audit.lead_auditor.name }}
          </span>
        </div>
      </div>

      <!-- Dates -->
      <div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Dates prévues</div>
        <div class="font-medium text-gray-900 dark:text-white">
          {{ formatDateRange(audit.planned_start_date, audit.planned_end_date) }}
        </div>
      </div>

      <!-- Site (if exists) -->
      <div v-if="audit.site">
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Site</div>
        <div class="font-medium text-gray-900 dark:text-white truncate">
          {{ audit.site.name }}
        </div>
      </div>

      <!-- Conformity Rate (if completed) -->
      <div v-if="audit.conformity_rate !== undefined && audit.conformity_rate !== null">
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Taux de conformité</div>
        <div :class="['font-semibold text-lg', conformityColor]">
          {{ audit.conformity_rate }}%
        </div>
      </div>
    </div>

    <!-- Findings Summary (if exists) -->
    <div v-if="audit.findings_count && audit.findings_count > 0" class="mt-4 flex items-center gap-3 text-sm">
      <div v-if="audit.findings_major" class="flex items-center gap-1">
        <div class="w-3 h-3 rounded-full bg-red-500" />
        <span class="text-gray-700 dark:text-gray-300">{{ audit.findings_major }} majeur(s)</span>
      </div>
      <div v-if="audit.findings_minor" class="flex items-center gap-1">
        <div class="w-3 h-3 rounded-full bg-orange-500" />
        <span class="text-gray-700 dark:text-gray-300">{{ audit.findings_minor }} mineur(s)</span>
      </div>
      <div v-if="audit.findings_observation" class="flex items-center gap-1">
        <div class="w-3 h-3 rounded-full bg-blue-500" />
        <span class="text-gray-700 dark:text-gray-300">{{ audit.findings_observation }} obs.</span>
      </div>
    </div>

    <!-- Axes QHSE -->
    <div v-if="audit.axes_qhse && audit.axes_qhse.length > 0" class="flex gap-2 mt-3">
      <span
        v-for="axis in audit.axes_qhse"
        :key="axis"
        :class="['px-2 py-1 rounded text-xs font-semibold', axisColor(axis)]"
      >
        {{ axis }}
      </span>
    </div>

    <!-- Footer: Actions -->
    <template v-if="$slots.actions" #footer>
      <div class="flex items-center justify-end gap-2">
        <slot :audit="audit" name="actions" />
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { Audit } from '@/types/audit'
  import type { QHSEAxis } from '@/types/shared'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import AuditTypeBadge from './AuditTypeBadge.vue'

  interface Props {
    audit: Audit
    hoverable?: boolean
    clickable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    hoverable: true,
    clickable: false,
  })

  defineEmits<{
    click: [audit: Audit]
  }>()

  const cardVariant = computed(() => {
    if (props.audit.status === 'cancelled') return 'danger'
    if (props.audit.conformity_rate !== undefined && props.audit.conformity_rate < 70) return 'warning'
    return 'default'
  })

  const conformityColor = computed(() => {
    const rate = props.audit.conformity_rate || 0
    if (rate >= 90) return 'text-green-600 dark:text-green-400'
    if (rate >= 70) return 'text-orange-600 dark:text-orange-400'
    return 'text-red-600 dark:text-red-400'
  })

  function axisColor (axis: QHSEAxis): string {
    const colors: Record<QHSEAxis, string> = {
      Q: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
      H: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
      S: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
      E: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
    }
    return colors[axis]
  }

  function formatDateRange (start: string, end: string): string {
    const startDate = new Date(start).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })
    const endDate = new Date(end).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' })
    return `${startDate} - ${endDate}`
  }

  function getInitials (name: string): string {
    return name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }
</script>
