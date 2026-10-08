/**
 * Action Management Module Types
 * (Corrective & Preventive Actions)
 */

export interface Action {
  id: number
  tenant_id: number
  reference: string
  title: string
  description: string
  type: ActionType
  priority: ActionPriority
  status: ActionStatus

  // Source
  source_type: ActionSource
  source_id?: number
  source?: {
    type: string
    reference: string
    title: string
  }

  // Assignment
  responsible_id: number
  responsible?: { id: number, name: string }
  site_id?: number
  site?: { id: number, name: string }

  // Timeline
  planned_start_date: string
  planned_end_date: string
  actual_start_date?: string
  actual_end_date?: string

  // Implementation
  implementation_plan?: string
  resources_needed?: string
  estimated_cost?: number
  actual_cost?: number

  // Verification
  verification_method?: string
  verification_criteria?: string
  verified_by_id?: number
  verified_by?: { id: number, name: string }
  verified_at?: string
  is_effective?: boolean
  verification_notes?: string

  // Progress
  progress_percentage: number
  milestones?: ActionMilestone[]
  updates?: ActionUpdate[]

  // Files
  attachments?: Attachment[]
  evidence?: Attachment[]

  created_at: string
  updated_at: string
}

export type ActionType
  = | 'corrective'
    | 'preventive'
    | 'improvement'

export type ActionPriority = 'low' | 'medium' | 'high' | 'urgent'

export type ActionStatus
  = | 'planned'
    | 'in_progress'
    | 'pending_verification'
    | 'verified_effective'
    | 'verified_ineffective'
    | 'completed'
    | 'cancelled'

export type ActionSource
  = | 'non_conformity'
    | 'audit'
    | 'risk'
    | 'complaint'
    | 'incident'
    | 'inspection'
    | 'management_review'
    | 'other'

export interface ActionMilestone {
  id: number
  title: string
  description?: string
  due_date: string
  completed_date?: string
  status: 'pending' | 'completed'
}

export interface ActionUpdate {
  id: number
  user_id: number
  user?: { id: number, name: string }
  comment: string
  progress_percentage?: number
  created_at: string
}

export interface Attachment {
  id: number
  name: string
  url: string
  size: number
  type: string
  uploaded_at: string
}

export interface ActionStatistics {
  total: number
  by_type: Record<ActionType, number>
  by_status: Record<ActionStatus, number>
  by_priority: Record<ActionPriority, number>
  overdue: number
  completion_rate: number
  average_completion_time_days: number
  effectiveness_rate: number
}

export interface CreateActionPayload {
  title: string
  description: string
  type: ActionType
  priority: ActionPriority
  source_type: ActionSource
  source_id?: number
  responsible_id: number
  site_id?: number
  planned_start_date: string
  planned_end_date: string
  implementation_plan?: string
  resources_needed?: string
  estimated_cost?: number
}

export interface UpdateActionPayload {
  title?: string
  description?: string
  type?: ActionType
  priority?: ActionPriority
  status?: ActionStatus
  responsible_id?: number
  planned_start_date?: string
  planned_end_date?: string
  actual_start_date?: string
  actual_end_date?: string
  implementation_plan?: string
  resources_needed?: string
  estimated_cost?: number
  actual_cost?: number
  progress_percentage?: number
}

export interface VerifyActionPayload {
  verification_method: string
  verification_criteria: string
  is_effective: boolean
  verification_notes?: string
  evidence?: File[]
}
