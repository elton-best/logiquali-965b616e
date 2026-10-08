// Types pour le module d'amélioration continue QHSE

export interface Axe {
  id: number
  code: 'Q' | 'HS' | 'E'
  name: string
  description: string
  is_active: boolean
  color: string
  icon: string
  created_at?: string
  updated_at?: string
}

export interface WorkflowState {
  id: number
  entity_type: 'non_conformity' | 'audit' | 'objective' | 'risk' | 'reclamation' | 'action'
  name: string
  slug: string
  color: string
  icon: string
  order: number
  next_states: string[]
  is_initial: boolean
  is_final: boolean
}

export interface NonConformity {
  id: number
  ref: string
  site_id: number
  process_id?: number
  description: string
  severity: 'minor' | 'major' | 'critical'
  source: 'internal' | 'external' | 'audit' | 'complaint' | 'inspection'
  detected_at: string
  responsible_id?: number
  workflow_state_id: number

  // Analyse causes
  root_cause?: string
  immediate_action?: string
  analysis_method?: '5why' | 'ishikawa'
  analysis_data?: {
    why_analysis?: { question: string, answer: string }[]
    ishikawa?: {
      manpower?: string[]
      method?: string[]
      material?: string[]
      machine?: string[]
      environment?: string[]
      measurement?: string[]
    }
  }

  // Validation
  is_validated: boolean
  validated_by?: number
  validated_at?: string
  validation_notes?: string

  // Vérification efficacité
  is_effective?: boolean
  verification_notes?: string
  verified_by?: number
  verified_at?: string
  verification_date?: string

  // Relations
  site?: any
  process?: any
  responsible?: any
  workflow_state?: WorkflowState
  axes?: Axe[]
  actions?: Action[]
  documents?: any[]

  created_at: string
  updated_at: string
  created_by?: number
  updated_by?: number
}

export interface Audit {
  id: number
  ref: string
  site_id: number
  type: 'system' | 'process' | 'product' | 'supplier' | 'thematic'
  title: string
  scope: string
  standard?: string
  planned_date: string
  actual_date?: string
  duration_hours?: number
  lead_auditor_id: number
  team_members?: number[]
  workflow_state_id: number

  // Planification
  checklist?: {
    category: string
    items: { ref: string, question: string, conformity?: 'c' | 'nc' | 'obs' }[]
  }[]

  // Constatations
  findings?: {
    type: 'major' | 'minor' | 'observation'
    description: string
    clause?: string
    process_id?: number
    auto_create_nc: boolean
    nc_id?: number
  }[]

  // Rapport
  conclusion?: string
  recommendations?: string
  report_generated_at?: string

  // Relations
  site?: any
  lead_auditor?: any
  team?: any[]
  workflow_state?: WorkflowState
  axes?: Axe[]
  non_conformities?: NonConformity[]
  actions?: Action[]

  created_at: string
  updated_at: string
}

export interface Indicateur {
  id: number
  ref: string
  site_id: number
  process_id?: number
  name: string
  description?: string
  category: 'quality' | 'safety' | 'environment' | 'performance' | 'customer'
  formula: string
  unit: string
  frequency: 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly'

  // Seuils
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  alert_below?: number
  alert_above?: number

  // Données historiques (JSON)
  historical_data: {
    date: string
    value: number
    notes?: string
  }[]

  // Responsable
  responsible_id?: number

  // Relations
  site?: any
  process?: any
  responsible?: any
  axes?: Axe[]

  created_at: string
  updated_at: string
}

export interface Objective {
  id: number
  ref: string
  site_id: number
  title: string
  description: string
  category: 'quality' | 'safety' | 'environment' | 'performance' | 'strategic'

  // SMART
  is_specific: boolean
  is_measurable: boolean
  is_achievable: boolean
  is_relevant: boolean
  is_time_bound: boolean

  // Échéance et suivi
  start_date: string
  deadline: string
  progress: number // 0-100
  status: 'draft' | 'active' | 'achieved' | 'cancelled' | 'delayed'

  // Indicateur lié
  indicator_id?: number
  target_value?: number

  // Responsable
  responsible_id: number
  workflow_state_id: number

  // Milestones
  milestones?: {
    title: string
    date: string
    completed: boolean
    completed_at?: string
  }[]

  // Relations
  site?: any
  responsible?: any
  indicator?: Indicateur
  workflow_state?: WorkflowState
  axes?: Axe[]
  actions?: Action[]
  risks?: Risk[]

  created_at: string
  updated_at: string
}

export interface Risk {
  id: number
  ref: string
  site_id: number
  process_id?: number
  type: 'risk' | 'opportunity'
  category: 'strategic' | 'operational' | 'financial' | 'compliance' | 'hs' | 'environmental'
  description: string

  // Cotation initiale (matrice 4x4)
  probability: number // 1-4
  gravity: number // 1-4
  impact: number // 1-4 (pour opportunités)
  criticality?: number // probability × gravity

  // Évaluation initiale
  initial_assessment?: {
    probability: number
    gravity: number
    criticality: number
    date: string
  }

  // Traitement
  treatment?: 'avoid' | 'reduce' | 'transfer' | 'accept' | 'exploit'
  treatment_plan?: string

  // Évaluation résiduelle
  residual_assessment?: {
    probability: number
    gravity: number
    criticality: number
    date: string
  }

  // DUERP (Document Unique HS)
  duerp_reference?: string
  prevention_measures?: string

  // Responsable et validation
  responsible_id: number
  validated_by?: number
  validation_date?: string
  workflow_state_id: number

  // Revue
  last_review_date?: string
  next_review_date?: string

  // Relations
  site?: any
  process?: any
  responsible?: any
  workflow_state?: WorkflowState
  axes?: Axe[]
  actions?: Action[]
  non_conformities?: NonConformity[]

  created_at: string
  updated_at: string
}

export interface Reclamation {
  id: number
  ref: string
  site_id: number
  status?: 'pending' | 'in_progress' | 'closed' | 'cancelled'
  source?: 'internal' | 'external' | 'audit' | 'complaint' | 'inspection' | 'other'

  // Client
  customer_id?: number
  customer_name: string
  customer_email?: string
  customer_phone?: string

  // Réclamation
  type: 'product' | 'service' | 'delivery' | 'quality' | 'safety' | 'other'
  severity: 'minor' | 'major' | 'critical'
  description: string
  received_at: string

  // Analyse
  root_cause?: string
  immediate_action?: string
  preventive_action?: string

  // Réponse
  response?: string
  responded_at?: string
  responded_by?: number

  // Satisfaction post-traitement
  satisfaction_score?: number // 1-5
  satisfaction_comments?: string

  // Responsable
  responsible_id?: number
  workflow_state_id: number

  // Relations
  site?: any
  customer?: any
  responsible?: any
  workflow_state?: WorkflowState
  axes?: Axe[]
  actions?: Action[]
  non_conformities?: NonConformity[]

  created_at: string
  updated_at: string
}

export interface ReclamationFilters {
  status?: 'pending' | 'in_progress' | 'closed' | 'cancelled'
  severity?: 'minor' | 'major' | 'critical'
  source?: 'internal' | 'external' | 'audit' | 'complaint' | 'inspection' | 'other'
  axes?: number[]
  assigned_to?: number
}

export interface ReclamationStatistics {
  total: number
  by_status: Record<string, number>
  by_severity: Record<string, number>
  by_source?: Record<string, number>
  average_resolution_days?: number
  average_satisfaction?: number
}

export interface Action {
  id: number
  ref: string
  type: 'corrective' | 'preventive' | 'improvement' | 'emergency' | 'curative'
  description: string
  responsible_id: number
  deadline: string
  priority: 'low' | 'medium' | 'high' | 'critical'

  // Avancement
  progress: number // 0-100
  started_at?: string
  completed_at?: string

  // Vérification efficacité
  is_effective?: boolean
  verification_notes?: string
  verified_by?: number
  verified_at?: string

  // Plan d'action
  plan_action_id?: number

  // Workflow
  workflow_state_id: number

  // Relations polymorphiques (actionables)
  actionables?: {
    id: number
    actionable_type: 'NonConformity' | 'Audit' | 'Risk' | 'Objective' | 'Reclamation'
    actionable_id: number
  }[]

  // Relations
  responsible?: any
  plan_action?: PlanAction
  workflow_state?: WorkflowState
  axes?: Axe[]
  documents?: any[]

  created_at: string
  updated_at: string
}

export interface PlanAction {
  id: number
  ref: string
  title: string
  description?: string
  responsible_id?: number
  deadline?: string
  status: 'draft' | 'active' | 'completed' | 'cancelled'
  progress: number // Auto-calculé depuis actions

  // Relations
  responsible?: any
  actions?: Action[]
  axes?: Axe[]

  created_at: string
  updated_at: string
}

// DTOs pour création/modification

export interface CreateNonConformityDto {
  site_id: number
  process_id?: number
  description: string
  severity: 'minor' | 'major' | 'critical'
  source: 'internal' | 'external' | 'audit' | 'complaint' | 'inspection'
  detected_at?: string
  responsible_id?: number
  axes?: number[]
  immediate_action?: string
}

export interface AnalyzeNonConformityDto {
  method: '5why' | 'ishikawa'
  root_cause: string
  analysis_data?: any
  actions?: {
    type: 'corrective' | 'preventive'
    description: string
    responsible_id: number
    deadline: string
  }[]
}

export interface CreateAuditDto {
  site_id: number
  type: 'system' | 'process' | 'product' | 'supplier' | 'thematic'
  title: string
  scope: string
  standard?: string
  planned_date: string
  lead_auditor_id: number
  team_members?: number[]
  axes?: number[]
  checklist?: any
}

export interface CreateKpiDto {
  site_id: number
  process_id?: number
  name: string
  description?: string
  category: string
  formula: string
  unit: string
  frequency: string
  target_value?: number
  min_threshold?: number
  max_threshold?: number
  alert_below?: number
  alert_above?: number
  responsible_id?: number
  axes?: number[]
}

export interface AddKpiValueDto {
  date: string
  value: number
  notes?: string
}

export interface CreateRiskDto {
  site_id: number
  process_id?: number
  type: 'risk' | 'opportunity'
  category: string
  description: string
  responsible_id: number
  axes?: number[]
}

export interface RiskFilters {
  site_id?: number
  process_id?: number
  type?: 'risk' | 'opportunity'
  category?: string
  axes?: number[]
  search?: string
  page?: number
  per_page?: number
}

export interface RiskStatistics {
  total: number
  by_type: { risk: number, opportunity: number }
  by_category: Record<string, number>
  high_criticality: number
  treated: number
}

export interface AssessRiskDto {
  probability: number
  gravity: number
  detectability?: number
}

export interface TreatRiskDto {
  treatment: 'avoid' | 'reduce' | 'transfer' | 'accept' | 'exploit'
  treatment_plan: string
  residual_probability?: number
  residual_severity?: number
  actions?: {
    type: string
    description: string
    responsible_id: number
    deadline: string
  }[]
}

// Types Dashboard

export interface DashboardStats {
  non_conformities: {
    total: number
    by_severity: { minor: number, major: number, critical: number }
    by_status: { draft: number, in_analysis: number, validated: number, closed: number }
    average_resolution_days: number
  }
  audits: {
    total: number
    upcoming: number
    completed: number
    findings_count: number
  }
  indicators: {
    total: number
    with_alerts: number
    above_target: number
    below_target: number
  }
  objectives: {
    total: number
    achieved: number
    in_progress: number
    delayed: number
    average_progress: number
  }
  risks: {
    total: number
    high_criticality: number
    by_type: { risk: number, opportunity: number }
    treated: number
  }
  complaints: {
    total: number
    resolved: number
    pending: number
    average_satisfaction: number
  }
}

export interface TrendData {
  period: string
  nc_count: number
  audits_count: number
  risks_high: number
  objectives_achieved: number
}

export interface RiskMatrixData {
  matrix: number[][] // 4x4 matrix
  risks: {
    id: number
    ref: string
    description: string
    probability: number
    gravity: number
    position: { x: number, y: number }
  }[]
}

export type RiskMatrix = RiskMatrixData
