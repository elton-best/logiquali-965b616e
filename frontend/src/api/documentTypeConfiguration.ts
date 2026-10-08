import { apiClient } from './client'

export interface CodeStructurePart {
  id?: number
  part_type: 'type_code' | 'process_code' | 'year' | 'month' | 'sequence' | 'separator' | 'custom'
  order: number
  length?: number
  separator?: string
  custom_value?: string
  sequence_scope?: 'global' | 'by_type' | 'by_type_process' | 'by_type_year' | 'by_type_process_year' | 'by_type_process_year_month'
  padding_char?: string
}

export interface DocumentTypeConfiguration {
  id?: number
  enterprise_id?: number
  site_id?: number
  type_code: string
  type_label: string
  description?: string
  code_structure: CodeStructurePart[]
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export interface PreviewCodeResponse {
  preview: string
  structure_breakdown: Array<{
    part_type: string
    value: string
    description: string
  }>
}

export interface VisibilityStats {
  total: number
  by_scope: {
    global: number
    enterprise: number
    site: number
  }
  by_status: {
    active: number
    inactive: number
  }
  by_type: Record<number, number>
}

export const documentTypeConfigurationApi = {
  getAll: (params?: any) =>
    apiClient.get<DocumentTypeConfiguration[]>('/document-type-configurations', { params }),

  list: (params?: { enterprise_id?: number, site_id?: number, is_active?: boolean }) =>
    apiClient.get<DocumentTypeConfiguration[]>('/document-type-configurations', { params }),

  get: (id: number) =>
    apiClient.get<DocumentTypeConfiguration>(`/document-type-configurations/${id}`),

  create: (data: Omit<DocumentTypeConfiguration, 'id' | 'created_at' | 'updated_at'>) =>
    apiClient.post<DocumentTypeConfiguration>('/document-type-configurations', data),

  update: (id: number, data: Partial<DocumentTypeConfiguration>) =>
    apiClient.put<DocumentTypeConfiguration>(`/document-type-configurations/${id}`, data),

  delete: (id: number) =>
    apiClient.delete(`/document-type-configurations/${id}`),

  previewCode: (data: { code_structure: CodeStructurePart[], type_code: string }) =>
    apiClient.post<PreviewCodeResponse>('/document-type-configurations/preview-code', data),

  duplicate: (id: number, data: { type_code: string, type_label: string }) =>
    apiClient.post<DocumentTypeConfiguration>(`/document-type-configurations/${id}/duplicate`, data),

  validateStructure: (code_structure: CodeStructurePart[]) =>
    apiClient.post<{ valid: boolean, errors?: string[] }>('/document-type-configurations/validate-structure', { code_structure }),

  toggleActive: (id: number) =>
    apiClient.patch<DocumentTypeConfiguration>(`/document-type-configurations/${id}/toggle-active`),

  // Visibility & Sharing
  getVisibilityStats: () =>
    apiClient.get<VisibilityStats>('/document-type-configurations/visibility-stats'),

  getAdvancedFilters: (filters: any) =>
    apiClient.get<DocumentTypeConfiguration[]>('/document-type-configurations/advanced-filters', { params: filters }),

  shareWithSites: (id: number, siteIds: number[], conflictStrategy: 'skip' | 'override' = 'skip') =>
    apiClient.post(`/document-type-configurations/${id}/share-with-sites`, {
      site_ids: siteIds,
      conflict_strategy: conflictStrategy,
    }),

  shareWithEnterprises: (id: number, enterpriseIds: number[]) =>
    apiClient.post(`/document-type-configurations/${id}/share-with-enterprises`, { enterprise_ids: enterpriseIds }),

  getAvailableSitesForSharing: (id: number) =>
    apiClient.get(`/document-type-configurations/${id}/available-sites-for-sharing`),

  getAvailableEnterprisesForSharing: (id: number) =>
    apiClient.get(`/document-type-configurations/${id}/available-enterprises-for-sharing`),
}
