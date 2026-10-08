<template>
  <div class="criteria-panel">
    <v-alert
      v-if="criteria.length === 0"
      border="start"
      color="info"
      density="comfortable"
      icon="mdi-information-outline"
      variant="tonal"
    >
      Aucun critère disponible pour cette évaluation.
    </v-alert>

    <v-row v-else dense>
      <v-col v-for="criterion in criteria" :key="criterion.key" cols="12" md="6">
        <v-card class="criterion-card" variant="tonal">
          <v-card-text class="pa-4">
            <div class="d-flex align-start justify-space-between ga-3 mb-3">
              <div>
                <div class="text-subtitle-2 font-weight-bold">{{ criterion.label }}</div>
                <div v-if="criterion.description" class="text-caption text-medium-emphasis mt-1">
                  {{ criterion.description }}
                </div>
              </div>
              <v-chip size="x-small" variant="outlined">
                {{ criterion.scaleMin }}-{{ criterion.scaleMax }}
              </v-chip>
            </div>

            <v-skeleton-loader
              v-if="loading"
              class="mb-2"
              type="text"
            />
            <v-btn-toggle
              v-else
              class="rating-toggle mb-2"
              color="primary"
              density="comfortable"
              divided
              mandatory
              :model-value="readCriterionValue(criterion)"
              :readonly="readonly"
              @update:model-value="updateCriterionValue(criterion, $event)"
            >
              <v-btn
                v-for="option in criterion.options"
                :key="`${criterion.key}-${option.value}`"
                size="small"
                :value="option.value"
              >
                {{ option.value }}
              </v-btn>
            </v-btn-toggle>

            <div class="text-caption text-medium-emphasis">
              {{ selectedOptionTitle(criterion) }}
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup lang="ts">
  import type { DynamicCriterion } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { watch } from 'vue'

  const props = withDefaults(defineProps<{
    modelValue: Record<string, number>
    criteria: DynamicCriterion[]
    loading?: boolean
    readonly?: boolean
  }>(), {
    loading: false,
    readonly: false,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Record<string, number>): void
  }>()

  function getDefaultScore (criterion: DynamicCriterion): number {
    return criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin
  }

  function readCriterionValue (criterion: DynamicCriterion): number {
    const value = Number(props.modelValue?.[criterion.key])
    if (Number.isFinite(value) && value >= criterion.scaleMin && value <= criterion.scaleMax) {
      return value
    }
    return getDefaultScore(criterion)
  }

  function selectedOptionTitle (criterion: DynamicCriterion): string {
    const activeValue = readCriterionValue(criterion)
    const found = criterion.options.find(option => Number(option.value) === Number(activeValue))
    return found?.title || `Niveau ${activeValue}`
  }

  function updateCriterionValue (criterion: DynamicCriterion, value: unknown): void {
    const numericValue = Number(value)
    if (!Number.isFinite(numericValue)) return
    emit('update:modelValue', {
      ...props.modelValue,
      [criterion.key]: numericValue,
    })
  }

  watch(
    () => props.criteria,
    value => {
      if (!Array.isArray(value) || value.length === 0) return
      const next = { ...props.modelValue }
      let hasChanges = false

      for (const criterion of value) {
        const current = Number(next[criterion.key])
        if (!Number.isFinite(current) || current < criterion.scaleMin || current > criterion.scaleMax) {
          next[criterion.key] = getDefaultScore(criterion)
          hasChanges = true
        }
      }

      if (hasChanges) {
        emit('update:modelValue', next)
      }
    },
    { immediate: true, deep: true },
  )
</script>

<style scoped>
  .criteria-panel {
    width: 100%;
  }

  .criterion-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.8));
  }

  .rating-toggle {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 6px;
  }
</style>
