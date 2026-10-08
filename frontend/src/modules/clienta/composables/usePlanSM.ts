import { computed, ref } from 'vue'
import api from '@/api/client'

export interface PlanSMTask {
  id: string
  type: string
  title: string
  start_date: string
  deadline: string
  status: string
  responsible_id: number | null
  responsible_name: string | null
  involved_people: number[]
  frequency: string
  process_id: number | null
  color: string
}

export interface PlanSMResponse {
  data: PlanSMTask[]
  pagination: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
  filters: {
    view: string
    start_date: string
    end_date: string
    user_ids: number[]
    process_id: number | null
    types: string[]
  }
}

export function usePlanSM () {
  const tasks = ref<PlanSMTask[]>([])
  const loading = ref(false)
  const pagination = ref({
    total: 0,
    per_page: 50,
    current_page: 1,
    last_page: 1,
  })

  // Filters
  const view = ref<'day' | 'week' | 'month'>('month')
  const selectedUserIds = ref<number[]>([])
  const selectedProcessId = ref<number | null>(null)
  const selectedTypes = ref<string[]>([])

  const typeLabels: Record<string, string> = {
    action: 'Actions',
    audit: 'Audits',
    formation: 'Formations',
    communication: 'Communications',
    non_conformity: 'Non-conformités',
    objective: 'Objectifs',
    risk: 'Risques',
    plan_action: 'Plans d\'action',
    maintenance_plan: 'Plans de maintenance',
    calibration_plan: 'Plans d\'étalonnage',
    compliance_obligation_action: 'Actions conformité',
    stakeholder_requirement_action: 'Actions parties prenantes',
    process_risk_opportunity: 'Risques/opportunités processus',
    operational_project_activity: 'Activités projet',
    operational_project_task: 'Tâches projet',
  }

  const typeColors: Record<string, string> = {
    action: '#FF5252',
    audit: '#FF9800',
    formation: '#2196F3',
    communication: '#FFC107',
    non_conformity: '#E91E63',
    objective: '#00BCD4',
    risk: '#FF6F00',
    plan_action: '#8BC34A',
    maintenance_plan: '#9C27B0',
    calibration_plan: '#4CAF50',
    compliance_obligation_action: '#673AB7',
    stakeholder_requirement_action: '#009688',
    process_risk_opportunity: '#F57F17',
    operational_project_activity: '#3F51B5',
    operational_project_task: '#512DA8',
  }

  const availableTypes = computed(() =>
    Object.entries(typeLabels).map(([key, label]) => ({
      id: key,
      label,
      color: typeColors[key] || '#9E9E9E',
    })),
  )

  async function fetchTasks () {
    loading.value = true
    try {
      const params = new URLSearchParams()
      params.append('view', view.value)

      if (selectedUserIds.value.length > 0) {
        for (const id of selectedUserIds.value) {
          params.append('user_id', String(id))
        }
      }

      if (selectedProcessId.value) {
        params.append('process_id', String(selectedProcessId.value))
      }

      if (selectedTypes.value.length > 0) {
        for (const type of selectedTypes.value) {
          params.append('type', type)
        }
      }

      const response = await api.get<PlanSMResponse>(`/plan-sm?${params.toString()}`)
      tasks.value = response.data.data
      pagination.value = response.data.pagination
    } catch (error) {
      console.error('Error fetching plan-sm tasks:', error)
      tasks.value = []
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    tasks,
    loading,
    pagination,
    view,
    selectedUserIds,
    selectedProcessId,
    selectedTypes,

    // Computed
    availableTypes,
    typeLabels,
    typeColors,

    // Methods
    fetchTasks,
  }
}
