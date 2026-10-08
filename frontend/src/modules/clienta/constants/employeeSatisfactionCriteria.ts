import type { DynamicCriterion } from '@/modules/clienta/composables/useEvaluationCriteria'

export const defaultEmployeeSatisfactionCriteria: DynamicCriterion[] = [
  {
    key: 'ambiance_travail',
    label: 'Ambiance de travail',
    description: null,
    scaleMin: 1,
    scaleMax: 3,
    weight: 1,
    required: false,
    options: [
      { value: 1, title: '1 - Insatisfait' },
      { value: 2, title: '2 - Moyennement satisfait' },
      { value: 3, title: '3 - Satisfait' },
    ],
  },
  {
    key: 'communication_interne',
    label: 'Communication interne',
    description: null,
    scaleMin: 1,
    scaleMax: 3,
    weight: 1,
    required: false,
    options: [
      { value: 1, title: '1 - Insatisfait' },
      { value: 2, title: '2 - Moyennement satisfait' },
      { value: 3, title: '3 - Satisfait' },
    ],
  },
  {
    key: 'conditions_travail',
    label: 'Conditions de travail',
    description: null,
    scaleMin: 1,
    scaleMax: 3,
    weight: 1,
    required: false,
    options: [
      { value: 1, title: '1 - Insatisfait' },
      { value: 2, title: '2 - Moyennement satisfait' },
      { value: 3, title: '3 - Satisfait' },
    ],
  },
  {
    key: 'reconnaissance',
    label: 'Reconnaissance',
    description: null,
    scaleMin: 1,
    scaleMax: 3,
    weight: 1,
    required: false,
    options: [
      { value: 1, title: '1 - Insatisfait' },
      { value: 2, title: '2 - Moyennement satisfait' },
      { value: 3, title: '3 - Satisfait' },
    ],
  },
  {
    key: 'support_managerial',
    label: 'Support managérial',
    description: null,
    scaleMin: 1,
    scaleMax: 3,
    weight: 1,
    required: false,
    options: [
      { value: 1, title: '1 - Insatisfait' },
      { value: 2, title: '2 - Moyennement satisfait' },
      { value: 3, title: '3 - Satisfait' },
    ],
  },
]
