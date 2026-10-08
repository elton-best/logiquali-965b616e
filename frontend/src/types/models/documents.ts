/**
 * Document Management Module Types
 */

export interface Document {
  id: number
  tenant_id: number
  title: string
  reference: string
  description?: string
  category_id: number
  category?: DocumentCategory
  type: DocumentType
  version: string
  status: DocumentStatus
  file_url: string
  file_name: string
  file_size: number
  file_type: string
  author_id: number
  author?: { id: number, name: string }
  reviewer_id?: number
  reviewer?: { id: number, name: string }
  approver_id?: number
  approver?: { id: number, name: string }
  effective_date?: string
  review_date?: string
  expiry_date?: string
  rejection_reason?: string
  tags?: string[]
  metadata?: Record<string, any>
  created_at: string
  updated_at: string
}

export type DocumentType
  = | 'policy'
    | 'procedure'
    | 'instruction'
    | 'form'
    | 'record'
    | 'manual'
    | 'standard'
    | 'other'

export type DocumentStatus
  = | 'draft'
    | 'in_review'
    | 'approved'
    | 'published'
    | 'archived'
    | 'obsolete'

export interface DocumentCategory {
  id: number
  name: string
  slug: string
  description?: string
  parent_id?: number
  color?: string
  icon?: string
}

export interface DocumentVersion {
  id: number
  document_id: number
  version: string
  file_url: string
  changes: string
  created_by: number
  created_at: string
}

export interface CreateDocumentPayload {
  title: string
  reference?: string
  description?: string
  category_id: number
  type: DocumentType
  file: File
  tags?: string[]
  effective_date?: string
  review_date?: string
}

export interface UpdateDocumentPayload {
  title?: string
  description?: string
  category_id?: number
  type?: DocumentType
  status?: DocumentStatus
  tags?: string[]
  effective_date?: string
  review_date?: string
}
