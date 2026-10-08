<template>
  <div>
    <div
      v-for="(criterion, index) in sortedCriteria"
      :key="criterion.id"
      class="mb-6"
    >
      <div class="d-flex justify-space-between align-start mb-2">
        <div>
          <h4 class="text-body-1 font-weight-medium">
            {{ index + 1 }}. {{ criterion.name }}
          </h4>
          <p v-if="criterion.description" class="text-body-2 text-medium-emphasis mt-1">
            {{ criterion.description }}
          </p>
        </div>
        <v-chip
          v-if="scores[criterion.id] !== undefined"
          class="ml-2"
          :color="getScoreColor(scores[criterion.id], criterion)"
          size="small"
        >
          {{ scores[criterion.id] }}/{{ criterion.scale_max }}
        </v-chip>
      </div>

      <template v-if="criterion.scale_type === 'stars'">
        <v-rating
          active-color="amber-darken-2"
          color="amber"
          half-increments
          hover
          :length="criterion.scale_max"
          :model-value="scores[criterion.id]"
          :readonly="readonly"
          @update:model-value="onUpdateScore(criterion.id, $event)"
        />
      </template>
      <template v-else-if="criterion.scale_type === 'satisfaction'">
        <v-chip-group
          :disabled="readonly"
          mandatory
          :model-value="scores[criterion.id]"
          selected-class="text-primary"
          @update:model-value="onUpdateScore(criterion.id, $event)"
        >
          <v-chip
            v-for="(label, value) in getSatisfactionLabels(criterion)"
            :key="String(value)"
            filter
            :value="Number(value)"
          >
            {{ label }}
          </v-chip>
        </v-chip-group>
      </template>
      <template v-else>
        <v-slider
          :color="getScoreColor(scores[criterion.id], criterion)"
          :disabled="loading"
          :max="criterion.scale_max"
          :min="criterion.scale_min"
          :model-value="scores[criterion.id]"
          :readonly="readonly"
          show-ticks="always"
          :step="1"
          thumb-label="always"
          tick-size="4"
          @update:model-value="onUpdateScore(criterion.id, $event)"
        >
          <template #prepend>
            <span class="text-caption">{{ criterion.scale_min }}</span>
          </template>
          <template #append>
            <span class="text-caption">{{ criterion.scale_max }}</span>
          </template>
        </v-slider>
        <div v-if="criterion.scale_labels" class="d-flex justify-space-between text-caption text-medium-emphasis mt-n2">
          <span>{{ criterion.scale_labels[String(criterion.scale_min)] || 'Minimum' }}</span>
          <span>{{ criterion.scale_labels[String(criterion.scale_max)] || 'Maximum' }}</span>
        </div>
      </template>

      <v-expand-transition>
        <div v-if="showCommentFor[criterion.id]" class="mt-2">
          <v-textarea
            density="compact"
            hide-details
            label="Commentaire (optionnel)"
            :model-value="comments[criterion.id]"
            :readonly="readonly"
            rows="2"
            variant="outlined"
            @update:model-value="onUpdateComment(criterion.id, $event)"
          />
        </div>
      </v-expand-transition>
      <v-btn
        class="mt-1"
        :disabled="readonly"
        size="x-small"
        variant="text"
        @click="emit('toggle-comment', criterion.id)"
      >
        <v-icon class="mr-1" size="14">
          {{ showCommentFor[criterion.id] ? 'mdi-minus' : 'mdi-plus' }}
        </v-icon>
        {{ showCommentFor[criterion.id] ? 'Masquer' : 'Ajouter un commentaire' }}
      </v-btn>

      <v-divider v-if="index < sortedCriteria.length - 1" class="mt-4" />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface DynamicCriterionInput {
    id: number
    name: string
    description: string | null
    scale_type: string
    scale_min: number
    scale_max: number
    scale_labels: Record<string, string> | null
    display_order: number
  }

  const props = withDefaults(defineProps<{
    criteria: DynamicCriterionInput[]
    scores: Record<number, number>
    comments: Record<number, string>
    showCommentFor: Record<number, boolean>
    loading?: boolean
    readonly?: boolean
  }>(), {
    loading: false,
    readonly: false,
  })

  const emit = defineEmits<{
    (event: 'update-score', criterionId: number, value: number): void
    (event: 'update-comment', criterionId: number, value: string): void
    (event: 'toggle-comment', criterionId: number): void
  }>()

  const sortedCriteria = computed(() => {
    return [...props.criteria].toSorted((a, b) => Number(a.display_order) - Number(b.display_order))
  })

  function onUpdateScore (criterionId: number, value: unknown): void {
    const numeric = Number(value)
    if (!Number.isFinite(numeric)) return
    emit('update-score', criterionId, numeric)
  }

  function onUpdateComment (criterionId: number, value: unknown): void {
    emit('update-comment', criterionId, String(value || ''))
  }

  function getScoreColor (score: number | undefined, criterion: DynamicCriterionInput): string {
    if (score === undefined) return 'grey'
    const range = criterion.scale_max - criterion.scale_min
    const normalized = range > 0 ? (score - criterion.scale_min) / range : 0
    if (normalized >= 0.8) return 'success'
    if (normalized >= 0.6) return 'light-green'
    if (normalized >= 0.4) return 'warning'
    if (normalized >= 0.2) return 'orange'
    return 'error'
  }

  function getSatisfactionLabels (criterion: DynamicCriterionInput): Record<string, string> {
    if (criterion.scale_labels) return criterion.scale_labels

    if (criterion.scale_max === 3) {
      return {
        1: 'Insatisfait',
        2: 'Moyennement satisfait',
        3: 'Satisfait',
      }
    }

    return {
      1: 'Très insatisfait',
      2: 'Insatisfait',
      3: 'Neutre',
      4: 'Satisfait',
      5: 'Très satisfait',
    }
  }
</script>
