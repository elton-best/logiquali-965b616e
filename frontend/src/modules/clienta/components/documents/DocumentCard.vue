<template>
  <v-card
    class="document-card"
    elevation="0"
    rounded="lg"
    @click="$emit('click', document)"
  >
    <v-card-text class="pa-4">
      <div class="d-flex align-start justify-space-between mb-3">
        <div class="d-flex align-center ga-2">
          <TypeBadge
            :name="(document as any).typeConfiguration?.name"
            :type="(document as any).typeConfiguration?.abbreviation ?? (document.metadata as any)?.document_type_abbreviation"
          />
          <WorkflowActionBadge v-if="workflowAction" :action="workflowAction" />
        </div>
        <div class="d-flex ga-1">
          <StateBadge :etat="document.etat" />
          <WorkflowStatusBadge :status="document.workflow_status" />
        </div>
      </div>

      <div class="mb-3">
        <div class="text-subtitle-1 font-weight-semibold text-grey-darken-4 mb-1">
          {{ DocumentHelpers.getTitle(document) }}
        </div>
        <div class="d-flex align-center flex-wrap ga-2">
          <code class="text-caption code-pill">{{ document.code || '—' }}</code>
          <v-chip
            v-if="document.metadata?.code_generated"
            color="amber"
            size="x-small"
            variant="tonal"
          >
            Code auto
          </v-chip>
          <span class="text-caption text-medium-emphasis">v{{ document.version }}</span>
        </div>
      </div>

      <div v-if="document.description" class="text-body-2 text-medium-emphasis mb-3 line-clamp-2">
        {{ document.description }}
      </div>

      <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis">
        <span>{{ document.processus || '—' }}</span>
        <span>{{ formatDate(document.date_creation) }}</span>
      </div>

      <div v-if="document.prochaine_revision" class="mt-3 pt-3 border-t">
        <div class="d-flex align-center ga-1 text-caption" :class="isReviewSoon ? 'text-warning' : 'text-medium-emphasis'">
          <Clock class="w-3 h-3" />
          <span>Révision: {{ formatDate(document.prochaine_revision) }}</span>
        </div>
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import type { UnifiedDocument } from '../../types/document-unified.types'
  import { Clock } from 'lucide-vue-next'
  import { computed } from 'vue'
  import { DocumentHelpers } from '../../types/document-unified.types'
  import StateBadge from './StateBadge.vue'
  import StatusBadge from './StatusBadge.vue'
  import TypeBadge from './TypeBadge.vue'
  import WorkflowStatusBadge from './WorkflowStatusBadge.vue'
  import WorkflowActionBadge from './WorkflowActionBadge.vue'

  const props = defineProps<{
    document: UnifiedDocument
    workflowAction?: 'verification' | 'approval' | 'rejection'
  }>()

  defineEmits<{
    click: [document: UnifiedDocument]
  }>()

  const isReviewSoon = computed(() => {
    if (!props.document.prochaine_revision) return false
    const diff = new Date(props.document.prochaine_revision).getTime() - Date.now()
    return diff < 30 * 24 * 60 * 60 * 1000 // 30 jours
  })

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR')
  }
</script>

<style scoped>
.document-card {
  cursor: pointer;
  border: 1px solid #e2e8f0;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: #ffffff;
}

.document-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.code-pill {
  background: #f1f5f9;
  padding: 2px 8px;
  border-radius: 999px;
}
</style>
