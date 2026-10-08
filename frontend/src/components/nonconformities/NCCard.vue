<template>
  <BaseCard
    class="nc-card"
    :clickable="clickable"
    :hoverable="hoverable"
    :variant="cardVariant"
    @click="$emit('click', nc)"
  >
    <template #header>
      <div class="card-header-content">
        <div class="header-left">
          <div class="header-badges">
            <span class="ref-badge">{{ nc.ref }}</span>
            <NCSeverityBadge :severity="nc.severity" size="xs" />
            <NCTypeBadge size="xs" :type="nc.type" />
          </div>
          <h3 class="card-title">
            {{ nc.title }}
          </h3>
        </div>
        <StatusBadge
          module="nc"
          size="sm"
          :status="nc.status"
        />
      </div>
    </template>

    <!-- Description -->
    <div v-if="nc.description && showDescription" class="description">
      <p class="description-text">
        {{ nc.description }}
      </p>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
      <!-- Source -->
      <div class="info-item">
        <div class="info-label">Source</div>
        <div class="info-value">
          {{ nc.source || 'Non spécifiée' }}
        </div>
      </div>

      <!-- Detected At -->
      <div class="info-item">
        <div class="info-label">Détectée le</div>
        <div class="info-value">
          {{ formatDate(nc.detected_at) }}
        </div>
      </div>

      <!-- Responsible (if assigned) -->
      <div v-if="nc.responsible" class="info-item">
        <div class="info-label">Responsable</div>
        <div class="user-info">
          <div
            v-if="nc.responsible.avatar"
            class="user-avatar"
          >
            <img :alt="nc.responsible.name" :src="nc.responsible.avatar">
          </div>
          <div
            v-else
            class="user-avatar-initials"
          >
            {{ getInitials(nc.responsible.name) }}
          </div>
          <span class="user-name">
            {{ nc.responsible.name }}
          </span>
        </div>
      </div>

      <!-- Deadline (if exists) -->
      <div v-if="nc.deadline" class="info-item">
        <div class="info-label">Échéance</div>
        <div :class="['info-value', deadlineColor]">
          {{ formatDate(nc.deadline) }}
        </div>
      </div>
    </div>

    <!-- Axes QHSE (if exists) -->
    <div v-if="nc.axes && nc.axes.length > 0" class="axes-container">
      <span
        v-for="axis in nc.axes"
        :key="axis"
        :class="['axis-badge', axisClass(axis)]"
      >
        {{ axis }}
      </span>
    </div>

    <!-- Footer: Actions -->
    <template v-if="$slots.actions" #footer>
      <div class="card-actions">
        <slot name="actions" :nc="nc" />
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { NonConformity } from '@/types/nonConformity'
  import type { QHSEAxis } from '@/types/shared'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import NCSeverityBadge from './NCSeverityBadge.vue'
  import NCTypeBadge from './NCTypeBadge.vue'

  interface Props {
    nc: NonConformity
    hoverable?: boolean
    clickable?: boolean
    showDescription?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    hoverable: true,
    clickable: false,
    showDescription: true,
  })

  defineEmits<{
    click: [nc: NonConformity]
  }>()

  const cardVariant = computed(() => {
    if (props.nc.severity === 'majeur') return 'danger'
    if (props.nc.severity === 'mineur') return 'warning'
    return 'default'
  })

  const deadlineColor = computed(() => {
    if (!props.nc.deadline) return ''

    const deadline = new Date(props.nc.deadline)
    const today = new Date()
    const daysUntil = Math.ceil((deadline.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

    if (daysUntil < 0 && props.nc.status !== 'clos') {
      return 'deadline-overdue'
    }
    if (daysUntil <= 7 && props.nc.status !== 'clos') {
      return 'deadline-soon'
    }
    return 'deadline-normal'
  })

  function axisClass (axis: QHSEAxis): string {
    const classes: Record<QHSEAxis, string> = {
      Q: 'axis-q',
      H: 'axis-h',
      S: 'axis-s',
      E: 'axis-e',
    }
    return classes[axis]
  }

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

.deadline-overdue {
  color: var(--color-danger-600);
}

.deadline-soon {
  color: var(--color-warning-600);
}

.deadline-normal {
  color: var(--text-primary);
}

.axes-container {
  display: flex;
  gap: var(--spacing-2);
  margin-top: var(--spacing-3);
}

.axis-badge {
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
}

.axis-q {
  background: var(--color-primary-100);
  color: var(--color-primary-700);
}

.axis-h {
  background: var(--color-success-100);
  color: var(--color-success-700);
}

.axis-s {
  background: #ede9fe;
  color: #7c3aed;
}

.axis-e {
  background: #d1fae5;
  color: #047857;
}

.card-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--spacing-2);
}
</style>
