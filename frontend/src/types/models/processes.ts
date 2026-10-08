/**
 * Process Management Module Types
 */

// ============= PROCESS (Main Entity) =============

export interface Process {
  id: number
  ref: string
  site_id?: number
  site?: Site
  title: string
  type: ProcessType
  category: ProcessCategory
  code?: string
  pilot_id: number
  pilot?: User
  copilot_id?: number
  copilot?: User
  purpose?: string
  finalite?: string
  acteurs?: string[]
  ressources?: string[]
  methodes?: string[]
  interfaces?: string[]
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  normes_iso?: string[]
  status: ProcessStatus
  is_validated: boolean
  validated_by?: number
  validator?: User
  validated_at?: string
  last_review_at?: string
  next_review_at?: string
  review_frequency_months?: number
  version?: string
  parent_process_id?: number
  parent_process?: Process
  level?: number
  order?: number
  created_at: string
  updated_at: string
  deleted_at?: string

  // Relations
  child_processes?: Process[]
  resources_list?: ProcessResource[]
  indicators?: ProcessIndicator[]
  risks_opportunities?: ProcessRiskOpportunity[]
  iso_coverages?: ProcessIsoCoverage[]
  reviews?: ProcessReview[]
  sequences?: ProcessSequence[]
  versions?: ProcessVersion[]
  current_version?: ProcessVersion
  process_objectives?: ProcessObjective[]
  audits?: Audit[]
  documents_via_process?: Document[]
  activities?: Activity[]
  risks?: Risk[]
  opportunities?: Opportunity[]
  objectives?: Objective[]
  documents?: Document[]
  responsibilities?: Responsibility[]
  team_members?: TeamMember[]
  dashboards?: Dashboard[]
  non_conformities?: NonConformity[]
}

export type ProcessCategory
  = 'management'
    | 'operational'
    | 'support'

export type ProcessType
  = 'strategic'
    | 'core'
    | 'enabling'

export type ProcessStatus
  = 'draft'
    | 'active'
    | 'under_review'
    | 'archived'

// ============= PROCESS INDICATOR =============

export interface ProcessIndicator {
  id: number
  process_id: number
  process?: Process
  code: string
  name: string
  description?: string
  type: ProcessIndicatorType
  category: ProcessIndicatorCategory
  unit: string
  measurement_frequency: MeasurementFrequency
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  alert_threshold?: number
  formula?: string
  responsible_user_id?: number
  responsible_user?: User
  is_active: boolean
  status?: IndicatorStatus
  created_at: string
  updated_at: string
  deleted_at?: string

  // Relations
  values?: ProcessIndicatorValue[]
}

export type ProcessIndicatorType
  = 'quantitatif'
    | 'qualitatif'
    | 'binaire'

export type ProcessIndicatorCategory
  = 'qualite'
    | 'delai'
    | 'cout'
    | 'environnement'
    | 'securite'
    | 'performance'

export type MeasurementFrequency
  = 'daily'
    | 'weekly'
    | 'monthly'
    | 'quarterly'
    | 'annual'

export type IndicatorStatus
  = 'ok'
    | 'warning'
    | 'critical'

// ============= PROCESS INDICATOR VALUE =============

export interface ProcessIndicatorValue {
  id: number
  indicator_id: number
  indicator?: ProcessIndicator
  measurement_date: string
  period?: string
  value: number
  comment?: string
  status?: IndicatorStatus
  recorded_by?: number
  recorder?: User
  created_at: string
  updated_at: string
}

// ============= PROCESS RISK OPPORTUNITY =============

export interface ProcessRiskOpportunity {
  id: number
  process_id: number
  process?: Process
  type: RiskOpportunityType
  code?: string
  title: string
  description?: string
  cause?: string
  consequence?: string
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  probabilite: number // 1-3
  gravite: number // 1-3
  criticite?: number // auto-calculated
  niveau?: CriticiteLevel
  strategie?: string
  actions_prevues?: string
  responsible_user_id?: number
  responsible_user?: User
  target_date?: string
  status: RiskOpportunityStatus
  probabilite_residuelle?: number
  gravite_residuelle?: number
  criticite_residuelle?: number
  created_by?: number
  creator?: User
  created_at: string
  updated_at: string
  deleted_at?: string
}

export type RiskOpportunityType
  = 'risque'
    | 'opportunite'

export type CriticiteLevel
  = 'critique'
    | 'eleve'
    | 'moyen'
    | 'faible'

export type RiskOpportunityStatus
  = 'identified'
    | 'in_treatment'
    | 'monitored'
    | 'closed'

// ============= PROCESS ISO COVERAGE =============

export interface ProcessIsoCoverage {
  id: number
  process_id: number
  process?: Process
  norme: IsoNorme
  version?: string
  clause_number: string
  clause_title?: string
  clause_description?: string
  coverage_level: CoverageLevel
  coverage_percentage?: number
  coverage_comment?: string
  evidence?: string[]
  conformity_status?: ConformityStatus
  last_audit_date?: string
  next_audit_date?: string
  created_at: string
  updated_at: string
}

export type IsoNorme
  = 'ISO_9001'
    | 'ISO_14001'
    | 'ISO_45001'
    | 'ISO_27001'

export type CoverageLevel
  = 'none'
    | 'partial'
    | 'full'
    | 'exceeded'

export type ConformityStatus
  = 'conforme'
    | 'non_conforme'
    | 'non_applicable'
    | 'en_cours'

// ============= PROCESS REVIEW =============

export interface ProcessReview {
  id: number
  process_id: number
  process?: Process
  type: ReviewType
  review_date: string
  version_reviewed?: string
  led_by?: number
  leader?: User
  participants?: number[]
  objectives?: string
  findings?: string
  strengths?: string
  weaknesses?: string
  opportunities_improvement?: string
  decision?: ReviewDecision
  decision_comment?: string
  action_items?: ActionItem[]
  next_review_date?: string
  status: ReviewStatus
  attachments?: string[]
  created_at: string
  updated_at: string
  deleted_at?: string
}

export type ReviewType
  = 'planned'
    | 'extraordinary'
    | 'audit_follow_up'

export type ReviewDecision
  = 'approved'
    | 'approved_with_changes'
    | 'rejected'
    | 'deferred'

export type ReviewStatus
  = 'planned'
    | 'in_progress'
    | 'completed'
    | 'cancelled'

export interface ActionItem {
  description: string
  responsible?: number
  deadline?: string
  status?: 'pending' | 'in_progress' | 'completed'
}

// ============= PROCESS SEQUENCE =============

export interface ProcessSequence {
  id: number
  process_id: number
  process?: Process
  sequence_order: number
  input_description?: string
  activity_description: string
  output_description?: string
  responsible_user_id?: number
  responsible_user?: User
  duration_minutes?: number
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number

  // Relations
  documents?: Document[]
}

// ============= PROCESS VERSION =============

export interface ProcessVersion {
  id: number
  process_id: number
  process?: Process
  version_number: string
  version_date: string
  author_user_id?: number
  author?: User
  verifier_user_id?: number
  verifier?: User
  approver_user_id?: number
  approver?: User
  status: VersionStatus
  changes_description?: string
  verified_at?: string
  approved_at?: string
  is_current: boolean
  created_at: string
  updated_at: string
  deleted_at?: string
}

export type VersionStatus
  = 'draft'
    | 'verified'
    | 'approved'
    | 'archived'

// ============= PROCESS RESOURCE =============

export interface ProcessResource {
  id: number
  ref: string
  process_id: number
  process?: Process
  type: ResourceType
  description: string
  quantity?: number
  is_available: boolean
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
}

export type ResourceType
  = 'human'
    | 'equipment'
    | 'material'
    | 'information'
    | 'software'
    | 'infrastructure'

// ============= PROCESS INTERACTION =============

export interface ProcessInteraction {
  id: number
  ref: string
  supplier_process_id?: number
  supplier_process?: Process
  self_process_id: number
  self_process?: Process
  client_process_id?: number
  client_process?: Process
  description?: string
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
}

// ============= PROCESS OBJECTIVE =============

export interface ProcessObjective {
  id: number
  process_id: number
  process?: Process
  title: string
  description?: string
  target_value?: number
  target_date?: string
  indicator_id?: number
  indicator?: ProcessIndicator
  status: ObjectiveStatus
  achievement_percentage: number
  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export type ObjectiveStatus
  = 'not_started'
    | 'in_progress'
    | 'achieved'
    | 'failed'

// ============= SUPPORTING TYPES =============

export interface Site {
  id: number
  name: string
}

export interface User {
  id: number
  name: string
  email?: string
}

export interface Activity {
  id: number
  title: string
}

export interface Risk {
  id: number
  title: string
}

export interface Opportunity {
  id: number
  title: string
}

export interface Objective {
  id: number
  title: string
}

export interface Document {
  id: number
  title: string
}

export interface Responsibility {
  id: number
  title: string
}

export interface TeamMember {
  id: number
  name: string
}

export interface Dashboard {
  id: number
  name: string
}

export interface NonConformity {
  id: number
  reference: string
}

export interface Audit {
  id: number
  title: string
}

// ============= PAYLOADS =============

export interface CreateProcessPayload {
  title: string
  type: ProcessType
  category: ProcessCategory
  pilot_id: number
  copilot_id?: number
  site_id?: number
  code?: string
  purpose?: string
  finalite?: string
  parent_process_id?: number
  level?: number
  order?: number
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  normes_iso?: string[]
  acteurs?: string[]
  ressources?: string[]
  methodes?: string[]
  interfaces?: string[]
}

export interface UpdateProcessPayload {
  title?: string
  type?: ProcessType
  category?: ProcessCategory
  pilot_id?: number
  copilot_id?: number
  site_id?: number
  code?: string
  purpose?: string
  finalite?: string
  status?: ProcessStatus
  version?: string
  parent_process_id?: number
  level?: number
  order?: number
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  normes_iso?: string[]
  acteurs?: string[]
  ressources?: string[]
  methodes?: string[]
  interfaces?: string[]
  review_frequency_months?: number
  next_review_at?: string
}

export interface CreateProcessIndicatorPayload {
  process_id: number
  code: string
  name: string
  description?: string
  type: ProcessIndicatorType
  category: ProcessIndicatorCategory
  unit: string
  measurement_frequency: MeasurementFrequency
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  alert_threshold?: number
  formula?: string
  responsible_user_id?: number
  is_active?: boolean
}

export interface UpdateProcessIndicatorPayload {
  code?: string
  name?: string
  description?: string
  type?: ProcessIndicatorType
  category?: ProcessIndicatorCategory
  unit?: string
  measurement_frequency?: MeasurementFrequency
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  alert_threshold?: number
  formula?: string
  responsible_user_id?: number
  is_active?: boolean
}

export interface CreateProcessRiskOpportunityPayload {
  process_id: number
  type: RiskOpportunityType
  code?: string
  title: string
  description?: string
  cause?: string
  consequence?: string
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  probabilite: number
  gravite: number
  strategie?: string
  actions_prevues?: string
  responsible_user_id?: number
  target_date?: string
  status?: RiskOpportunityStatus
}

export interface UpdateProcessRiskOpportunityPayload {
  type?: RiskOpportunityType
  code?: string
  title?: string
  description?: string
  cause?: string
  consequence?: string
  aspect_qualite?: boolean
  aspect_environnement?: boolean
  aspect_sante_securite?: boolean
  probabilite?: number
  gravite?: number
  strategie?: string
  actions_prevues?: string
  responsible_user_id?: number
  target_date?: string
  status?: RiskOpportunityStatus
  probabilite_residuelle?: number
  gravite_residuelle?: number
}

export interface CreateProcessReviewPayload {
  process_id: number
  type: ReviewType
  review_date: string
  version_reviewed?: string
  led_by?: number
  participants?: number[]
  objectives?: string
  status?: ReviewStatus
}

export interface UpdateProcessReviewPayload {
  type?: ReviewType
  review_date?: string
  version_reviewed?: string
  led_by?: number
  participants?: number[]
  objectives?: string
  findings?: string
  strengths?: string
  weaknesses?: string
  opportunities_improvement?: string
  decision?: ReviewDecision
  decision_comment?: string
  action_items?: ActionItem[]
  next_review_date?: string
  status?: ReviewStatus
  attachments?: string[]
}

export interface CreateProcessSequencePayload {
  process_id: number
  sequence_order: number
  input_description?: string
  activity_description: string
  output_description?: string
  responsible_user_id?: number
  duration_minutes?: number
}

export interface UpdateProcessSequencePayload {
  sequence_order?: number
  input_description?: string
  activity_description?: string
  output_description?: string
  responsible_user_id?: number
  duration_minutes?: number
}

export interface CreateProcessVersionPayload {
  process_id: number
  version_number: string
  version_date: string
  author_user_id?: number
  changes_description?: string
}

export interface UpdateProcessVersionPayload {
  version_number?: string
  version_date?: string
  changes_description?: string
  status?: VersionStatus
}

export interface CreateProcessResourcePayload {
  process_id: number
  type: ResourceType
  description: string
  quantity?: number
  is_available?: boolean
}

export interface UpdateProcessResourcePayload {
  type?: ResourceType
  description?: string
  quantity?: number
  is_available?: boolean
}

export interface CreateProcessInteractionPayload {
  supplier_process_id?: number
  self_process_id: number
  client_process_id?: number
  description?: string
}

export interface UpdateProcessInteractionPayload {
  supplier_process_id?: number
  self_process_id?: number
  client_process_id?: number
  description?: string
}

export interface CreateProcessObjectivePayload {
  process_id: number
  title: string
  description?: string
  target_value?: number
  target_date?: string
  indicator_id?: number
}

export interface UpdateProcessObjectivePayload {
  title?: string
  description?: string
  target_value?: number
  target_date?: string
  indicator_id?: number
  status?: ObjectiveStatus
  achievement_percentage?: number
}

export interface CreateProcessIsoCoveragePayload {
  process_id: number
  norme: IsoNorme
  version?: string
  clause_number: string
  clause_title?: string
  clause_description?: string
  coverage_level: CoverageLevel
  coverage_percentage?: number
  coverage_comment?: string
  evidence?: string[]
  conformity_status?: ConformityStatus
}

export interface UpdateProcessIsoCoveragePayload {
  norme?: IsoNorme
  version?: string
  clause_number?: string
  clause_title?: string
  clause_description?: string
  coverage_level?: CoverageLevel
  coverage_percentage?: number
  coverage_comment?: string
  evidence?: string[]
  conformity_status?: ConformityStatus
  last_audit_date?: string
  next_audit_date?: string
}

export interface CreateProcessIndicatorValuePayload {
  indicator_id: number
  measurement_date: string
  period?: string
  value: number
  comment?: string
}

export interface UpdateProcessIndicatorValuePayload {
  measurement_date?: string
  period?: string
  value?: number
  comment?: string
}
