<template>
  <BaseCard hoverable>
    <div class="flex items-start justify-between mb-3">
      <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ objective.title }}
          </h3>
          <BaseBadge :label="priorityLabel" size="sm" :variant="priorityVariant">
            {{ priorityLabel }}
          </BaseBadge>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ objective.code }}</p>
      </div>

      <StatusBadge module="process" :status="objective.status" />
    </div>

    <p v-if="objective.description" class="text-sm text-gray-700 dark:text-gray-300 mb-4 line-clamp-2">
      {{ objective.description }}
    </p>

    <div class="space-y-3">
      <!-- Progress -->
      <div>
        <div class="flex items-center justify-between text-sm mb-1">
          <span class="text-gray-600 dark:text-gray-400">Progression</span>
          <span :class="['font-semibold', achievementColorClass]">
            {{ objective.achievement_rate || 0 }}%
          </span>
        </div>
        <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
          <div
            :class="['h-full transition-all duration-500', achievementBarClass]"
            :style="{ width: `${Math.min(objective.achievement_rate || 0, 100)}%` }"
          />
        </div>
      </div>

      <!-- Target -->
      <div v-if="objective.target_value !== undefined" class="flex items-center justify-between text-sm">
        <span class="text-gray-600 dark:text-gray-400">Cible</span>
        <span class="font-medium">{{ objective.target_value }} {{ objective.unit }}</span>
      </div>

      <!-- Milestones -->
      <div v-if="objective.milestones && objective.milestones.length > 0">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
          Jalons : {{ achievedMilestonesCount }} / {{ objective.milestones.length }}
        </p>
        <div class="flex gap-1">
          <div
            v-for="milestone in objective.milestones"
            :key="milestone.id"
            :class="[
              'flex-1 h-1 rounded-full',
              milestone.achieved ? 'bg-green-500' : 'bg-gray-300'
            ]"
            :title="milestone.title"
          />
        </div>
      </div>

      <!-- Timeline -->
      <div class="flex items-center justify-between text-sm pt-3 border-t border-gray-200 dark:border-gray-700">
        <div>
          <span class="text-gray-600 dark:text-gray-400">Échéance : </span>
          <span :class="['font-medium', isOverdue ? 'text-red-600' : 'text-gray-900 dark:text-white']">
            {{ formatDate(objective.target_date) }}
          </span>
        </div>

        <div v-if="daysRemaining !== null" :class="['text-xs', daysRemainingClass]">
          {{ daysRemainingText }}
        </div>
      </div>

      <!-- Responsible -->
      <div v-if="objective.responsible" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        {{ objective.responsible.name }}
      </div>
    </div>

    <div v-if="showActions" class="flex gap-2 mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
      <button
        class="flex-1 px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
        @click="$emit('view')"
      >
        Détails
      </button>
      <button
        v-if="objective.status === 'active'"
        class="flex-1 px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50 dark:hover:bg-gray-800"
        @click="$emit('update-progress')"
      >
        Mettre à jour
      </button>
    </div>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { Objective } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseBadge from '@/components/common/BaseBadge.vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'

  interface Props {
    objective: Objective
    showActions?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    showActions: true,
  })

  defineEmits<{
    'view': []
    'update-progress': []
    'edit': []
  }>()

  const priorityLabel = computed(() => {
    const labels = {
      low: 'Basse',
      medium: 'Moyenne',
      high: 'Haute',
      critical: 'Critique',
    }
    return labels[props.objective.priority]
  })

  const priorityVariant = computed(() => {
    const variants = {
      low: 'default' as const,
      medium: 'info' as const,
      high: 'warning' as const,
      critical: 'danger' as const,
    }
    return variants[props.objective.priority]
  })

  const achievementColorClass = computed(() => {
    const rate = props.objective.achievement_rate || 0
    if (rate >= 100) return 'text-green-600'
    if (rate >= 75) return 'text-blue-600'
    if (rate >= 50) return 'text-yellow-600'
    return 'text-red-600'
  })

  const achievementBarClass = computed(() => {
    const rate = props.objective.achievement_rate || 0
    if (rate >= 100) return 'bg-green-500'
    if (rate >= 75) return 'bg-blue-500'
    if (rate >= 50) return 'bg-yellow-500'
    return 'bg-red-500'
  })

  const achievedMilestonesCount = computed(() => {
    return props.objective.milestones?.filter(m => m.achieved).length || 0
  })

  const isOverdue = computed(() => {
    if (props.objective.status !== 'active') return false
    return new Date(props.objective.target_date) < new Date()
  })

  const daysRemaining = computed(() => {
    if (props.objective.status !== 'active') return null
    const target = new Date(props.objective.target_date)
    const now = new Date()
    const diff = Math.ceil((target.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))
    return diff
  })

  const daysRemainingText = computed(() => {
    if (daysRemaining.value === null) return ''
    if (daysRemaining.value < 0) return `En retard de ${Math.abs(daysRemaining.value)} j`
    if (daysRemaining.value === 0) return 'Aujourd\'hui'
    if (daysRemaining.value === 1) return 'Demain'
    return `${daysRemaining.value} jours restants`
  })

  const daysRemainingClass = computed(() => {
    if (daysRemaining.value === null) return ''
    if (daysRemaining.value < 0) return 'text-red-600 font-medium'
    if (daysRemaining.value <= 7) return 'text-orange-600 font-medium'
    return 'text-gray-600'
  })

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }
</script>
