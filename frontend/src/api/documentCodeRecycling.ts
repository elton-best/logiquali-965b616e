import { apiClient } from '@/api/client'

export interface ReleasedCode {
  code: string
  released_at: string
  reason: 'rejected' | 'deleted' | 'manual'
}

export interface CodeHistoryEntry {
  code: string
  released_at: string
  reused_at: string | null
  reason: string
  released_by: number
}

export interface ReleaseCodePayload {
  code: string
  reason: 'rejected' | 'deleted' | 'manual'
  original_document_id?: number
}

export const documentCodeRecyclingApi = {
  checkAvailability (code: string) {
    return apiClient.get<{ available: boolean, code: string }>(
      '/api/v1/document-codes/check-availability',
      { params: { code } },
    )
  },

  getAvailableCodes () {
    return apiClient.get<{ codes: ReleasedCode[], count: number }>(
      '/api/v1/document-codes/available',
    )
  },

  releaseCode (payload: ReleaseCodePayload) {
    return apiClient.post<{ message: string, release: ReleasedCode }>(
      '/api/v1/document-codes/release',
      payload,
    )
  },

  getCodeHistory (code: string) {
    return apiClient.get<{ code: string, history: CodeHistoryEntry[] }>(
      `/api/v1/document-codes/${encodeURIComponent(code)}/history`,
    )
  },
}
