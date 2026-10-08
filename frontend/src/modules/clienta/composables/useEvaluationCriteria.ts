import { type MaybeRefOrGetter, ref, type Ref, toValue, watch } from 'vue'
import evaluationCriteriaApi, {
  type EvaluationCriteria,
  type EvaluationFormType,
} from '@/api/services/evaluationCriteria'

export interface DynamicCriterionOption {
  value: number
  title: string
}

export interface DynamicCriterion {
  id?: number
  key: string
  label: string
  description: string | null
  scaleMin: number
  scaleMax: number
  weight: number
  required: boolean
  options: DynamicCriterionOption[]
}

function getDefaultOptionLabel (value: number, scaleMin: number, scaleMax: number): string {
  if (scaleMin === 1 && scaleMax === 3) {
    const labels: Record<number, string> = {
      1: 'Insatisfait',
      2: 'Moyennement satisfait',
      3: 'Satisfait',
    }
    return labels[value] || `Niveau ${value}`
  }

  if (scaleMin === 1 && scaleMax === 5) {
    const labels: Record<number, string> = {
      1: 'Très insatisfait',
      2: 'Insatisfait',
      3: 'Neutre',
      4: 'Satisfait',
      5: 'Très satisfait',
    }
    return labels[value] || `Niveau ${value}`
  }

  return `Niveau ${value}`
}

export function buildCriterionOptions (
  scaleMin: number,
  scaleMax: number,
  scaleLabels?: Record<string, string> | null,
): DynamicCriterionOption[] {
  const normalizedMin = Number.isFinite(scaleMin) ? scaleMin : 1
  const normalizedMax = Number.isFinite(scaleMax) ? scaleMax : normalizedMin

  const options: DynamicCriterionOption[] = []
  for (let value = normalizedMin; value <= normalizedMax; value += 1) {
    const explicitLabel = scaleLabels?.[String(value)] || scaleLabels?.[value]
    options.push({
      value,
      title: `${value} - ${explicitLabel || getDefaultOptionLabel(value, normalizedMin, normalizedMax)}`,
    })
  }

  return options
}

export function mapEvaluationCriteriaToDynamicCriterion (criterion: EvaluationCriteria): DynamicCriterion {
  const key = String(criterion.code || criterion.name || `criterion_${criterion.id}`)
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')

  return {
    id: Number(criterion.id),
    key,
    label: criterion.name,
    description: criterion.description,
    scaleMin: Number(criterion.scale_min) || 1,
    scaleMax: Number(criterion.scale_max) || 5,
    weight: Number(criterion.weight) || 1,
    required: Boolean(criterion.is_mandatory),
    options: buildCriterionOptions(
      Number(criterion.scale_min) || 1,
      Number(criterion.scale_max) || 5,
      criterion.scale_labels,
    ),
  }
}

export async function fetchEvaluationCriteriaWithFallback (
  formType: EvaluationFormType,
  fallbackCriteria: DynamicCriterion[] = [],
): Promise<{ criteria: DynamicCriterion[], usesFallback: boolean }> {
  try {
    const response = await evaluationCriteriaApi.list({
      form_type: formType,
      is_active: true,
      per_page: 200,
    })

    const remoteCriteria = Array.isArray(response.data)
      ? response.data
          .slice()
          .toSorted((a, b) => Number(a.display_order || 0) - Number(b.display_order || 0))
          .map(mapEvaluationCriteriaToDynamicCriterion)
      : []

    if (remoteCriteria.length > 0) {
      return { criteria: remoteCriteria, usesFallback: false }
    }
  } catch (error) {
    console.error('Error fetching evaluation criteria:', error)
  }

  return {
    criteria: fallbackCriteria.map(item => ({ ...item, options: item.options.slice() })),
    usesFallback: true,
  }
}

export function useEvaluationCriteria (
  formType: MaybeRefOrGetter<EvaluationFormType>,
  fallbackCriteria: DynamicCriterion[] = [],
): {
  criteria: Ref<DynamicCriterion[]>
  loadingCriteria: Ref<boolean>
  usesFallbackCriteria: Ref<boolean>
  reloadCriteria: () => Promise<void>
} {
  const criteria = ref<DynamicCriterion[]>(fallbackCriteria.map(item => ({ ...item, options: item.options.slice() })))
  const loadingCriteria = ref(false)
  const usesFallbackCriteria = ref(fallbackCriteria.length > 0)

  async function reloadCriteria (): Promise<void> {
    loadingCriteria.value = true
    try {
      const result = await fetchEvaluationCriteriaWithFallback(toValue(formType), fallbackCriteria)
      criteria.value = result.criteria
      usesFallbackCriteria.value = result.usesFallback
    } finally {
      loadingCriteria.value = false
    }
  }

  watch(
    () => toValue(formType),
    () => {
      void reloadCriteria()
    },
    { immediate: true },
  )

  return {
    criteria,
    loadingCriteria,
    usesFallbackCriteria,
    reloadCriteria,
  }
}
