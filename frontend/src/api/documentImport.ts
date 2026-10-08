import { apiClient } from '@/api/client'

export interface DocumentImportRecord {
  id: number
  filename: string
  status: 'pending' | 'validating' | 'validated' | 'importing' | 'completed' | 'failed' | 'rolled_back'
  total_rows: number
  valid_rows: number | null
  invalid_rows: number | null
  imported_rows: number | null
  failed_rows: number | null
  error_message: string | null
  validation_results: ValidationResults | null
  import_stats: ImportStats | null
  created_at: string
  completed_at: string | null
}

export interface ValidationResults {
  valid_count: number
  invalid_count: number
  rows: ValidationRow[]
}

export interface ValidationRow {
  row_number: number
  data: Record<string, string>
  is_valid: boolean
  errors: string[]
  error_details?: Array<{ cause: string, correction_attendue: string }>
  warnings: string[]
}

export interface ImportStats {
  imported: number
  failed: number
  rejected?: number
  correlation_id?: string
  errors: Array<{ row?: number, row_number?: number, error: string, correction_attendue?: string }>
}

export interface ExecuteImportPayload {
  preview_confirmed: true
  conflict_strategy: 'skip_invalid' | 'stop_on_error'
  code_generation_mode: 'nomenclature_only'
  target_scope: 'site'
  document_type_catalog_id: number
  process_id: number
}

export interface UploadResponse {
  import_id: number
  filename: string
  total_rows: number
  headers: string[]
}

export const documentImportApi = {
  index (params?: { status?: string }) {
    return apiClient.get<{ success: boolean, data: { data: DocumentImportRecord[] } }>(
      '/document-imports',
      { params },
    )
  },

  show (importId: number) {
    return apiClient.get<{ success: boolean, data: DocumentImportRecord }>(
      `/document-imports/${importId}`,
    )
  },

  upload (file: File, siteId: number, onProgress?: (p: number) => void) {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('site_id', String(siteId))
    return apiClient.post<{ success: boolean, data: UploadResponse }>(
      '/document-imports/upload',
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: e => {
          if (onProgress && e.total) {
            onProgress(Math.round((e.loaded * 100) / e.total))
          }
        },
      },
    )
  },

  uploadFiles (files: File[], siteId: number, onProgress?: (p: number) => void) {
    const formData = new FormData()
    files.forEach(file => formData.append('files[]', file))
    formData.append('site_id', String(siteId))
    return apiClient.post<{ success: boolean, data: UploadResponse }>(
      '/document-imports/upload-files',
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: e => {
          if (onProgress && e.total) {
            onProgress(Math.round((e.loaded * 100) / e.total))
          }
        },
      },
    )
  },

  validate (importId: number, payload: { document_type_catalog_id: number, process_id: number }) {
    return apiClient.post<{ success: boolean, data: { import_id: number, status: string, validation_results: ValidationResults } }>(
      `/document-imports/${importId}/validate`,
      payload,
    )
  },

  execute (importId: number, payload: ExecuteImportPayload) {
    return apiClient.post<{ success: boolean, data: { import_id: number, status: string, stats: ImportStats } }>(
      `/document-imports/${importId}/execute`,
      payload,
    )
  },

  rollback (importId: number) {
    return apiClient.post<{ success: boolean, data: { deleted_count: number, message: string } }>(
      `/document-imports/${importId}/rollback`,
    )
  },

  destroy (importId: number) {
    return apiClient.delete(`/document-imports/${importId}`)
  },

  downloadTemplate () {
    return apiClient.get<{ success: boolean, data: { download_url: string, filename: string } }>(
      '/document-imports/template',
    )
  },
}
