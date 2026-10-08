import type { UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
import api from './client'

type DocumentResource = UnifiedDocument

export const documentWorkflowApi = {
  getPendingVerification: (siteId?: number) =>
    api.get<DocumentResource[]>('/document-code-workflow/pending-verification', { params: { site_id: siteId } }),

  getPendingApproval: (siteId?: number) =>
    api.get<DocumentResource[]>('/document-code-workflow/pending-approval', { params: { site_id: siteId } }),

  verifyCode: (documentId: number) =>
    api.post<DocumentResource>(`/document-code-workflow/documents/${documentId}/verify`),

  activateCode: (documentId: number) =>
    api.post<DocumentResource>(`/document-code-workflow/documents/${documentId}/activate`),

  releaseCode: (documentId: number) =>
    api.post(`/document-code-workflow/documents/${documentId}/release`),
}
