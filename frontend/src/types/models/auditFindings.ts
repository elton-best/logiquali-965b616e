export interface AuditFinding {
  id: number
  ref: string
  audit_id: number
  checklist_item_id?: number
  type: 'nc_major' | 'nc_minor' | 'observation' | 'opportunity' | 'good_practice'
  qhse_axes?: ('quality' | 'health' | 'safety' | 'environment')[]
  process_id?: number
  location?: string
  clause_iso?: string
  title: string
  description: string
  evidence?: string
  requirement?: string
  attachments?: Attachment[]
  severity: 'critical' | 'high' | 'medium' | 'low'
  priority: number
  detected_by: number
  concerned_user_id?: number
  detected_at: string
  root_cause?: string
  immediate_action?: string
  non_conformity_id?: number
  nc_created: boolean
  status: 'open' | 'nc_created' | 'action_planned' | 'resolved' | 'verified' | 'closed'
  resolution_deadline?: string
  resolved_at?: string
  created_at: string
  updated_at: string

  // Relations
  audit?: any
  process?: any
  detected_by_user?: any
  concerned_user?: any
  non_conformity?: any

  // Computed
  type_label?: string
  severity_label?: string
  severity_color?: string
}

export interface Attachment {
  url: string
  name: string
  type: 'image' | 'document' | 'video'
  size?: number
}

export interface ChecklistItem {
  question: string
  clause_iso?: string
  process_id?: number
  risk_id?: number
  conformity: 'conform' | 'non_conform' | 'not_applicable' | null
  evidence: string
  notes: string
}
