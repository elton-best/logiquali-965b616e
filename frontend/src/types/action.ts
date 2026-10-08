/**
 * Types TypeScript pour le module Actions
 */

import type {
  ActionPriority,
  ActionStatus,
  ActionType,
  BaseEntity,
  ProcessReference,
  SiteReference,
  UserReference,
} from './shared'

export interface Action extends BaseEntity {
  ref: string
  site_id: number
  site?: SiteReference

  process_id?: number
  process?: ProcessReference

  plan_action_id?: number
  plan_action?: PlanAction

  workflow_state_id?: number

  type: ActionType
  priority: ActionPriority
  title: string
  description?: string
  source?: string

  initiator_id?: number
  initiator?: UserReference

  root_cause?: string
  immediate_action?: string
  immediate_action_date?: string

  responsible_id: number
  responsible?: UserReference

  approved_by?: number
  approver?: UserReference
  approval_date?: string
  approval_notes?: string

  deadline: string
  progress: number
  progress_notes?: ProgressNote[]

  estimated_cost?: number
  actual_cost?: number
  required_resources?: string

  status: ActionStatus

  effectiveness_verified: boolean
  verification_date?: string
  verification_notes?: string

  // Relations
  non_conformities?: any[]
  audits?: any[]
  risks?: any[]
  objectives?: any[]
  reclamations?: any[]
}

export interface ProgressNote {
  date: string
  progress: number
  note: string
  user_id: number
  user_name?: string
}

export interface PlanAction extends BaseEntity {
  ref: string
  site_id: number
  site?: SiteReference

  title: string
  description?: string
  objective?: string

  start_date: string
  end_date: string

  coordinator_id?: number
  coordinator?: UserReference

  status: 'draft' | 'active' | 'completed' | 'cancelled'

  total_estimated_cost?: number
  total_actual_cost?: number

  actions?: Action[]
  actions_count?: number
  completed_actions_count?: number

  progress?: number
}

export interface ActionFilters {
  search?: string
  type?: ActionType | ActionType[]
  priority?: ActionPriority | ActionPriority[]
  status?: ActionStatus | ActionStatus[]
  responsible_id?: number
  site_id?: number
  plan_action_id?: number
  overdue?: boolean
  due_date_from?: string
  due_date_to?: string
  page?: number
  per_page?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export interface CreateActionPayload {
  site_id: number
  plan_action_id?: number
  type: ActionType
  priority: ActionPriority
  title: string
  description?: string
  source?: string
  root_cause?: string
  immediate_action?: string
  immediate_action_date?: string
  responsible_id: number
  deadline: string
  estimated_cost?: number
  required_resources?: string
}

export interface UpdateActionPayload extends Partial<CreateActionPayload> {
  progress?: number
  progress_notes?: string
  status?: ActionStatus
}

export interface UpdateProgressPayload {
  progress: number
  notes?: string
}

export interface VerifyEffectivenessPayload {
  is_effective: boolean
  verification_notes?: string
}

export interface ActionStatistics {
  total: number
  by_status: {
    pending: number
    in_progress: number
    completed: number
    cancelled: number
    overdue: number
  }
  by_priority: {
    low: number
    medium: number
    high: number
    critical: number
  }
  by_type: {
    corrective: number
    preventive: number
    improvement: number
    routine: number
  }
  completion_rate: number
  overdue_count: number
  average_completion_days?: number
}
