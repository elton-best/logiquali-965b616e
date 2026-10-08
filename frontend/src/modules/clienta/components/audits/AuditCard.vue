<template>
  <AppCard clickable hover-lift @click="$emit('click', audit)">
    <template #header>
      <div class="flex items-center justify-between">
        <AppBadge
          :label="getTypeLabel(audit.type)"
          :variant="getTypeVariant(audit.type)"
        />
        <span class="text-xs text-neutral-500">{{ audit.reference }}</span>
      </div>
    </template>

    <template #content>
      <h3
        class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2 line-clamp-2"
      >
        {{ audit.title }}
      </h3>

      <p
        class="text-sm text-neutral-600 dark:text-neutral-400 mb-4 line-clamp-2"
      >
        {{ audit.scope }}
      </p>

      <div
        class="flex items-center gap-4 mb-4 text-sm text-neutral-600 dark:text-neutral-400"
      >
        <div class="flex items-center gap-2">
          <Calendar class="w-4 h-4" />
          <span>{{ formatDate(audit.planned_date) }}</span>
        </div>

        <div class="h-4 w-px bg-neutral-200 dark:bg-neutral-700" />

        <div class="flex items-center gap-2">
          <User class="w-4 h-4" />
          <span>{{ audit.lead_auditor?.name || "N/A" }}</span>
        </div>
      </div>

      <div class="flex items-center justify-between">
        <AppBadge
          :label="getStatusLabel(audit.status)"
          :variant="getStatusVariant(audit.status)"
        />

        <div v-if="audit.conformity_rate" class="flex items-center gap-2">
          <div class="relative w-8 h-8">
            <svg class="w-8 h-8 transform -rotate-90">
              <circle
                class="text-neutral-200 dark:text-neutral-700"
                cx="16"
                cy="16"
                fill="none"
                r="14"
                stroke="currentColor"
                stroke-width="3"
              />
              <circle
                class="transition-all duration-300"
                cx="16"
                cy="16"
                fill="none"
                r="14"
                :stroke="getConformityColor(audit.conformity_rate)"
                :stroke-dasharray="`${2 * Math.PI * 14}`"
                :stroke-dashoffset="`${2 * Math.PI * 14 * (1 - audit.conformity_rate / 100)}`"
                stroke-linecap="round"
                stroke-width="3"
              />
            </svg>
            <span
              class="absolute inset-0 flex items-center justify-center text-xs font-bold"
            >
              {{ audit.conformity_rate }}%
            </span>
          </div>
        </div>
      </div>
    </template>

    <template #footer>
      <div class="flex items-center gap-2">
        <AppButton size="sm" variant="ghost" @click.stop="$emit('view', audit)">
          <Eye class="w-4 h-4 mr-2" />
          Voir
        </AppButton>
        <AppButton size="sm" variant="ghost" @click.stop="$emit('edit', audit)">
          <Pencil class="w-4 h-4 mr-2" />
          Modifier
        </AppButton>
        <div class="flex-1" />
        <AppIconButton
          v-if="audit.report_file"
          ariaLabel="Télécharger le rapport"
          icon="Download"
          size="sm"
          variant="ghost"
          @click.stop="$emit('download', audit)"
        />
      </div>
    </template>
  </AppCard>
</template>

<script setup lang="ts">
  import { Calendar, Download, Eye, Pencil, User } from 'lucide-vue-next'
  import AppBadge from '@/components/common/AppBadge.vue'
  import AppButton from '@/components/common/AppButton.vue'
  import AppCard from '@/components/common/AppCard.vue'
  import AppIconButton from '@/components/common/AppIconButton.vue'

  interface Audit {
    id: number
    reference: string
    title: string
    type: string
    scope: string
    status: string
    planned_date: string
    conformity_rate?: number
    lead_auditor?: {
      name: string
    }
    report_file?: string
  }

  interface Props {
    audit: Audit
  }

  defineProps<Props>()

  defineEmits<{
    (e: 'click' | 'view' | 'edit' | 'download', audit: Audit): void
  }>()

  function getTypeVariant (
    type: string,
  ): 'primary' | 'success' | 'info' | 'audit' {
    const variants: Record<string, 'primary' | 'success' | 'info' | 'audit'> = {
      internal: 'info',
      external: 'audit',
      certification: 'success',
    }
    return variants[type] || 'info'
  }

  function getTypeLabel (type: string): string {
    const labels: Record<string, string> = {
      internal: 'Interne',
      external: 'Externe',
      certification: 'Certification',
    }
    return labels[type] || type
  }

  function getStatusVariant (
    status: string,
  ): 'primary' | 'success' | 'warning' | 'error' | 'info' {
    const variants: Record<
      string,
      'primary' | 'success' | 'warning' | 'error' | 'info'
    > = {
      planned: 'info',
      in_progress: 'warning',
      completed: 'success',
      cancelled: 'error',
    }
    return variants[status] || 'info'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      planned: 'Planifié',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
    }
    return labels[status] || status
  }

  function getConformityColor (rate: number): string {
    if (rate >= 90) return '#10b981'
    if (rate >= 75) return '#f59e0b'
    return '#ef4444'
  }

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  }
</script>
