// Types et interfaces pour l'API

export interface User {
  id: number
  username: string
  name?: string
  email: string
  phone?: string
  photo_path?: string
  photo_url?: string
  signature_path?: string
  signature_url?: string
  signature_uploaded_at?: string
  user_type: 'super_admin' | 'company' | 'clientb'
  role?: string
  must_change_password?: boolean
  password_changed_at?: string
  is_active: boolean
  last_login_at?: string
  email_verified_at?: string
  enterprise?: Enterprise
  site?: Site
  permissions?: Permission[]
  authz_enforce_active_scope?: boolean
}

export interface Enterprise {
  id: number
  name: string
  sigle?: string
  codification_mode?: 'standard' | 'custom'
  email: string
  owner_user_id?: number
  logo_path?: string
  registration_number?: string
  status: 'pending' | 'active' | 'rejected' | 'suspended'
  approval_status?: 'pending' | 'approved' | 'rejected'
  trial_ends_at?: string
  rejection_reason?: string
  suspension_reason?: string
  organigram_path?: string
  field?: string
  sites?: Site[]
  users?: User[]
  documents?: EnterpriseDocument[]
  created_at?: string
  updated_at?: string
}

export interface EnterpriseDocument {
  id: number
  enterprise_id: number
  document_type: 'legal' | 'compliance' | 'other'
  document_number?: string
  name: string
  stored_path: string
  uploaded_by?: number
  download_url?: string
  file_size?: string
  file_extension?: string
  created_at?: string
  updated_at?: string
}

export interface Site {
  id: number
  enterprise_id: number
  name: string
  location?: string
  is_headquarter: boolean
  is_active: boolean
  subscriptions?: Subscription[]
  created_at?: string
  updated_at?: string
}

export interface Subscription {
  id: number
  site_id: number
  offer_id: number
  status: 'active' | 'suspended' | 'expired' | 'cancelled'
  start_date: string
  expiration_date?: string
  end_date?: string
  suspension_reason?: string
  offer?: Offer
  site?: Site
  created_at?: string
  updated_at?: string
}

export interface Offer {
  id: number
  name: string
  description?: string
  price: number
  duration_months: number
  norms?: Norm[]
  created_at?: string
  updated_at?: string
}

export interface Norm {
  id: number
  code: string
  name: string
  description?: string
  domain: 'quality' | 'environment' | 'security' | 'food_safety' | 'integrated' | 'other'
  current_version_id?: number
  pdf_file_path?: string | null
  pdf_file_url?: string | null
  pdf_original_name?: string | null
  pdf_uploaded_at?: string | null
  has_pdf_document?: boolean
  status: 'draft' | 'published' | 'archived'
  currentVersion?: NormVersion
  versions?: NormVersion[]
  created_at?: string
  updated_at?: string
}

export interface NormVersion {
  id: number
  norm_id: number
  version_code: string
  full_code: string
  published_at?: string
  archived_at?: string
  excel_file_path?: string
  is_current: boolean
  metadata?: any
  sections?: NormSection[]
  total_sections?: number
  chapters_count?: number
  created_at?: string
  updated_at?: string
}

export interface NormSection {
  id: number
  norm_version_id: number
  parent_id?: number
  path: string
  level: number
  type: 'chapter' | 'subchapter' | 'paragraph' | 'point' | 'note' | 'annex'
  number: string
  title?: string
  content?: string
  order_index: number
  references?: string[]
  metadata?: any
  children?: NormSection[]
  icon?: string
  created_at?: string
  updated_at?: string
}

export interface NormAnnotation {
  id: number
  norm_section_id: number
  user_id: number
  enterprise_id?: number
  content: string
  is_private: boolean
  type: 'note' | 'question' | 'clarification' | 'implementation'
  created_at?: string
  updated_at?: string
}

export interface Permission {
  id: number
  name: string
  slug: string
  description?: string
  category?: string
}

// Réponses API
export interface LoginResponse {
  mfa_required?: boolean
  mfa_token?: string
  mfa_expires_at?: string
  user?: User
  role?: string
  token?: string
  token_type?: 'Bearer'
  expires_at?: string
  email_verified?: boolean
  onboarding?: {
    password_change_required: boolean
    signature_uploaded: boolean
    signature_uploaded_at?: string | null
  }
  redirect_to?: string
  requires_subscription?: boolean
  requires_company_setup?: boolean
  trial_expired?: boolean
  message?: string
}

export interface RegisterEnterpriseResponse {
  message: string
  enterprise_id: number
  admin_email: string
}

export interface RegisterClientResponse {
  message: string
  user: User
  token: string
  email_verified: boolean
  verification_sent: boolean
}

export interface ApiResponse<T = any> {
  data: T
  message?: string
  errors?: Record<string, string[]>
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

// Query parameters for pagination and filtering
export interface QueryParams {
  [key: string]: any
  page?: number
  per_page?: number
  search?: string
  sort_by?: string
  sort_order?: 'asc' | 'desc'
}

// Requêtes
export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterEnterpriseRequest {
  enterprise_name: string
  sigle?: string
  email: string
  enterprise_email?: string
  registration_number: string
  address: string
  first_name: string
  last_name: string
  username?: string
  job_title?: string
  password: string
  password_confirmation: string
  phone?: string
  ifu?: string
  city?: string
  country?: string
  field: string
  // Documents séparés
  logo?: File
  rccm_document?: File
  ifu_document?: File
  id_document?: File
  // Numéros de documents
  rccm_number?: string
  ifu_number?: string
  id_type?: string
  id_number?: string
}

export interface RegisterClientRequest {
  username: string
  email: string
  password: string
  password_confirmation: string
  phone?: string
}

export interface ForgotPasswordRequest {
  email: string
}

export interface ResetPasswordRequest {
  email: string
  token: string
  password: string
  password_confirmation: string
}

// Erreurs API
export interface ApiError {
  message: string
  error_code?: string
  correlation_id?: string
  code?: string
  redirect?: string
  errors?: Record<string, string[]>
  status?: number
}
