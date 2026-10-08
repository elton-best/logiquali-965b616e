/**
 * Types TypeScript pour le module Audits & Évaluations
 * Conforme ISO 9001:2015 §9.2, ISO 14001:2015 §9.2, ISO 45001:2018 §9.2
 */

import type {
  AuditStatus,
  AuditType,
  BaseEntity,
  FindingSeverity,
  NonConformityReference,
  PaginationLinks,
  PaginationMeta,
  ProcessReference,
  QHSEAxis,
  SiteReference,
  UserReference,
} from './shared'

// ==================== CORE ENTITIES ====================

/**
 * Audit complet
 */
export interface Audit extends BaseEntity {
  code: string
  title: string
  audit_type: AuditType
  status: AuditStatus

  // Périmètre
  site_id?: number
  site?: SiteReference
  process_ids?: number[]
  processes?: ProcessReference[]
  axes_qhse: QHSEAxis[] // Q, H, S, E

  // Planning
  planned_start_date: string
  planned_end_date: string
  actual_start_date?: string
  actual_end_date?: string

  // Participants
  lead_auditor_id: number
  lead_auditor?: UserReference
  auditor_ids?: number[]
  auditors?: UserReference[]
  auditee_ids?: number[]
  auditees?: UserReference[]

  // Contenu
  objectives?: string
  scope?: string
  methodology?: string
  reference_documents?: string[]

  // Programme
  audit_program_id?: number
  audit_program?: AuditProgramReference

  // Résultats
  findings_count?: number
  findings_major?: number
  findings_minor?: number
  findings_observation?: number
  findings_opportunity?: number
  conformity_rate?: number // 0-100

  // Rapport
  report_generated_at?: string
  report_url?: string
  conclusions?: string
  recommendations?: string

  // Relations
  findings?: AuditFinding[]
  checklist_items?: AuditChecklistItem[]
  non_conformities?: NonConformityReference[]

  // Métadonnées
  created_by_id?: number
  created_by?: UserReference
  verified_by_id?: number
  verified_by?: UserReference
  verified_at?: string
  approved_by_id?: number
  approved_by?: UserReference
  approved_at?: string
}

/**
 * Programme d'audits annuel
 */
export interface AuditProgram extends BaseEntity {
  title: string
  year: number
  status: 'draft' | 'approved' | 'in_progress' | 'completed'

  // Périmètre
  site_ids?: number[]
  sites?: SiteReference[]
  axes_qhse: QHSEAxis[]

  // Planning
  start_date: string
  end_date: string

  // Contenu
  objectives?: string
  methodology?: string
  resources?: string

  // Relations
  audits?: Audit[]
  audits_count?: number
  audits_planned?: number
  audits_completed?: number
  audits_overdue?: number

  // Métadonnées
  created_by_id?: number
  created_by?: UserReference
  approved_by_id?: number
  approved_by?: UserReference
  approved_at?: string
}

/**
 * Constat d'audit (Finding)
 */
export interface AuditFinding extends BaseEntity {
  audit_id: number
  audit?: {
    id: number
    code: string
    title: string
  }

  reference: string // AUD-2026-001-F001

  // Classification
  severity: FindingSeverity // 'majeur' | 'mineur' | 'observation' | 'opportunite'
  category: 'non_conformite' | 'observation' | 'bonne_pratique' | 'opportunite'

  // Périmètre
  process_id?: number
  process?: ProcessReference
  clause_iso?: string // ex: "7.1.6", "8.5.1"
  axes_qhse?: QHSEAxis[]

  // Contenu
  title: string
  description: string
  evidence?: string // Preuves objectives
  requirement?: string // Exigence applicable
  recommendation?: string

  // Localisation
  location?: string
  detected_at: string

  // Personnes
  detected_by_id?: number
  detected_by?: UserReference
  responsible_id?: number
  responsible?: UserReference

  // Photos/Documents
  attachments?: string[] // URLs

  // Suivi
  nc_generated: boolean
  non_conformity_id?: number
  non_conformity?: NonConformityReference

  action_required: boolean
  action_deadline?: string

  status: 'open' | 'action_planned' | 'in_progress' | 'resolved' | 'verified' | 'closed'
  resolved_at?: string
  verified_at?: string
  verified_by_id?: number
  verified_by?: UserReference
}

/**
 * Item de checklist d'audit
 */
export interface AuditChecklistItem extends BaseEntity {
  audit_id: number

  // Organisation
  section: string // ex: "7. Support", "8. Opérations"
  clause_iso?: string
  order_index: number

  // Contenu
  question: string
  reference_doc?: string
  expected_evidence?: string

  // Évaluation
  is_applicable: boolean
  is_conformity?: boolean | null // null = non évalué, true = conforme, false = non-conforme
  rating?: 1 | 2 | 3 | 4 | 5 // 1=insuffisant, 5=excellent

  // Résultat
  observation?: string
  evidence_found?: string
  gap_identified?: string

  // Généré automatiquement ?
  auto_generated: boolean
  template_id?: number
}

/**
 * Évaluation de l'auditeur (post-audit)
 */
export interface AuditorEvaluation extends BaseEntity {
  audit_id: number
  auditor_id: number
  auditor?: UserReference

  // Critères d'évaluation
  technical_competence?: number // 1-3
  communication_skills?: number
  objectivity?: number
  analytical_skills?: number
  time_management?: number

  overall_rating?: number // Moyenne

  strengths?: string
  areas_for_improvement?: string

  evaluated_by_id?: number
  evaluated_by?: UserReference
  evaluated_at?: string
}

// ==================== REFERENCES ====================

export interface AuditProgramReference {
  id: number
  title: string
  year: number
  status: string
}

// ==================== FILTERS & PARAMS ====================

export interface AuditFilters {
  search?: string
  status?: AuditStatus
  audit_type?: AuditType
  site_id?: number
  process_id?: number
  axis?: QHSEAxis
  lead_auditor_id?: number
  year?: number
  planned_after?: string
  planned_before?: string
  overdue?: boolean
  program_id?: number
  page?: number
  per_page?: number
  sort_by?: 'planned_start_date' | 'created_at' | 'code' | 'conformity_rate'
  sort_order?: 'asc' | 'desc'
}

export interface AuditProgramFilters {
  search?: string
  status?: string
  year?: number
  site_id?: number
  page?: number
  per_page?: number
}

export interface AuditFindingFilters {
  audit_id?: number
  severity?: FindingSeverity
  category?: string
  status?: string
  process_id?: number
  clause_iso?: string
  responsible_id?: number
  nc_generated?: boolean
  page?: number
  per_page?: number
}

// ==================== PAYLOADS ====================

export interface CreateAuditPayload {
  title: string
  audit_type: AuditType
  site_id?: number
  process_ids?: number[]
  axes_qhse: QHSEAxis[]

  planned_start_date: string
  planned_end_date: string

  lead_auditor_id: number
  auditor_ids?: number[]
  auditee_ids?: number[]

  objectives?: string
  scope?: string
  methodology?: string
  reference_documents?: string[]

  audit_program_id?: number
}

export interface UpdateAuditPayload extends Partial<CreateAuditPayload> {
  status?: AuditStatus
  actual_start_date?: string
  actual_end_date?: string
  conclusions?: string
  recommendations?: string
}

export interface CreateAuditProgramPayload {
  title: string
  year: number
  site_ids?: number[]
  axes_qhse: QHSEAxis[]
  start_date: string
  end_date: string
  objectives?: string
  methodology?: string
  resources?: string
}

export interface CreateAuditFindingPayload {
  audit_id: number
  severity: FindingSeverity
  category: string

  process_id?: number
  clause_iso?: string
  axes_qhse?: QHSEAxis[]

  title: string
  description: string
  evidence?: string
  requirement?: string
  recommendation?: string

  location?: string
  detected_at: string
  responsible_id?: number

  action_required: boolean
  action_deadline?: string
}

export interface UpdateAuditFindingPayload extends Partial<CreateAuditFindingPayload> {
  status?: string
  observation?: string
}

export interface GenerateChecklistPayload {
  audit_id: number
  template_id?: number
  clauses_iso?: string[]
  include_processes?: boolean
}

export interface ConductAuditPayload {
  actual_start_date?: string
  checklist_responses?: Array<{
    item_id: number
    is_applicable: boolean
    is_conformity?: boolean
    rating?: number
    observation?: string
    evidence_found?: string
    gap_identified?: string
  }>
}

export interface CompleteAuditPayload {
  actual_end_date: string
  conclusions: string
  recommendations?: string
  conformity_rate?: number
}

// ==================== STATISTICS ====================

export interface AuditStatistics {
  total: number
  by_status: Record<AuditStatus, number>
  by_type: Record<AuditType, number>
  by_axis: Record<QHSEAxis, number>

  planned: number
  in_progress: number
  completed: number
  overdue: number

  average_conformity_rate: number
  total_findings: number
  findings_by_severity: Record<FindingSeverity, number>

  completion_rate: number // % audits terminés vs planifiés
  on_time_rate: number // % audits terminés dans les délais
}

export interface AuditProgramStatistics {
  total_programs: number
  active_programs: number
  total_audits: number
  audits_completed: number
  audits_overdue: number
  completion_rate: number
}

export interface AuditFindingStatistics {
  total: number
  by_severity: Record<FindingSeverity, number>
  by_category: Record<string, number>
  by_status: Record<string, number>
  nc_generation_rate: number
  average_resolution_time: number // jours
}

// ==================== RESPONSES ====================

export interface PaginatedAuditsResponse {
  data: Audit[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface PaginatedAuditProgramsResponse {
  data: AuditProgram[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface PaginatedAuditFindingsResponse {
  data: AuditFinding[]
  meta: PaginationMeta
  links: PaginationLinks
}

// ==================== CALENDAR EVENTS ====================

/**
 * Événement pour FullCalendar
 */
export interface AuditCalendarEvent {
  id: number
  title: string
  start: string
  end: string
  allDay?: boolean

  // Custom props
  audit: Audit
  status: AuditStatus
  type: AuditType
  leadAuditor: UserReference

  // FullCalendar styling
  backgroundColor?: string
  borderColor?: string
  textColor?: string
  classNames?: string[]
}

// ==================== TIMELINE ====================

export interface AuditTimelineEvent {
  id: number
  audit_id: number
  event_type: 'created' | 'planned' | 'started' | 'finding_added' | 'completed' | 'verified' | 'approved' | 'status_changed'
  description: string
  occurred_at: string
  user_id?: number
  user?: UserReference
  metadata?: Record<string, any>
}
