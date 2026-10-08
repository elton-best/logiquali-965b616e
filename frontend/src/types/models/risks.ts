/**
 * Risk Management Module Types
 */

export interface Risk {
  id: number
  tenant_id: number
  reference: string
  title: string
  description: string
  category: RiskCategory
  type: RiskType
  source?: string
  status: RiskStatus
  site_id?: number
  site?: { id: number, name: string }
  process_id?: number
  process?: { id: number, name: string }
  owner_id: number
  owner?: { id: number, name: string }

  // Risk Assessment
  likelihood_before: number // 1-3
  impact_before: number // 1-3
  risk_level_before: RiskLevel

  // After treatment
  likelihood_after?: number
  impact_after?: number
  risk_level_after?: RiskLevel

  // Treatment
  treatment_strategy?: RiskTreatment
  treatment_plan?: string
  treatment_actions?: RiskAction[]
  control_measures?: ControlMeasure[]

  // Monitoring
  review_frequency?: ReviewFrequency
  next_review_date?: string
  last_reviewed_at?: string

  // Financial
  potential_cost?: number
  treatment_cost?: number

  created_at: string
  updated_at: string
}

export type RiskCategory
  = | 'strategic'
    | 'operational'
    | 'financial'
    | 'compliance'
    | 'safety'
    | 'environmental'
    | 'reputation'
    | 'technological'
    | 'other'

export type RiskType = 'threat' | 'opportunity'

export type RiskStatus
  = | 'identified'
    | 'assessed'
    | 'treated'
    | 'monitored'
    | 'accepted'
    | 'closed'

export type RiskLevel = 'low' | 'medium' | 'high' | 'critical'

export type RiskTreatment = 'avoid' | 'reduce' | 'transfer' | 'accept'

export type ReviewFrequency = 'monthly' | 'quarterly' | 'biannual' | 'annual'

export interface RiskAction {
  id: number
  description: string
  responsible_id: number
  responsible?: { id: number, name: string }
  due_date: string
  status: 'pending' | 'in_progress' | 'completed'
  completion_date?: string
}

export interface ControlMeasure {
  id: number
  description: string
  type: 'preventive' | 'detective' | 'corrective'
  effectiveness: 'low' | 'medium' | 'high'
  status: 'active' | 'inactive'
}

export interface RiskMatrix {
  likelihood: number
  impact: number
  level: RiskLevel
  count: number
  risks: Risk[]
}

export interface CreateRiskPayload {
  title: string
  description: string
  category: RiskCategory
  type: RiskType
  source?: string
  site_id?: number
  process_id?: number
  owner_id: number
  likelihood_before: number
  impact_before: number
  treatment_strategy?: RiskTreatment
  treatment_plan?: string
  review_frequency?: ReviewFrequency
}

export interface UpdateRiskPayload {
  title?: string
  description?: string
  category?: RiskCategory
  status?: RiskStatus
  likelihood_before?: number
  impact_before?: number
  likelihood_after?: number
  impact_after?: number
  treatment_strategy?: RiskTreatment
  treatment_plan?: string
  potential_cost?: number
  treatment_cost?: number
  review_frequency?: ReviewFrequency
  next_review_date?: string
}
