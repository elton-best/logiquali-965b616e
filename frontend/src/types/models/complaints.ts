/**
 * Complaints, Claims & Suggestions Module Types
 */

export interface Complaint {
  id: number
  tenant_id: number
  reference: string
  type: ComplaintType
  title: string
  description: string
  category: ComplaintCategory
  severity: ComplaintSeverity
  status: ComplaintStatus

  // Source
  source: ComplaintSource
  customer_name?: string
  customer_email?: string
  customer_phone?: string
  submitted_at: string

  // Assignment
  assigned_to_id?: number
  assigned_to?: { id: number, name: string }
  site_id?: number
  site?: { id: number, name: string }

  // Investigation
  root_cause?: string
  investigation_notes?: string

  // Response
  response?: string
  response_sent_at?: string
  response_method?: 'email' | 'phone' | 'mail' | 'in_person'

  // Actions
  corrective_actions?: ComplaintAction[]
  preventive_actions?: ComplaintAction[]

  // Follow-up
  customer_satisfaction?: number // 1-3
  follow_up_notes?: string
  closed_at?: string
  closed_by_id?: number

  // Financial
  compensation_amount?: number
  resolution_cost?: number

  attachments?: Attachment[]
  created_at: string
  updated_at: string
}

export type ComplaintType = 'complaint' | 'claim' | 'suggestion'

export type ComplaintCategory
  = | 'product_quality'
    | 'service_quality'
    | 'delivery'
    | 'pricing'
    | 'customer_service'
    | 'safety'
    | 'environmental'
    | 'other'

export type ComplaintSeverity = 'low' | 'medium' | 'high' | 'critical'

export type ComplaintStatus
  = | 'received'
    | 'acknowledged'
    | 'investigating'
    | 'action_plan'
    | 'implementing'
    | 'resolved'
    | 'closed'
    | 'rejected'

export type ComplaintSource
  = | 'customer'
    | 'supplier'
    | 'employee'
    | 'regulator'
    | 'public'
    | 'other'

export interface ComplaintAction {
  id: number
  description: string
  responsible_id: number
  responsible?: { id: number, name: string }
  due_date: string
  status: 'pending' | 'in_progress' | 'completed'
  completion_date?: string
  evidence?: string[]
}

export interface Attachment {
  id: number
  name: string
  url: string
  size: number
  type: string
  uploaded_at: string
}

export interface ComplaintStatistics {
  total: number
  by_type: Record<ComplaintType, number>
  by_status: Record<ComplaintStatus, number>
  by_category: Record<ComplaintCategory, number>
  average_resolution_time_days: number
  customer_satisfaction_avg: number
}

export interface CreateComplaintPayload {
  type: ComplaintType
  title: string
  description: string
  category: ComplaintCategory
  severity: ComplaintSeverity
  source: ComplaintSource
  customer_name?: string
  customer_email?: string
  customer_phone?: string
  site_id?: number
  assigned_to_id?: number
  attachments?: File[]
}

export interface UpdateComplaintPayload {
  title?: string
  description?: string
  category?: ComplaintCategory
  severity?: ComplaintSeverity
  status?: ComplaintStatus
  assigned_to_id?: number
  root_cause?: string
  investigation_notes?: string
  response?: string
  response_method?: 'email' | 'phone' | 'mail' | 'in_person'
  customer_satisfaction?: number
  follow_up_notes?: string
  compensation_amount?: number
  resolution_cost?: number
}

export interface SendResponsePayload {
  response: string
  method: 'email' | 'phone' | 'mail' | 'in_person'
  send_email: boolean
}
