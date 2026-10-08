/**
 * Types TypeScript pour le module Non-Conformités
 */

import type {
  ActionReference,
  AuditReference,
  BaseEntity,
  NCSeverity,
  NCStatus,
  NCType,
  ProcessReference,
  QHSEAxis,
  SiteReference,
  UserReference,
} from './shared'

export interface NonConformity extends BaseEntity {
  ref: string
  site_id: number
  site?: SiteReference

  process_id?: number
  process?: ProcessReference

  audit_id?: number
  audit?: AuditReference

  workflow_state_id?: number

  title: string
  type: NCType
  source: string
  severity: NCSeverity
  priority?: 'low' | 'medium' | 'high' | 'critical'

  description: string

  detected_by?: number
  detected_by_user?: UserReference
  detected_at: string

  // Analyse causes
  root_cause_analysis?: string
  cause_analysis?: CauseAnalysis

  // Impacts
  impacts?: Impact[]
  cost_impact?: number

  // Actions
  corrective_action?: string
  preventive_action?: string

  responsible_id?: number
  responsible?: UserReference

  deadline?: string
  resolution_date?: string

  // Vérification efficacité
  effectiveness_verified: boolean
  verification_date?: string
  verified_by?: number
  verified_by_user?: UserReference
  verification_notes?: string

  status: NCStatus

  // Relations
  axes?: QHSEAxis[]
  actions?: ActionReference[]
  documents?: any[]
  processes?: ProcessReference[]
  risks?: any[]
}

/**
 * Analyse des causes (méthode 5M / Ishikawa)
 */
export interface CauseAnalysis {
  // Méthode 5M
  main_material?: string // Matière
  machine?: string // Machine
  method?: string // Méthode
  workforce?: string // Main d'œuvre (Manpower)
  environment?: string // Milieu

  // Ou causes générales
  immediate_cause?: string
  root_cause?: string
  contributing_factors?: string[]
  notes?: string

  // Diagramme Ishikawa (optionnel)
  fishbone?: {
    category: string
    causes: string[]
  }[]
}

/**
 * Impact d'une NC
 */
export interface Impact {
  type: 'quality' | 'safety' | 'environmental' | 'financial' | 'reputational' | 'regulatory'
  description: string
  severity?: 'low' | 'medium' | 'high'
  estimated_cost?: number
}

/**
 * Filtres pour les NC
 */
export interface NCFilters {
  search?: string
  type?: NCType | NCType[]
  severity?: NCSeverity | NCSeverity[]
  status?: NCStatus | NCStatus[]
  priority?: string | string[]
  site_id?: number
  process_id?: number
  audit_id?: number
  responsible_id?: number
  axes?: QHSEAxis[]
  detected_from?: string
  detected_to?: string
  overdue?: boolean
  unresolved?: boolean
  page?: number
  per_page?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

/**
 * Payload création NC
 */
export interface CreateNCPayload {
  site_id: number
  process_id?: number
  audit_id?: number
  title: string
  type: NCType
  source: string
  severity: NCSeverity
  priority?: 'low' | 'medium' | 'high' | 'critical'
  description: string
  detected_by?: number
  detected_at: string
  axes?: QHSEAxis[]
}

/**
 * Payload mise à jour NC
 */
export interface UpdateNCPayload extends Partial<CreateNCPayload> {
  responsible_id?: number
  deadline?: string
  status?: NCStatus
}

/**
 * Payload analyse des causes
 */
export interface AnalyzeNCPayload {
  root_cause_analysis?: string
  cause_analysis: CauseAnalysis
  impacts?: Impact[]
  cost_impact?: number
  corrective_action?: string
  preventive_action?: string
}

/**
 * Payload validation NC
 */
export interface ValidateNCPayload {
  validated: boolean
  validation_notes?: string
  responsible_id?: number
  deadline?: string
}

/**
 * Payload vérification efficacité
 */
export interface VerifyEffectivenessNCPayload {
  is_effective: boolean
  verification_notes?: string
}

/**
 * Statistiques NC
 */
export interface NCStatistics {
  total: number
  by_status: {
    nouveau: number
    en_analyse: number
    action_en_cours: number
    en_verification: number
    clos: number
    rejete: number
  }
  by_severity: {
    majeur: number
    mineur: number
    observation: number
  }
  by_type: {
    audit_interne: number
    audit_externe: number
    reclamation_client: number
    incident: number
    non_conformite_produit: number
    autre: number
  }
  by_axis?: {
    Q: number
    H: number
    S: number
    E: number
  }
  overdue_count: number
  unresolved_count: number
  average_resolution_days?: number
  effectiveness_rate?: number
}

/**
 * Timeline NC (historique)
 */
export interface NCTimelineEvent {
  id: number
  type: 'created' | 'analyzed' | 'action_created' | 'verified' | 'closed' | 'status_changed'
  description: string
  user?: UserReference
  created_at: string
  metadata?: Record<string, any>
}
