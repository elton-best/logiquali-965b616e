/**
 * Types TypeScript pour le module Gestion Documentaire
 * ISO 9001:2015 §7.5 - Informations documentées
 */

import type {
  BaseEntity,
  DocumentStatus,
  DocumentType,
  PaginationLinks,
  PaginationMeta,
  ProcessReference,
  SiteReference,
  UserReference,
} from './shared'

// ==================== CORE ENTITIES ====================

/**
 * Document principal
 */
export interface Document extends BaseEntity {
  ref: string
  document_number?: string
  code: string
  title: string
  description?: string

  // Classification
  type: DocumentType
  category_id?: number
  category?: DocumentCategory

  // Version & Fichier
  version: string // "1.0", "2.1", etc.
  file_path: string
  file_url?: string
  file_size?: number // bytes
  file_extension?: string // pdf, docx, xlsx
  template_path?: string

  // Statut & Workflow
  status: DocumentStatus
  workflow_id?: number
  workflow?: DocumentWorkflow
  workflow_status?: string
  needs_verification?: boolean
  rejection_reason?: string
  module_type?: string
  module_id?: number
  source_type?: string
  source_module?: string
  source_submodule?: string
  source_section?: string
  inventory_link?: {
    id: number
    code: string
    site_id: number
  } | null

  // Localisation
  site_id?: number
  site?: SiteReference
  process_id?: number
  process?: ProcessReference

  // Personnes
  author_id: number
  author?: UserReference
  approver_id?: number
  approver?: UserReference
  verifier_id?: number
  verifier?: UserReference
  approved_at?: string

  // Publication & Validité
  is_active: boolean
  is_archived?: boolean
  published_at?: string
  effective_date?: string
  review_due_date?: string
  is_review_overdue?: boolean
  archived_at?: string

  // Métadonnées
  keywords?: string
  tags?: string[]
  language?: string

  // Confidentialité
  is_confidential: boolean
  confidentiality_level?: 'public' | 'internal' | 'confidential' | 'restricted'

  // Rétention
  retention_period_years?: number

  // Relations
  versions?: DocumentVersion[]
  versions_count?: number
  current_version?: DocumentVersion

  approvals?: DocumentApproval[]
  links?: DocumentLink[]
  workflow_events?: DocumentWorkflowEvent[]

  // Collaboration (TipTap)
  collaboration_version?: number
  is_collaborative?: boolean
}

export interface DocumentWorkflowEvent {
  id: number
  event_type: string
  from_status?: string | null
  to_status: string
  comment?: string | null
  chain_index: number
  event_hash: string
  previous_event_hash?: string | null
  event_signature: string
  occurred_at?: string
  sealed_at?: string
  metadata?: Record<string, any>
  actor?: {
    id: number
    name: string
    email?: string
  } | null
}

/**
 * Version de document
 */
export interface DocumentVersion extends BaseEntity {
  document_id: number
  document?: {
    id: number
    ref: string
    title: string
  }

  version: string // "1.0", "2.1"
  version_number: number // 1, 2, 3 (auto-incrémenté)

  file_path: string
  file_original_name?: string
  file_url?: string
  file_size?: number
  file_size_human?: string

  change_summary?: string // Description changements
  change_type?: 'minor' | 'major' | 'revision' | 'correction'

  created_by_id: number
  created_by?: UserReference

  is_current: boolean
  status: 'draft' | 'review' | 'approved' | 'archived'

  approved_by_id?: number
  approved_by?: UserReference
  approved_at?: string
}

/**
 * Catégorie de documents
 */
export interface DocumentCategory extends BaseEntity {
  name: string
  level?: number
  code?: string
  description?: string
  parent_id?: number
  parent?: DocumentCategory

  // Nomenclature (ex: PRO-XXX, FOR-XXX, ENR-XXX)
  prefix?: string
  color?: string
  icon?: string

  documents_count?: number
}

/**
 * Workflow d'approbation
 */
export interface DocumentWorkflow extends BaseEntity {
  name: string
  description?: string

  // Étapes séquentielles
  steps: DocumentWorkflowStep[]

  is_active: boolean
  is_default?: boolean

  documents_count?: number
}

/**
 * Étape de workflow
 */
export interface DocumentWorkflowStep {
  id?: number
  order: number
  name: string
  role: 'author' | 'reviewer' | 'approver' | 'validator'

  // Assignation
  user_id?: number
  user?: UserReference
  role_name?: string // "Responsable Qualité", "Direction"

  is_required: boolean
  deadline_days?: number // Délai en jours

  status?: 'pending' | 'in_progress' | 'approved' | 'rejected' | 'skipped'
  completed_at?: string
  completed_by?: UserReference
  comments?: string
}

/**
 * Approbation de document
 */
export interface DocumentApproval extends BaseEntity {
  document_id: number
  document_version_id?: number

  approver_id: number
  approver?: UserReference

  status: 'pending' | 'approved' | 'rejected' | 'requested_changes'
  comments?: string

  requested_at: string
  responded_at?: string

  workflow_step_id?: number
  workflow_step?: DocumentWorkflowStep
}

/**
 * Lien entre documents
 */
export interface DocumentLink extends BaseEntity {
  document_id: number
  linked_document_id: number
  linked_document?: Document

  link_type: 'reference' | 'annex' | 'supersedes' | 'related' | 'parent' | 'child'
  description?: string
}

// ==================== FILTERS & PARAMS ====================

export interface DocumentFilters {
  search?: string
  status?: DocumentStatus
  type?: DocumentType
  category_id?: number
  site_id?: number
  process_id?: number
  author_id?: number
  is_active?: boolean
  is_confidential?: boolean
  tags?: string[]
  language?: string
  effective_after?: string
  effective_before?: string
  review_overdue?: boolean
  archived?: boolean
  page?: number
  per_page?: number
  sort_by?: 'created_at' | 'updated_at' | 'title' | 'code' | 'version' | 'effective_date'
  sort_order?: 'asc' | 'desc'
}

export interface DocumentVersionFilters {
  document_id?: number
  status?: string
  is_current?: boolean
  created_by_id?: number
  page?: number
  per_page?: number
}

export interface DocumentCategoryFilters {
  search?: string
  parent_id?: number
  page?: number
  per_page?: number
}

// ==================== PAYLOADS ====================

export interface CreateDocumentPayload {
  title: string
  code?: string
  type: DocumentType
  description?: string
  category_id?: number

  site_id?: number
  process_id?: number

  keywords?: string
  tags?: string[]
  language?: string

  is_confidential?: boolean
  confidentiality_level?: string

  effective_date?: string
  review_due_date?: string
  retention_period_years?: number

  workflow_id?: number

  // File upload handled separately via FormData
}

export interface UpdateDocumentPayload extends Partial<CreateDocumentPayload> {
  status?: DocumentStatus
  approver_id?: number
  is_active?: boolean
}

export interface CreateDocumentVersionPayload {
  document_id: number
  file: File
  version?: string // Si non fourni, auto-incrémenté
  change_summary: string
  change_type?: 'minor' | 'major' | 'revision' | 'correction'
  is_major_change?: boolean

  // File upload handled separately
}

export interface ApproveDocumentPayload {
  document_id: number
  version_id?: number
  comments?: string
  status: 'approved' | 'rejected' | 'requested_changes'
}

export interface CreateDocumentCategoryPayload {
  name: string
  code?: string
  description?: string
  parent_id?: number
  prefix?: string
  color?: string
  icon?: string
}

export interface UploadDocumentFilePayload {
  file: File
  document_id?: number // Si existant (nouvelle version)
}

// ==================== STATISTICS ====================

export interface DocumentStatistics {
  total: number
  by_status: Record<DocumentStatus, number>
  by_type: Record<DocumentType, number>

  active: number
  draft: number
  review: number
  approved: number
  archived: number

  confidential: number
  public: number

  review_overdue: number
  review_due_soon: number // < 30 jours

  total_versions: number
  average_versions_per_doc: number

  by_category?: Record<string, number>
  by_language?: Record<string, number>
}

export interface DocumentCategoryStatistics {
  total_categories: number
  total_documents: number
  documents_by_category: Record<string, number>
}

// ==================== RESPONSES ====================

export interface PaginatedDocumentsResponse {
  data: Document[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface PaginatedDocumentVersionsResponse {
  data: DocumentVersion[]
  meta: PaginationMeta
  links: PaginationLinks
}

export interface PaginatedDocumentCategoriesResponse {
  data: DocumentCategory[]
  meta: PaginationMeta
  links: PaginationLinks
}

// ==================== INVENTORY ====================

/**
 * Item pour inventaire documentaire
 */
export interface DocumentInventoryItem {
  ref: string
  code: string
  title: string
  type: string
  category?: string
  version: string
  status: string
  author: string
  effective_date?: string
  review_due_date?: string
  site?: string
  process?: string
  tags: string[]
}

export interface DocumentInventoryFilters {
  site_id?: number
  process_id?: number
  category_id?: number
  type?: DocumentType
  status?: DocumentStatus
  include_archived?: boolean
  language?: string
}

// ==================== TIMELINE ====================

export interface DocumentTimelineEvent {
  id: number
  document_id: number
  event_type: 'created' | 'uploaded' | 'version_created' | 'submitted_review' | 'approved' | 'rejected' | 'published' | 'archived' | 'restored'
  description: string
  occurred_at: string
  user_id?: number
  user?: UserReference
  metadata?: Record<string, any>
}

// ==================== PREVIEW ====================

export interface DocumentPreviewOptions {
  page?: number // Pour PDF multipages
  width?: number
  height?: number
  quality?: 'low' | 'medium' | 'high'
}
