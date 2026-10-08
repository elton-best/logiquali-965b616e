export interface AuditProgram {
  id: number
  ref: string
  site_id: number
  year: number
  title: string
  program_manager_id: number
  objectives?: string
  scope?: string
  target_processes?: TargetProcess[]
  target_sites?: TargetSite[]
  risk_based_criteria?: Record<string, any> | string[]
  planned_audits_count: number
  completed_audits_count: number
  conformity_rate?: number
  nc_major_count: number
  nc_minor_count: number
  observations_count: number
  last_review_date?: string
  next_review_date?: string
  status: 'draft' | 'validated' | 'in_progress' | 'completed' | 'archived'
  validated_by?: number
  validated_at?: string
  created_at: string
  updated_at: string

  // Relations
  site?: any
  program_manager?: any
  validator?: any
  audits?: any[]

  // Computed
  completion_rate?: number
  status_label?: string
}

export interface TargetProcess {
  process_id: number
  frequency: 'annual' | 'biannual' | 'quarterly' | 'monthly'
  priority: number
}

export interface TargetSite {
  site_id: number
  planned_audits_count: number
}

export interface AuditProgramFilters {
  year?: number
  status?: string
  site_id?: number
  per_page?: number
}

export interface AuditProgramStats {
  completion_rate: number
  conformity_rate: number
  nc_major_count: number
  nc_minor_count: number
  observations_count: number
  audits_by_status: Record<string, number>
  audits_by_quarter: Record<string, number>
  audits_by_type: Record<string, number>
  top_processes_audited: ProcessAuditCount[]
  trend_conformity: ConformityTrend[]
}

export interface ProcessAuditCount {
  id: number
  title: string
  audit_count: number
  avg_conformity: number
}

export interface ConformityTrend {
  date: string
  rate: number
  title: string
}

export interface AuditSuggestion {
  process_id: number
  process_title: string
  reason: string
  priority: number
  frequency: string
  criticality?: number
  risk_count?: number
  type: string
  last_audit_date?: string
}
