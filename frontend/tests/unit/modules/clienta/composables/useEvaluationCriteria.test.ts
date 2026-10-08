import { beforeEach, describe, expect, it, vi } from 'vitest'
import evaluationCriteriaApi from '@/api/services/evaluationCriteria'
import {
  buildCriterionOptions,
  fetchEvaluationCriteriaWithFallback,
} from '@/modules/clienta/composables/useEvaluationCriteria'
import { defaultEmployeeSatisfactionCriteria } from '@/modules/clienta/constants/employeeSatisfactionCriteria'

vi.mock('@/api/services/evaluationCriteria', () => ({
  default: {
    list: vi.fn(),
  },
}))

describe('useEvaluationCriteria helpers', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.spyOn(console, 'error').mockImplementation(() => undefined)
  })

  it('builds option labels from explicit scale labels', () => {
    expect(buildCriterionOptions(1, 3, { 1: 'Faible', 2: 'Moyen', 3: 'Fort' })).toEqual([
      { value: 1, title: '1 - Faible' },
      { value: 2, title: '2 - Moyen' },
      { value: 3, title: '3 - Fort' },
    ])
  })

  it('returns remote active criteria when the api responds', async () => {
    vi.mocked(evaluationCriteriaApi.list).mockResolvedValue({
      data: [
        {
          id: 2,
          code: 'communication_interne',
          name: 'Communication interne',
          description: 'Clarté des informations',
          scale_min: 1,
          scale_max: 5,
          scale_labels: { 5: 'Excellent' },
          weight: 1,
          is_mandatory: true,
          is_active: true,
          display_order: 2,
        },
        {
          id: 1,
          code: 'ambiance_travail',
          name: 'Ambiance de travail',
          description: null,
          scale_min: 1,
          scale_max: 5,
          scale_labels: null,
          weight: 1,
          is_mandatory: false,
          is_active: true,
          display_order: 1,
        },
      ],
    } as any)

    const result = await fetchEvaluationCriteriaWithFallback(
      'evaluation_personnel',
      defaultEmployeeSatisfactionCriteria,
    )

    expect(evaluationCriteriaApi.list).toHaveBeenCalledWith({
      form_type: 'evaluation_personnel',
      is_active: true,
      per_page: 200,
    })
    expect(result.usesFallback).toBe(false)
    expect(result.criteria.map(item => item.key)).toEqual([
      'ambiance_travail',
      'communication_interne',
    ])
    expect(result.criteria[1].options[4]).toEqual({
      value: 5,
      title: '5 - Excellent',
    })
  })

  it('falls back to default criteria when the api fails', async () => {
    vi.mocked(evaluationCriteriaApi.list).mockRejectedValue(new Error('network'))

    const result = await fetchEvaluationCriteriaWithFallback(
      'evaluation_personnel',
      defaultEmployeeSatisfactionCriteria,
    )

    expect(result.usesFallback).toBe(true)
    expect(result.criteria).toEqual(defaultEmployeeSatisfactionCriteria)
  })
})
