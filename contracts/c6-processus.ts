/**
 * CONTRAT C6 — PROCESSUS
 * Propriétaire : Dev A
 * Consommateur : Dev B (rattachement R&O, NC, DUERP, projets)
 *
 * Décrit la structure d'un Processus telle qu'exposée par les endpoints :
 * - GET /api/v1/processes
 * - GET /api/v1/processes/{id}
 * - GET /api/v1/processes-cartography
 */

export type ProcessCategory = 'management' | 'realization' | 'support' | 'pilotage' | 'operationnel'
export type ProcessStatus = 'draft' | 'active' | 'inactive' | 'in_review' | 'validated' | 'obsolete'

export interface ProcessUserSummary {
  id: number
  name: string
  email: string
  avatar?: string | null
}

export interface ProcessSequenceContract {
  id: number
  process_id: number
  sequence_order: number
  activity_description: string
  sub_activities?: string[]
  input_description?: string | null
  output_description?: string | null
  supplier_processes?: string[]
  client_processes?: string[]
  responsible_user_id?: number | null
  responsible_user?: ProcessUserSummary | null
  duration_minutes?: number | null
}

export interface ProcessObjectiveContract {
  id: number
  process_id: number
  title: string
  axe_strategique?: string | null
  axes_strategiques?: string[]
  indicateur?: string | null
  target_date?: string | null
  statut?: string | null
}

export interface ProcessContractSummary {
  id: number
  site_id: number
  enterprise_id?: number
  code?: string | null
  name: string
  title?: string
  abbreviation?: string | null
  type: ProcessCategory
  category: ProcessCategory
  status: ProcessStatus
  purpose?: string | null
  pilot_id?: number | null
  copilot_id?: number | null
  pilot?: ProcessUserSummary | null
  copilot?: ProcessUserSummary | null
  copilots?: ProcessUserSummary[]
  created_at?: string
  updated_at?: string
}

export interface ProcessContractDetail extends ProcessContractSummary {
  sequences?: ProcessSequenceContract[]
  process_objectives?: ProcessObjectiveContract[]
  indicators_count?: number
  risks_count?: number
}
