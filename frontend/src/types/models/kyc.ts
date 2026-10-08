/**
 * KYC (Know Your Customer) Types
 * For Client A registration and verification
 */

export interface KYCRequest {
  id: number
  reference: string
  status: KYCStatus

  // Company Information
  company_name: string
  rccm_number: string
  ifu_number: string
  city: string
  address?: string
  phone?: string

  // Admin Information
  admin_name: string
  admin_email: string
  admin_phone?: string
  admin_id_type: IDType
  admin_id_number: string

  // Documents
  documents: KYCDocument[]

  // Review
  reviewed_by_id?: number
  reviewed_by?: { id: number, name: string }
  reviewed_at?: string
  rejection_reason?: string

  // Tenant (created after approval)
  tenant_id?: number

  created_at: string
  updated_at: string
}

export type KYCStatus
  = | 'pending'
    | 'under_review'
    | 'approved'
    | 'rejected'
    | 'additional_info_required'

export type IDType = 'cip' | 'cni' | 'passeport'

export interface KYCDocument {
  id: number
  type: KYCDocumentType
  file_name: string
  file_url: string
  file_size: number
  uploaded_at: string
  verified: boolean
}

export type KYCDocumentType
  = | 'rccm'
    | 'ifu'
    | 'admin_id'
    | 'other'

export interface CreateKYCPayload {
  // Company
  company_name: string
  rccm_number: string
  ifu_number: string
  city: string
  address?: string
  phone?: string

  // Admin
  admin_name: string
  admin_email: string
  admin_phone?: string
  admin_id_type: IDType
  admin_id_number: string
  password: string
  password_confirmation: string

  // Documents
  rccm_document: File
  ifu_document: File
  admin_id_document: File

  // Terms
  accept_terms: boolean
}

export interface ReviewKYCPayload {
  status: 'approved' | 'rejected' | 'additional_info_required'
  rejection_reason?: string
}

export interface KYCStatistics {
  total: number
  pending: number
  under_review: number
  approved: number
  rejected: number
  average_review_time_hours: number
}
