/**
 * KYC Service
 * API calls for Client A registration and verification
 */

import type { ApiResponse, PaginatedResponse } from '@/types/api'
import type {
  CreateKYCPayload,
  KYCRequest,
  KYCStatistics,
  ReviewKYCPayload,
} from '@/types/models/kyc'
import api from '@/api/client'

export const kycService = {
  /**
   * Submit KYC request for Client A registration
   */
  async submitKYC (payload: CreateKYCPayload): Promise<ApiResponse<KYCRequest>> {
    const formData = new FormData()

    // Company info
    formData.append('company_name', payload.company_name)
    formData.append('rccm_number', payload.rccm_number)
    formData.append('ifu_number', payload.ifu_number)
    formData.append('city', payload.city)
    if (payload.address) {
      formData.append('address', payload.address)
    }
    if (payload.phone) {
      formData.append('phone', payload.phone)
    }

    // Admin info
    formData.append('admin_name', payload.admin_name)
    formData.append('admin_email', payload.admin_email)
    if (payload.admin_phone) {
      formData.append('admin_phone', payload.admin_phone)
    }
    formData.append('admin_id_type', payload.admin_id_type)
    formData.append('admin_id_number', payload.admin_id_number)
    formData.append('password', payload.password)
    formData.append('password_confirmation', payload.password_confirmation)

    // Documents
    formData.append('rccm_document', payload.rccm_document)
    formData.append('ifu_document', payload.ifu_document)
    formData.append('admin_id_document', payload.admin_id_document)

    // Terms
    formData.append('accept_terms', payload.accept_terms.toString())

    const response = await api.post<ApiResponse<KYCRequest>>('/kyc/submit', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },

  /**
   * Get all KYC requests (Super Admin only)
   */
  async getKYCRequests (params?: {
    status?: string
    page?: number
    per_page?: number
  }): Promise<ApiResponse<PaginatedResponse<KYCRequest>>> {
    const response = await api.get<ApiResponse<PaginatedResponse<KYCRequest>>>('/kyc/requests', { params })
    return response.data
  },

  /**
   * Get single KYC request
   */
  async getKYCRequest (id: number): Promise<ApiResponse<KYCRequest>> {
    const response = await api.get<ApiResponse<KYCRequest>>(`/kyc/requests/${id}`)
    return response.data
  },

  /**
   * Review KYC request (approve/reject)
   */
  async reviewKYC (id: number, payload: ReviewKYCPayload): Promise<ApiResponse<KYCRequest>> {
    const response = await api.post<ApiResponse<KYCRequest>>(`/kyc/requests/${id}/review`, payload)
    return response.data
  },

  /**
   * Get KYC statistics
   */
  async getKYCStatistics (): Promise<ApiResponse<KYCStatistics>> {
    const response = await api.get<ApiResponse<KYCStatistics>>('/kyc/statistics')
    return response.data
  },

  /**
   * Download KYC document
   */
  async downloadDocument (id: number, documentId: number): Promise<void> {
    const response = await api.get(`/kyc/requests/${id}/documents/${documentId}`, {
      responseType: 'blob',
    })

    const blob = new Blob([response.data])
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.download = `kyc-document-${documentId}`
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
  },
}
