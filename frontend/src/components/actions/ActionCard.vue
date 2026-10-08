<template>
  <BaseCard
    class="action-card"
    :clickable="clickable"
    :hoverable="hoverable"
    @click="$emit('click', action)"
  >
    <template #header>
      <div class="card-header-content">
        <div class="header-left">
          <div class="header-badges">
            <span class="ref-badge">{{ action.ref }}</span>
            <ActionPriorityBadge :priority="action.priority" size="xs" />
            <ActionTypeBadge size="xs" :type="action.type" />
          </div>
          <h3 class="card-title">
            {{ action.title }}
          </h3>
        </div>
        <StatusBadge
          module="action"
          size="sm"
          :status="action.status"
        />
      </div>
    </template>

    <!-- Description -->
    <div v-if="action.description && showDescription" class="description">
      <p class="description-text">
        {{ action.description }}
      </p>
    </div>

    <!-- Progress Bar -->
    <div v-if="showProgress" class="progress-section">
      <div class="progress-header">
        <span class="progress-label">Progression</span>
        <span class="progress-value">{{ action.progress }}%</span>
      </div>
      <div class="progress-bar">
        <div
          :class="['progress-fill', progressColor]"
          :style="{ width: `${action.progress}%` }"
        />
      </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
      <!-- Responsible -->
      <div class="info-item">
        <div class="info-label">Responsable</div>
        <div class="info-value">
          <div v-if="action.responsible" class="user-info">
            <div
              v-if="action.responsible.avatar"
              class="user-avatar"
            >
              <img :alt="action.responsible.name" :src="action.responsible.avatar">
            </div>
            <div
              v-else
              class="user-avatar-initials"
            >
              {{ getInitials(action.responsible.name) }}
            </div>
            <span class="user-name">
              {{ action.responsible.name }}
            </span>
          </div>
          <span v-else class="no-value">Non assigné</span>
        </div>
      </div>

      <!-- Deadline -->
      <div class="info-item">
        <div class="info-label">Échéance</div>
        <div :class="['info-value', deadlineColor]">
          {{ formatDate(action.deadline) }}
        </div>
      </div>
    </div>

    <!-- Footer: Actions -->
    <template v-if="$slots.actions" #footer>
      <div class="card-actions">
        <slot :action="action" name="actions" />
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { Action } from '@/types/action'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import ActionPriorityBadge from './ActionPriorityBadge.vue'
  import ActionTypeBadge from './ActionTypeBadge.vue'

  interface Props {
    action: Action
    hoverable?: boolean
    clickable?: boolean
    showDescription?: boolean
    showProgress?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    hoverable: true,
    clickable: false,
    showDescription: true,
    showProgress: true,
  })

  defineEmits<{
    click: [action: Action]
  }>()

  const progressColor = computed(() => {
    const progress = props.action.progress
    if (progress >= 75) return 'progress-success'
    if (progress >= 50) return 'progress-primary'
    if (progress >= 25) return 'progress-warning'
    return 'progress-default'
  })

  const deadlineColor = computed(() => {
    const deadline = new Date(props.action.deadline)
    const today = new Date()
    const daysUntil = Math.ceil((deadline.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

    if (daysUntil < 0 && props.action.status !== 'completed') {
      return 'deadline-overdue'
    }
    if (daysUntil <= 7 && props.action.status !== 'completed') {
      return 'deadline-soon'
    }
    return 'deadline-normal'
  })

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
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

<style scoped>
.card-header-content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--spacing-3);
}

.header-left {
  flex: 1;
  min-width: 0;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-1);
}

.ref-badge {
  font-size: var(--font-size-xs);
  font-family: monospace;
  color: var(--text-secondary);
}

.card-title {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.description {
  margin-bottom: var(--spacing-4);
}

.description-text {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.progress-section {
  margin-bottom: var(--spacing-4);
}

.progress-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-1);
}

.progress-label {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
}

.progress-value {
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: var(--color-gray-200);
  border-radius: var(--radius-full);
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: var(--radius-full);
  transition: all var(--transition-base);
}

.progress-success {
  background: var(--color-success-500);
}

.progress-primary {
  background: var(--color-primary-500);
}

.progress-warning {
  background: var(--color-warning-500);
}

.progress-default {
  background: var(--color-gray-400);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-4);
  font-size: var(--font-size-sm);
}

.info-item {
  min-width: 0;
}

.info-label {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-1);
}

.info-value {
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.user-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
}

.user-avatar {
  width: 24px;
  height: 24px;
  border-radius: var(--radius-full);
  background: var(--color-gray-200);
  overflow: hidden;
  flex-shrink: 0;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar-initials {
  width: 24px;
  height: 24px;
  border-radius: var(--radius-full);
  background: var(--color-primary-100);
  color: var(--color-primary-600);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  flex-shrink: 0;
}

.user-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.no-value {
  color: var(--text-tertiary);
}

.deadline-overdue {
  color: var(--color-danger-600);
}

.deadline-soon {
  color: var(--color-warning-600);
}

.deadline-normal {
  color: var(--text-primary);
}

.card-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--spacing-2);
}
</style>
