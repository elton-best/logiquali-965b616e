<template>
  <BaseCard :clickable="clickable" :hoverable="hoverable" @click="$emit('click', document)">
    <template #header>
      <div class="card-header-content">
        <div class="header-left">
          <div class="header-badges">
            <span class="ref-badge">{{ document.ref }}</span>
            <DocumentTypeBadge size="xs" :type="document.type" :name="(document as any).type_configuration_name" />
          </div>
          <h3 class="card-title">
            {{ document.title }}
          </h3>
        </div>
        <StatusBadge module="document" size="sm" :status="document.status" />
      </div>
    </template>

    <div v-if="document.description" class="description">
      <p class="description-text">
        {{ document.description }}
      </p>
    </div>

    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Version</div>
        <div class="version-value">
          {{ document.version }}
        </div>
      </div>

      <div v-if="document.author" class="info-item">
        <div class="info-label">Auteur</div>
        <div class="info-value">
          {{ document.author.name }}
        </div>
      </div>

      <div v-if="document.effective_date" class="info-item">
        <div class="info-label">Date d'effet</div>
        <div class="info-value">
          {{ formatDate(document.effective_date) }}
        </div>
      </div>

      <div v-if="document.review_due_date" class="info-item">
        <div class="info-label">Révision</div>
        <div :class="['info-value', reviewColor]">
          {{ formatDate(document.review_due_date) }}
        </div>
      </div>
    </div>

    <div v-if="document.tags && document.tags.length > 0" class="tags-container">
      <span
        v-for="tag in document.tags"
        :key="tag"
        class="tag"
      >
        {{ tag }}
      </span>
    </div>

    <div v-if="document.is_confidential" class="confidential-badge">
      <svg class="lock-icon" fill="currentColor" viewBox="0 0 20 20">
        <path clip-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" fill-rule="evenodd" />
      </svg>
      <span>{{ confidentialityLabel }}</span>
    </div>

    <template v-if="$slots.actions" #footer>
      <div class="card-actions">
        <slot :document="document" name="actions" />
      </div>
    </template>
  </BaseCard>
</template>

<script setup lang="ts">
  import type { Document } from '@/types/document'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import StatusBadge from '@/components/common/StatusBadge.vue'
  import DocumentTypeBadge from './DocumentTypeBadge.vue'

  interface Props {
    document: Document
    hoverable?: boolean
    clickable?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    hoverable: true,
    clickable: false,
  })

  defineEmits<{
    click: [document: Document]
  }>()

  const reviewColor = computed(() => {
    if (!props.document.review_due_date) return ''
    const today = new Date()
    const reviewDate = new Date(props.document.review_due_date)
    const daysUntil = Math.ceil((reviewDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

    if (daysUntil < 0) return 'review-overdue'
    if (daysUntil <= 30) return 'review-soon'
    return 'review-normal'
  })

  const confidentialityLabel = computed(() => {
    const labels: Record<string, string> = {
      public: 'Public',
      internal: 'Interne',
      confidential: 'Confidentiel',
      restricted: 'Restreint',
    }
    return labels[props.document.confidentiality_level || 'confidential'] || 'Confidentiel'
  })

  function formatDate (date: string): string {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
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
  margin-bottom: var(--spacing-3);
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
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.version-value {
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.review-overdue {
  color: var(--color-danger-600);
}

.review-soon {
  color: var(--color-warning-600);
}

.review-normal {
  color: var(--text-primary);
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-1);
  margin-top: var(--spacing-3);
}

.tag {
  padding: var(--spacing-1) var(--spacing-2);
  background: var(--color-gray-100);
  color: var(--text-secondary);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
}

.confidential-badge {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-top: var(--spacing-3);
  font-size: var(--font-size-xs);
  color: var(--color-danger-600);
}

.lock-icon {
  width: 16px;
  height: 16px;
}

.card-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: var(--spacing-2);
}
</style>
