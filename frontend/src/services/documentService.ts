/**
 * Document Service
 * API calls for document management (GED - Gestion Électronique des Documents)
 */

import type {
  CreateDocumentCategoryPayload,
  CreateDocumentPayload,
  CreateDocumentVersionPayload,
  Document,
  DocumentCategory,
  DocumentFilters,
  DocumentStatistics,
  DocumentVersion,
  UpdateDocumentPayload,
} from '@/types/document'
import type { PaginatedResponse } from '@/types/shared'
import api from '@/api/client'

const BASE_URL = '/documents'

export const documentService = {
  // ========== DOCUMENTS ==========

  /**
   * Get paginated list of documents
   */
  async getAll (filters?: DocumentFilters): Promise<PaginatedResponse<Document>> {
    const response = await api.get<PaginatedResponse<Document>>(BASE_URL, {
      params: filters,
    })
    return response.data
  },

  /**
   * Get single document by ID
   */
  async getById (id: number): Promise<Document> {
    const response = await api.get<Document>(`${BASE_URL}/${id}`)
    return response.data
  },

  /**
   * Create new document
   */
  async create (payload: CreateDocumentPayload): Promise<Document> {
    const response = await api.post<Document>(BASE_URL, payload)
    return response.data
  },

  /**
   * Update document
   */
  async update (id: number, payload: UpdateDocumentPayload): Promise<Document> {
    const response = await api.put<Document>(`${BASE_URL}/${id}`, payload)
    return response.data
  },

  /**
   * Delete document
   */
  async delete (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/${id}`)
  },

  // ========== WORKFLOW ==========

  /**
   * Submit document for approval
   */
  async submitForApproval (id: number): Promise<Document> {
    const response = await api.post<Document>(`${BASE_URL}/${id}/submit-for-approval`)
    return response.data
  },

  /**
   * Approve document
   */
  async approve (id: number, comment?: string): Promise<Document> {
    const response = await api.post<Document>(`${BASE_URL}/${id}/approve`, { comment })
    return response.data
  },

  /**
   * Reject document
   */
  async reject (id: number, comment: string): Promise<Document> {
    const response = await api.post<Document>(`${BASE_URL}/${id}/reject`, { comment })
    return response.data
  },

  /**
   * Publish document
   */
  async publish (id: number): Promise<Document> {
    const response = await api.post<Document>(`${BASE_URL}/${id}/publish`)
    return response.data
  },

  /**
   * Archive document
   */
  async archive (id: number): Promise<Document> {
    const response = await api.post<Document>(`${BASE_URL}/${id}/archive`)
    return response.data
  },

  // ========== VERSIONS ==========

  /**
   * Get document versions
   */
  async getVersions (documentId: number): Promise<DocumentVersion[]> {
    const response = await api.get<DocumentVersion[]>(`${BASE_URL}/${documentId}/versions`)
    return response.data
  },

  /**
   * Upload new version
   */
  async uploadVersion (documentId: number, payload: CreateDocumentVersionPayload): Promise<DocumentVersion> {
    const formData = new FormData()
    formData.append('file', payload.file)
    if (payload.change_summary) {
      formData.append('change_summary', payload.change_summary)
    }
    if (payload.is_major_change !== undefined) {
      formData.append('is_major_change', payload.is_major_change.toString())
    }

    const response = await api.post<DocumentVersion>(
      `${BASE_URL}/${documentId}/versions`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      },
    )
    return response.data
  },

  /**
   * Download document version
   */
  async downloadVersion (documentId: number, versionId: number): Promise<Blob> {
    const response = await api.get<Blob>(
      `${BASE_URL}/${documentId}/versions/${versionId}/download`,
      {
        responseType: 'blob',
      },
    )
    return response.data
  },

  /**
   * Preview document (latest or specific version)
   */
  async preview (documentId: number, versionId?: number): Promise<Blob> {
    const suffix = versionId ? `?version_id=${versionId}` : ''
    const response = await api.get<Blob>(`${BASE_URL}/${documentId}/preview${suffix}`, {
      responseType: 'blob',
    })
    return response.data
  },

  /**
   * Download document (latest or specific version)
   */
  async download (documentId: number, versionId?: number): Promise<Blob> {
    const suffix = versionId ? `/download/${versionId}` : '/download'
    const response = await api.get<Blob>(`${BASE_URL}/${documentId}${suffix}`, {
      responseType: 'blob',
    })
    return response.data
  },

  // ========== CATEGORIES ==========

  /**
   * Get all categories
   */
  async getCategories (): Promise<DocumentCategory[]> {
    const response = await api.get<DocumentCategory[]>(`${BASE_URL}/categories`)
    return response.data
  },

  /**
   * Create category
   */
  async createCategory (payload: CreateDocumentCategoryPayload): Promise<DocumentCategory> {
    const response = await api.post<DocumentCategory>(`${BASE_URL}/categories`, payload)
    return response.data
  },

  /**
   * Update category
   */
  async updateCategory (id: number, payload: Partial<CreateDocumentCategoryPayload>): Promise<DocumentCategory> {
    const response = await api.put<DocumentCategory>(`${BASE_URL}/categories/${id}`, payload)
    return response.data
  },

  /**
   * Delete category
   */
  async deleteCategory (id: number): Promise<void> {
    await api.delete(`${BASE_URL}/categories/${id}`)
  },

  // ========== STATISTICS & INVENTORY ==========

  /**
   * Get document statistics
   */
  async getStatistics (): Promise<DocumentStatistics> {
    const response = await api.get<DocumentStatistics>(`${BASE_URL}/statistics`)
    return response.data
  },

  /**
   * Generate document inventory (Excel export)
   */
  async generateInventory (filters?: DocumentFilters): Promise<Blob> {
    const response = await api.get<Blob>(`${BASE_URL}/inventory/export`, {
      params: filters,
      responseType: 'blob',
    })
    return response.data
  },

  // ========== SEARCH & FILTERS ==========

  /**
   * Search documents
   */
  async search (query: string, filters?: DocumentFilters): Promise<PaginatedResponse<Document>> {
    const response = await api.get<PaginatedResponse<Document>>(`${BASE_URL}/search`, {
      params: { q: query, ...filters },
    })
    return response.data
  },

  /**
   * Get documents by category
   */
  async getByCategory (categoryId: number, filters?: DocumentFilters): Promise<PaginatedResponse<Document>> {
    const response = await api.get<PaginatedResponse<Document>>(`${BASE_URL}/category/${categoryId}`, {
      params: filters,
    })
    return response.data
  },

  /**
   * Get documents pending review (overdue)
   */
  async getPendingReview (): Promise<Document[]> {
    const response = await api.get<Document[]>(`${BASE_URL}/pending-review`)
    return response.data
  },
}

export default documentService
