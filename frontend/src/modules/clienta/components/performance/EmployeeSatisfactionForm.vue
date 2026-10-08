<template>
  <v-row>
    <v-col cols="12" md="6">
      <v-text-field
        label="Collaborateur"
        :model-value="modelValue.respondent_name"
        :readonly="readonly"
        variant="outlined"
        @update:model-value="updateField('respondent_name', $event)"
      />
    </v-col>
    <v-col cols="12" md="6">
      <v-text-field
        label="Email"
        :model-value="modelValue.respondent_email"
        :readonly="readonly"
        type="email"
        variant="outlined"
        @update:model-value="updateField('respondent_email', $event)"
      />
    </v-col>
    <v-col cols="12" md="6">
      <v-text-field
        label="Année"
        :model-value="modelValue.year"
        :readonly="readonly"
        type="number"
        variant="outlined"
        @update:model-value="updateField('year', Number($event) || currentYear)"
      />
    </v-col>
    <v-col cols="12" md="6">
      <v-text-field
        label="Période"
        :model-value="modelValue.period"
        placeholder="Ex: T1"
        :readonly="readonly"
        variant="outlined"
        @update:model-value="updateField('period', String($event || ''))"
      />
    </v-col>

    <v-col cols="12">
      <v-alert
        v-if="usesFallbackCriteria"
        border="start"
        color="warning"
        density="comfortable"
        icon="mdi-alert-outline"
        variant="tonal"
      >
        Les critères paramétrés n'ont pas pu être chargés. Les critères par défaut sont utilisés.
      </v-alert>
    </v-col>

    <v-col cols="12">
      <CriteriaRatingPanel
        :criteria="criteria"
        :loading="loadingCriteria"
        :model-value="modelValue.responses"
        :readonly="readonly"
        @update:model-value="responses => updateField('responses', responses)"
      />
    </v-col>

    <v-col cols="12">
      <v-textarea
        label="Recommandations"
        :model-value="modelValue.recommendations"
        :readonly="readonly"
        rows="3"
        variant="outlined"
        @update:model-value="updateField('recommendations', String($event || ''))"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import { watch } from 'vue'
  import CriteriaRatingPanel from '@/modules/clienta/components/performance/CriteriaRatingPanel.vue'
  import { type DynamicCriterion, useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { defaultEmployeeSatisfactionCriteria } from '@/modules/clienta/constants/employeeSatisfactionCriteria'

  export interface EmployeeSatisfactionFormModel {
    respondent_name: string
    respondent_email: string
    year: number
    period: string
    responses: Record<string, number>
    recommendations: string
  }

  const currentYear = new Date().getFullYear()

  const props = withDefaults(defineProps<{
    modelValue: EmployeeSatisfactionFormModel
    readonly?: boolean
  }>(), {
    readonly: false,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: EmployeeSatisfactionFormModel): void
  }>()

  const {
    criteria,
    loadingCriteria,
    usesFallbackCriteria,
  } = useEvaluationCriteria('satisfaction_personnel', defaultEmployeeSatisfactionCriteria)

  function updateModel (patch: Partial<EmployeeSatisfactionFormModel>) {
    emit('update:modelValue', {
      ...props.modelValue,
      ...patch,
    })
  }

  function updateField<K extends keyof EmployeeSatisfactionFormModel> (key: K, value: EmployeeSatisfactionFormModel[K]) {
    updateModel({ [key]: value } as Partial<EmployeeSatisfactionFormModel>)
  }

  function getDefaultScore (criterion: DynamicCriterion): number {
    return criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin
  }

  function normalizeResponses (activeCriteria: DynamicCriterion[]) {
    const currentResponses = props.modelValue.responses || {}
    let hasChanged = false
    const nextResponses: Record<string, number> = { ...currentResponses }

    for (const criterion of activeCriteria) {
      const currentValue = Number(currentResponses[criterion.key])
      if (
        !Number.isFinite(currentValue)
        || currentValue < criterion.scaleMin
        || currentValue > criterion.scaleMax
      ) {
        nextResponses[criterion.key] = getDefaultScore(criterion)
        hasChanged = true
      }
    }

    if (hasChanged) {
      updateModel({ responses: nextResponses })
    }
  }

  watch(criteria, value => {
    if (value.length > 0) {
      normalizeResponses(value)
    }
  }, { immediate: true })
</script>
