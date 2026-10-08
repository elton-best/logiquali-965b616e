/**
 * Types partagés entre tous les modules frontend (pilotés par VITE_APP_NAME)
 * Fondations communes pour éviter duplication et assurer cohérence
 */

// ==================== BASE ENTITIES ====================

export interface BaseEntity {
  id: number
  created_at: string
  updated_at: string
  deleted_at?: string | null
}

export interface TimestampedEntity {
  created_at: string
  updated_at: string
}

export interface SoftDeletable {
  deleted_at?: string | null
}

// ==================== USER & ENTERPRISE ====================

export interface UserReference {
  id: number
  name: string
  first_name?: string
  last_name?: string
  email?: string
  avatar?: string
  job_title?: string
}

export interface EnterpriseReference {
  id: number
  name: string
  ref?: string
  logo?: string
}

export interface SiteReference {
  id: number
  name: string
  code?: string
  enterprise_id: number
}

// ==================== MODULE REFERENCES ====================

/**
 * Référence légère à un Audit
 * Utilisé pour afficher liens sans charger tout l'objet
 */
export interface AuditReference {
  id: number
  code: string
  title: string
  status: AuditStatus
  audit_type?: string
  planned_date?: string
}

/**
 * Référence légère à une Non-Conformité
 */
export interface NonConformityReference {
  id: number
  reference: string
  title: string
  status: NCStatus
  severity?: NCSeverity
  type?: NCType
}

/**
 * Référence légère à une Action
 */
export interface ActionReference {
  id: number
  title: string
  status: ActionStatus
  priority?: ActionPriority
  due_date?: string
  responsible?: UserReference
}

/**
 * Référence légère à un Document
 */
export interface DocumentReference {
  id: number
  reference: string
  title: string
  type: string
  version?: string
  status?: DocumentStatus
}

/**
 * Référence légère à un Processus
 */
export interface ProcessReference {
  id: number
  code: string
  name: string
  type?: ProcessType
  responsible?: UserReference
}

/**
 * Référence légère à un Risque
 */
export interface RiskReference {
  id: number
  reference: string
  title: string
  criticality?: number
  probability?: number
  severity?: number
}

/**
 * Référence légère à un Indicateur
 */
export interface IndicatorReference {
  id: number
  code: string
  name: string
  unit?: string
  current_value?: number
  target_value?: number
}

// ==================== ENUMS & TYPES ====================

// Statuts Audit
export type AuditStatus
  = | 'draft'
    | 'planned'
    | 'in_progress'
    | 'completed'
    | 'cancelled'

export type AuditType
  = | 'internal_process'
    | 'internal_product'
    | 'internal_system'
    | 'internal_thematic'
    | 'external_certification'
    | 'external_supplier'
    | 'external_surveillance'
    | 'supplier'
    | 'thematic'

// Statuts Non-Conformité
export type NCStatus
  = | 'nouveau'
    | 'en_analyse'
    | 'action_en_cours'
    | 'en_verification'
    | 'clos'
    | 'rejete'

export type NCSeverity = 'majeur' | 'mineur' | 'observation'

export type FindingSeverity
  = | 'majeur'
    | 'mineur'
    | 'observation'
    | 'opportunite'

export type NCType
  = | 'audit_interne'
    | 'audit_externe'
    | 'reclamation_client'
    | 'incident'
    | 'non_conformite_produit'
    | 'autre'

// Statuts Action
export type ActionStatus
  = | 'pending'
    | 'in_progress'
    | 'completed'
    | 'cancelled'
    | 'overdue'

export type ActionPriority = 'low' | 'medium' | 'high' | 'critical'

export type ActionType
  = | 'corrective'
    | 'preventive'
    | 'improvement'
    | 'routine'

// Statuts Document
export type DocumentStatus
  = | 'draft'
    | 'under_review'
    | 'approved'
    | 'obsolete'
    | 'archived'

export type DocumentType
  = | 'procedure'
    | 'instruction'
    | 'form'
    | 'record'
    | 'manual'
    | 'policy'
    | 'other'

// Types Processus
export type ProcessType = 'management' | 'support' | 'realisation'

// Axes QHSE
export type QHSEAxis = 'Q' | 'H' | 'S' | 'E'

// ==================== PAGINATION ====================

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  path?: string
  per_page: number
  to: number | null
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  links: PaginationLinks
  meta: PaginationMeta
}

// ==================== API RESPONSES ====================

export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  exception?: string
  file?: string
  line?: number
  trace?: any[]
}

// ==================== FILTERS ====================

export interface DateRange {
  start: string | null
  end: string | null
}

export interface BaseFilters {
  search?: string
  page?: number
  per_page?: number
  sort_by?: string
  sort_direction?: 'asc' | 'desc'
}

export interface AuditFilters extends BaseFilters {
  status?: AuditStatus | AuditStatus[]
  audit_type?: AuditType | AuditType[]
  year?: number
  site_id?: number
  qhse_axes?: QHSEAxis[]
  date_range?: DateRange
}

export interface NCFilters extends BaseFilters {
  status?: NCStatus | NCStatus[]
  severity?: NCSeverity | NCSeverity[]
  type?: NCType | NCType[]
  site_id?: number
  responsible_id?: number
  date_range?: DateRange
}

export interface ActionFilters extends BaseFilters {
  status?: ActionStatus | ActionStatus[]
  priority?: ActionPriority | ActionPriority[]
  type?: ActionType | ActionType[]
  responsible_id?: number
  due_date_range?: DateRange
  overdue?: boolean
}

export interface DocumentFilters extends BaseFilters {
  status?: DocumentStatus | DocumentStatus[]
  type?: DocumentType | DocumentType[]
  site_id?: number
  category_id?: number
  version?: string
}

// ==================== STATISTICS ====================

export interface StatusCount {
  status: string
  count: number
  percentage?: number
}

export interface TrendData {
  period: string // Format: YYYY-MM ou YYYY-Www
  value: number
}

export interface DashboardStats {
  total: number
  active?: number
  completed?: number
  overdue?: number
  by_status?: StatusCount[]
  trend?: TrendData[]
}

// ==================== ATTACHMENTS ====================

export interface Attachment {
  id?: number
  name: string
  path: string
  size?: number
  mime_type?: string
  uploaded_by?: UserReference
  uploaded_at?: string
}

// ==================== NOTIFICATIONS ====================

export interface Notification {
  id: string
  type: string
  data: any
  read_at: string | null
  created_at: string
}

// ==================== FORM STATES ====================

export type FormMode = 'create' | 'edit' | 'view'

export interface ValidationError {
  field: string
  message: string
}

export interface FormState<T> {
  data: T
  errors: Record<string, string[]>
  loading: boolean
  mode: FormMode
}

// ==================== TABLE ====================

export interface TableColumn<T = any> {
  key: keyof T | string
  label: string
  sortable?: boolean
  filterable?: boolean
  width?: string
  align?: 'left' | 'center' | 'right'
  formatter?: (value: any, row: T) => string | number
}

export interface TableAction<T = any> {
  label: string
  icon?: string
  variant?: 'primary' | 'secondary' | 'danger' | 'success'
  show?: (row: T) => boolean
  handler: (row: T) => void | Promise<void>
}

// ==================== EXPORT ====================

export type ExportFormat = 'xlsx' | 'csv' | 'pdf' | 'docx'

export interface ExportOptions {
  format: ExportFormat
  filename?: string
  filters?: Record<string, any>
  columns?: string[]
}

// ==================== CHARTS ====================

export interface ChartSeries {
  name: string
  data: number[]
}

export interface ChartDataPoint {
  x: string | number
  y: number
}

export interface ChartOptions {
  title?: string
  subtitle?: string
  xaxis?: {
    categories?: string[]
    title?: string
  }
  yaxis?: {
    title?: string
    min?: number
    max?: number
  }
  colors?: string[]
  legend?: {
    show: boolean
    position?: 'top' | 'right' | 'bottom' | 'left'
  }
}
