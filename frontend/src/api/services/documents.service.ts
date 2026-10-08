/**
 * Documents Service
 * API calls for document management (QHSE documents)
 */

import api from '@/api/client'

export interface Document {
  id: number
  tenant_id: number
  reference: string
  title: string
  description?: string
  type: DocumentType
  category: DocumentCategory
  status: DocumentStatus
  version: string
  file_path: string
  file_name: string
  file_size: number
  file_type: string
  created_by_id: number
  created_by?: {
    id: number
    name: string
    email: string
  }
  reviewed_by_id?: number
  reviewed_by?: {
    id: number
    name: string
    email: string
  }
  approved_by_id?: number
  approved_by?: {
    id: number
    name: string
    email: string
  }
  review_date?: string
  approval_date?: string
  effective_date?: string
  expiry_date?: string
  next_review_date?: string
  parent_id?: number
  versions_count?: number
  is_current: boolean
  tags?: string[]
  created_at: string
  updated_at: string
}

export type DocumentType = 'politique' | 'procedure' | 'instruction' | 'formulaire' | 'enregistrement' | 'plan' | 'manuel' | 'autre'

export type DocumentCategory = 'qualite' | 'hygiene' | 'securite' | 'environnement' | 'general'

export type DocumentStatus = 'draft' | 'pending_review' | 'under_review' | 'pending_approval' | 'approved' | 'rejected' | 'obsolete' | 'archived'

export interface DocumentVersion {
  id: number
  document_id: number
  version: string
  file_path: string
  file_name: string
  file_size: number
  created_by_id: number
  created_by?: {
    id: number
    name: string
  }
  changes_summary?: string
  notes?: string
  created_at: string
}

export interface CreateDocumentDTO {
  title: string
  description?: string
  type: DocumentType
  category: DocumentCategory
  effective_date?: string
  expiry_date?: string
  next_review_date?: string
  tags?: string[]
  file: File
}

export interface UpdateDocumentDTO {
  title?: string
  description?: string
  type?: DocumentType
  category?: DocumentCategory
  effective_date?: string
  expiry_date?: string
  next_review_date?: string
  tags?: string[]
}

export interface DocumentsListParams {
  page?: number
  per_page?: number
  search?: string
  type?: DocumentType
  category?: DocumentCategory
  status?: DocumentStatus
  created_by_id?: number
  sort_by?: 'created_at' | 'title' | 'reference' | 'effective_date'
  sort_order?: 'asc' | 'desc'
}

export interface DocumentsListResponse {
  data: Document[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface ReviewDocumentDTO {
  action: 'approve' | 'reject' | 'request_changes'
  comments?: string
}

export interface UploadVersionDTO {
  file: File
  changes_summary: string
}

class DocumentsService {
  private readonly basePath = '/documents'

  /**
   * Get list of documents
   */
  async getDocuments (params: DocumentsListParams = {}): Promise<DocumentsListResponse> {
    const response = await api.get<DocumentsListResponse>(this.basePath, { params })
    return response.data
  }

  /**
   * Get single document by ID
   */
  async getDocument (id: number): Promise<Document> {
    const response = await api.get<{ data: Document }>(`${this.basePath}/${id}`)
    return response.data.data
  }

  /**
   * Create new document with file upload
   */
  async createDocument (data: CreateDocumentDTO, onProgress?: (progress: number) => void): Promise<Document> {
    const formData = new FormData()
    formData.append('file', data.file)
    formData.append('title', data.title)
    formData.append('type', data.type)
    formData.append('category', data.category)

    if (data.description) {
      formData.append('description', data.description)
    }
    if (data.effective_date) {
      formData.append('effective_date', data.effective_date)
    }
    if (data.expiry_date) {
      formData.append('expiry_date', data.expiry_date)
    }
    if (data.next_review_date) {
      formData.append('next_review_date', data.next_review_date)
    }
    if (data.tags) {
      formData.append('tags', JSON.stringify(data.tags))
    }

    const response = await api.upload<{ data: Document }>(this.basePath, formData, onProgress)
    return response.data.data
  }

  /**
   * Update document metadata (not file)
   */
  async updateDocument (id: number, data: UpdateDocumentDTO): Promise<Document> {
    const response = await api.put<{ data: Document }>(`${this.basePath}/${id}`, data)
    return response.data.data
  }

  /**
   * Delete document
   */
  async deleteDocument (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Download document file
   */
  async downloadDocument (id: number, filename?: string): Promise<void> {
    await api.download(`${this.basePath}/${id}/download`, filename)
  }

  /**
   * Submit document for review
   */
  async submitForReview (id: number): Promise<Document> {
    const response = await api.post<{ data: Document }>(`${this.basePath}/${id}/submit-review`)
    return response.data.data
  }

  /**
   * Review document (approve/reject/request changes)
   */
  async reviewDocument (id: number, data: ReviewDocumentDTO): Promise<Document> {
    const response = await api.post<{ data: Document }>(`${this.basePath}/${id}/review`, data)
    return response.data.data
  }

  /**
   * Get document versions
   */
  async getVersions (id: number): Promise<DocumentVersion[]> {
    const response = await api.get<{ data: DocumentVersion[] }>(`${this.basePath}/${id}/versions`)
    return response.data.data
  }

  /**
   * Upload new version
   */
  async uploadVersion (id: number, data: UploadVersionDTO, onProgress?: (progress: number) => void): Promise<Document> {
    const formData = new FormData()
    formData.append('file', data.file)
    formData.append('changes_summary', data.changes_summary)

    const response = await api.upload<{ data: Document }>(`${this.basePath}/${id}/versions`, formData, onProgress)
    return response.data.data
  }

  /**
   * Download specific version
   */
  async downloadVersion (documentId: number, versionId: number, filename?: string): Promise<void> {
    await api.download(`${this.basePath}/${documentId}/versions/${versionId}/download`, filename)
  }

  /**
   * Mark document as obsolete
   */
  async markObsolete (id: number): Promise<Document> {
    const response = await api.post<{ data: Document }>(`${this.basePath}/${id}/obsolete`)
    return response.data.data
  }

  /**
   * Archive document
   */
  async archiveDocument (id: number): Promise<Document> {
    const response = await api.post<{ data: Document }>(`${this.basePath}/${id}/archive`)
    return response.data.data
  }
}

export const documentsService = new DocumentsService()
