/**
 * Audit Management Module Types
 */

export interface Audit {
  id: number
  tenant_id: number
  reference: string
  title: string
  description?: string
  type: AuditType
  status: AuditStatus
  scope: string
  standard?: string
  planned_date: string
  actual_date?: string
  duration_hours?: number
  site_id?: number
  site?: { id: number, name: string }
  lead_auditor_id: number
  lead_auditor?: { id: number, name: string }
  team_members?: AuditTeamMember[]
  auditees?: Auditee[]
  findings?: AuditFinding[]
  checklist?: AuditChecklistItem[]
  report_url?: string
  recommendations?: string
  follow_up_required: boolean
  follow_up_date?: string
  created_at: string
  updated_at: string
}

export type AuditType
  = | 'internal'
    | 'external'
    | 'certification'
    | 'surveillance'
    | 'follow_up'
    | 'process'
    | 'system'

export type AuditStatus
  = | 'planned'
    | 'in_progress'
    | 'reporting'
    | 'completed'
    | 'cancelled'

export interface AuditTeamMember {
  id: number
  user_id: number
  user?: { id: number, name: string }
  role: 'auditor' | 'observer' | 'technical_expert'
}

export interface Auditee {
  id: number
  user_id: number
  user?: { id: number, name: string }
  department?: string
}

export interface AuditFinding {
  id: number
  type: 'conformity' | 'non_conformity' | 'observation' | 'opportunity'
  severity?: 'minor' | 'major' | 'critical'
  clause_reference?: string
  description: string
  evidence?: string
  recommendation?: string
  action_required: boolean
  nc_id?: number
}

export interface AuditChecklistItem {
  id: number
  clause: string
  requirement: string
  question: string
  status: 'conforming' | 'non_conforming' | 'not_applicable'
  evidence?: string
  comments?: string
}

export interface CreateAuditPayload {
  title: string
  description?: string
  type: AuditType
  scope: string
  standard?: string
  planned_date: string
  duration_hours?: number
  site_id?: number
  lead_auditor_id: number
  team_members?: number[]
  auditees?: number[]
}

export interface UpdateAuditPayload {
  title?: string
  description?: string
  type?: AuditType
  status?: AuditStatus
  scope?: string
  standard?: string
  planned_date?: string
  actual_date?: string
  duration_hours?: number
  recommendations?: string
  follow_up_required?: boolean
  follow_up_date?: string
}
