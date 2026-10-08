// Types pour la configuration d'entreprise et documents personnalisés

export interface BrandColors {
  primary: string
  secondary: string
  accent?: string
}

export interface PhoneTypes {
  primary: 'mobile' | 'landline' | 'whatsapp'
  secondary?: 'mobile' | 'landline' | 'whatsapp'
}

export interface SocialMedia {
  linkedin?: string
  facebook?: string
  twitter?: string
  instagram?: string
  youtube?: string
}

export interface HeaderConfig {
  show_logo: boolean
  show_slogan: boolean
  show_certifications: boolean
  layout: 'professional' | 'minimal'
}

export interface FooterConfig {
  show_contact: boolean
  show_social: boolean
  show_legal_info: boolean
  show_page_numbers: boolean
  show_qr_code: boolean
  custom_text?: string | null
}

export interface DocumentTypeConfig {
  header_style: 'minimal' | 'professional'
  show_certifications: boolean
  show_confidentiality?: boolean
}

export interface HeaderFooterConfig {
  header: HeaderConfig
  footer: FooterConfig
  document_types?: Record<string, DocumentTypeConfig>
}

export interface EnterpriseBranding {
  slogan?: string
  brand_colors?: BrandColors
  header_font?: string
}

export interface EnterpriseLegalInfo {
  country?: string
  // Bénin
  rccm_number?: string
  ifu_number?: string
  cnss_number?: string
  fodefca_number?: string

  // France
  siret?: string
  rcs?: string
  vat_number?: string
  ape_code?: string

  // Général
  legal_form?: string
  share_capital?: string
  trade_register_number?: string
  tax_id?: string
}

export interface EnterpriseContact {
  address_line_1?: string
  address_line_2?: string
  postal_code?: string
  phone_primary?: string
  phone_secondary?: string
  phone_types?: PhoneTypes
  email_general?: string
  email_support?: string
  website?: string
  social_media?: SocialMedia
}

export interface EnterpriseDocumentConfig {
  header_footer_config?: HeaderFooterConfig
}

export interface Certification {
  id: number
  name: string
  code: string
  description?: string
  type: 'iso' | 'other'
  is_active: boolean
}

export interface EnterpriseCertification {
  id: number
  enterprise_id: number
  certification_id: number
  certification_number: string
  issue_date: string
  expiry_date: string
  issuing_body: string
  status: 'active' | 'expired' | 'expiring_soon'
  certification?: Certification
}

export interface QRCodeScan {
  id: number
  document_id: number
  document_type: string
  scanned_at: string
  ip_address: string
  user_agent: string
  country?: string
  city?: string
}

export interface QRCodeVerification {
  valid: boolean
  document_id: number
  document_type: string
  document_title: string
  code?: string
  version?: string
  workflow_status?: string
  status?: string
  approved_at?: string
  enterprise_name: string
  message?: string
  generated_at: string
  scan_count: number
}

export interface QRCodeStats {
  total_scans: number
  unique_ips: number
  countries: Record<string, number>
  recent_scans: QRCodeScan[]
}
