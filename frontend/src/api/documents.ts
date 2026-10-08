import type {
  DocumentLink,
  DocumentStats,
  DocumentTypeCatalog,
  DocumentVersion,
  Nomenclature,
} from '../types/document.types'
import api, { getAxiosApiClient } from '@/api/client'

export const documentsApi = {
  getAll: (params?: any) => api.get('/documents', { params }),
  getById: (id: number) => api.get(`/documents/${id}`),
  create: (data: FormData) => api.upload('/documents', data),
  update: (id: number, data: FormData | Record<string, any>) =>
    data instanceof FormData
      ? api.upload(`/documents/${id}?_method=PUT`, data)
      : api.put(`/documents/${id}`, data),
  delete: (id: number) => api.delete(`/documents/${id}`),

  uploadVersion: (id: number, data: FormData) => api.upload(`/documents/${id}/versions`, data),
  downloadVersion: (id: number, versionId: number, filename?: string) =>
    api.download(`/documents/${id}/download/${versionId}`, filename),

  // Unified preview & download (handles both disks on backend)
  previewFile: (id: number, versionId?: number) =>
    getAxiosApiClient().get(
      `/documents/${id}/preview${versionId ? `?version_id=${versionId}` : ''}`,
      { responseType: 'blob' },
    ),
  downloadFile: (id: number, versionId?: number) =>
    getAxiosApiClient().get(
      `/documents/${id}/download${versionId ? `/${versionId}` : ''}`,
      { responseType: 'blob' },
    ),

  previewCode: (params: { site_id: number, type: string, process_id?: number }) =>
    api.get<{ data: { code: string, nomenclature_template_id?: number | null, nomenclature_template_version?: number | null } }>(
      '/documents/preview-code', { params },
    ),

  getStats: (params?: any) => api.get<DocumentStats>('/documents/stats', { params }),
}

export const nomenclaturesApi = {
  getAll: (params?: any) => api.get<Nomenclature[]>('/nomenclature-templates', { params }).then((r: any) => ({
    ...r,
    data: Array.isArray(r.data?.data) ? r.data.data : r.data,
  })),
  getById: (id: number) => api.get<Nomenclature>(`/nomenclature-templates/${id}`).then((r: any) => ({
    ...r,
    data: r.data?.data ?? r.data,
  })),
  create: (data: any) => api.post<Nomenclature>('/nomenclature-templates', data).then((r: any) => ({
    ...r,
    data: r.data?.data ?? r.data,
  })),
  update: (id: number, data: any) => api.put<Nomenclature>(`/nomenclature-templates/${id}`, data).then((r: any) => ({
    ...r,
    data: r.data?.data ?? r.data,
  })),
  delete: (id: number) => api.delete(`/nomenclature-templates/${id}`),
}

export const documentTypeCatalogsApi = {
  getAll: (params?: any) => api.get<DocumentTypeCatalog[]>('/document-type-catalogs', { params }),
  create: (data: Partial<DocumentTypeCatalog>) => api.post<DocumentTypeCatalog>('/document-type-catalogs', data),
  update: (id: number, data: Partial<DocumentTypeCatalog>) => api.put<DocumentTypeCatalog>(`/document-type-catalogs/${id}`, data),
  delete: (id: number) => api.delete(`/document-type-catalogs/${id}`),
}
