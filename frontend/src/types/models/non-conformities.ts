/**
 * Non-Conformity Management Module Types
 */

export interface NonConformity {
  id: number
  tenant_id: number
  reference: string
  title: string
  description: string
  category: NCCategory
  severity: NCSeverity
  status: NCStatus
  source: NCSource
  detected_date: string
  detected_by_id: number
  detected_by?: { id: number, name: string }
  site_id?: number
  site?: { id: number, name: string }
  process_id?: number
  process?: { id: number, name: string }
  assigned_to_id?: number
  assigned_to?: { id: number, name: string }
  root_cause?: string
  immediate_action?: string
  corrective_actions?: CorrectiveAction[]
  preventive_actions?: PreventiveAction[]
  verification?: NCVerification
  cost_impact?: number
  attachments?: Attachment[]
  closed_at?: string
  closed_by_id?: number
  created_at: string
  updated_at: string
}

export type NCCategory
  = | 'quality'
    | 'safety'
    | 'environment'
    | 'health'
    | 'process'
    | 'documentation'
    | 'other'

export type NCSeverity = 'minor' | 'major' | 'critical'

export type NCStatus
  = | 'open'
    | 'investigating'
    | 'action_plan'
    | 'implementing'
    | 'verifying'
    | 'closed'
    | 'cancelled'

export type NCSource
  = | 'internal_audit'
    | 'external_audit'
    | 'inspection'
    | 'customer_complaint'
    | 'employee_report'
    | 'incident'
    | 'other'

export interface CorrectiveAction {
  id: number
  description: string
  responsible_id: number
  responsible?: { id: number, name: string }
  due_date: string
  status: 'pending' | 'in_progress' | 'completed'
  completion_date?: string
  evidence?: string[]
}

export interface PreventiveAction {
  id: number
  description: string
  responsible_id: number
  responsible?: { id: number, name: string }
  due_date: string
  status: 'pending' | 'in_progress' | 'completed'
  completion_date?: string
  evidence?: string[]
}

export interface NCVerification {
  verified_by_id: number
  verified_by?: { id: number, name: string }
  verified_at: string
  is_effective: boolean
  comments: string
}

export interface Attachment {
  id: number
  name: string
  url: string
  size: number
  type: string
  uploaded_at: string
}

export interface CreateNCPayload {
  title: string
  description: string
  category: NCCategory
  severity: NCSeverity
  source: NCSource
  detected_date: string
  site_id?: number
  process_id?: number
  assigned_to_id?: number
  immediate_action?: string
  attachments?: File[]
}

export interface UpdateNCPayload {
  title?: string
  description?: string
  category?: NCCategory
  severity?: NCSeverity
  status?: NCStatus
  assigned_to_id?: number
  root_cause?: string
  immediate_action?: string
  cost_impact?: number
}
