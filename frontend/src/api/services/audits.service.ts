/**
 * Audits Service
 * API calls for audit management (QHSE audits)
 */

import api from '@/api/client'

export interface Audit {
  id: number
  tenant_id?: number
  audit_program_id?: number | null
  ref?: string
  reference: string
  title: string
  type: AuditType
  scope?: string
  objectives?: string
  risk_based_criteria?: string
  standard?: AuditStandard
  site_id: number
  site?: {
    id: number
    name: string
    code?: string
  }
  program?: {
    id: number
    ref?: string
    title: string
    site?: {
      id: number
      name: string
    }
  }
  audit_program?: {
    id: number
    ref?: string
    title: string
  }
  audit_date: string
  planned_date?: string
  duration_days?: number
  quarter?: number | null
  frequency?: string | null
  lead_auditor_id: number
  lead_auditor?: {
    id: number
    name: string
    email: string
  }
  leadAuditor?: {
    id: number
    name: string
    email?: string
  }
  auditors?: any[]
  auditors_details?: Array<{
    id: number
    name: string
    email: string
  }>
  auditee_ids?: number[]
  auditees?: any[]
  auditees_details?: Array<{
    id: number
    name: string
    email: string
  }>
  processes?: Array<{
    id: number
    title?: string
    name?: string
  }>
  nonConformities?: any[]
  status: AuditStatus
  findings_count?: number
  ncs_count?: number
  observations?: string
  recommendations?: string
  conclusion?: string
  m9_d2_traceability?: Record<string, any> | null
  m9_d5_traceability?: Record<string, any> | null
  report_file?: string
  report_path?: string
  global_report_path?: string
  external_report_path?: string
  report_source?: 'auto' | 'external'
  report_version?: number
  external_report_uploaded_at?: string
  created_by_id: number
  created_by?: {
    id: number
    name: string
  }
  created_at: string
  updated_at: string
}

export type AuditType = 'interne' | 'externe' | 'certification'
export type AuditCreateType = 'internal' | 'external' | 'certification' | 'system' | 'process' | 'product' | 'supplier' | 'thematic' | 'surveillance'

export type AuditStandard = 'ISO9001' | 'ISO14001' | 'ISO45001' | 'autre'

export type AuditStatus = 'planned' | 'in_progress' | 'report_draft' | 'report_approved' | 'completed' | 'closed'

export interface AuditFinding {
  id: number
  audit_id: number
  reference: string
  type: FindingType
  standard_clause: string
  title: string
  description: string
  evidence?: string
  recommendation?: string
  severity: FindingSeverity
  status: FindingStatus
  responsible_id?: number
  responsible?: {
    id: number
    name: string
  }
  due_date?: string
  closed_date?: string
  created_at: string
  updated_at: string
}

export type FindingType = 'non_conformity' | 'observation' | 'opportunity'

export type FindingSeverity = 'critical' | 'major' | 'minor'

export type FindingStatus = 'open' | 'in_progress' | 'resolved' | 'verified' | 'closed'

export interface CreateAuditDTO {
  audit_program_id?: number | null
  title: string
  type: AuditCreateType
  scope: string
  site_id: number
  planned_date: string
  assigned_to?: number
  quarter?: number | null
  frequency?: 'annual' | 'biannual' | 'quarterly' | 'monthly' | 'ad_hoc'
  lead_auditor_id: number
  team_members?: number[]
  auditor_ids?: number[]
  auditee_ids?: number[]
  process_ids?: number[]
  objectives?: string
  reference_documents?: string
  risk_based_criteria?: string
  axes?: string[]
  m9_d2_traceability?: Record<string, any>
}

export interface UpdateAuditDTO {
  audit_program_id?: number | null
  title?: string
  type?: AuditCreateType
  scope?: string
  standard?: AuditStandard
  site_id?: number
  planned_date?: string
  audit_date?: string
  quarter?: number | null
  frequency?: 'annual' | 'biannual' | 'quarterly' | 'monthly' | 'ad_hoc'
  duration_days?: number
  lead_auditor_id?: number
  assigned_to?: number
  team_members?: number[]
  auditor_ids?: number[]
  auditee_ids?: number[]
  process_ids?: number[]
  observations?: string
  recommendations?: string
  conclusion?: string
  objectives?: string
  reference_documents?: string
  risk_based_criteria?: string
  axes?: string[]
  m9_d2_traceability?: Record<string, any>
}

export interface CreateFindingDTO {
  type: FindingType
  standard_clause: string
  title: string
  description: string
  evidence?: string
  recommendation?: string
  severity: FindingSeverity
  responsible_id?: number
  due_date?: string
}

export interface AuditsListParams {
  page?: number
  per_page?: number
  search?: string
  type?: AuditType
  standard?: AuditStandard
  status?: AuditStatus
  site_id?: number
  year?: number
  sort_by?: 'audit_date' | 'created_at' | 'reference'
  sort_order?: 'asc' | 'desc'
}

export interface AuditsListResponse {
  data: Audit[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface AuditReportGenerationResult {
  audit: Audit
  generatedDocumentId: number | null
}

class AuditsService {
  private readonly basePath = '/audits'

  /**
   * Get list of audits
   */
  async getAudits (params: AuditsListParams = {}): Promise<AuditsListResponse> {
    const response = await api.get<AuditsListResponse>(this.basePath, { params })
    return response.data
  }

  /**
   * Get single audit by ID
   */
  async getAudit (id: number): Promise<Audit> {
    const response = await api.get<{ data: Audit } | Audit>(`${this.basePath}/${id}`)
    return (response.data as any)?.data || (response.data as Audit)
  }

  /**
   * Create new audit
   */
  async createAudit (data: CreateAuditDTO): Promise<Audit> {
    const response = await api.post<{ data: Audit } | Audit>(this.basePath, data)
    return (response.data as any)?.data || (response.data as Audit)
  }

  /**
   * Update audit
   */
  async updateAudit (id: number, data: UpdateAuditDTO): Promise<Audit> {
    const response = await api.put<{ data: Audit } | Audit>(`${this.basePath}/${id}`, data)
    return (response.data as any)?.data || (response.data as Audit)
  }

  /**
   * Delete audit
   */
  async deleteAudit (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  /**
   * Close/complete an audit and trigger automatic report generation.
   */
  async completeAudit (id: number): Promise<Audit> {
    const response = await api.post<{ data: Audit } | Audit>(`${this.basePath}/${id}/complete`)
    return (response.data as any)?.data || (response.data as Audit)
  }

  /**
   * Get audit findings
   */
  async getFindings (auditId: number): Promise<AuditFinding[]> {
    const response = await api.get<{ data: AuditFinding[] }>(`${this.basePath}/${auditId}/findings`)
    return response.data.data
  }

  /**
   * Add finding to audit
   */
  async addFinding (auditId: number, data: CreateFindingDTO): Promise<AuditFinding> {
    const response = await api.post<{ data: AuditFinding } | AuditFinding>(`${this.basePath}/${auditId}/finding`, data)
    return (response.data as any)?.data || (response.data as AuditFinding)
  }

  /**
   * Generate audit report
   */
  async generateReport (id: number, format: 'pdf' | 'docx' = 'pdf'): Promise<AuditReportGenerationResult> {
    const response = await api.post<{ data: Audit, generated_document_id?: number }>(
      `${this.basePath}/${id}/generate-report`,
      { format },
    )
    const generatedFromHeader = Number(response.headers?.['x-generated-document-id'] || 0)
    const generatedFromBody = Number((response.data as any)?.generated_document_id || 0)

    return {
      audit: response.data.data,
      generatedDocumentId: generatedFromHeader || generatedFromBody || null,
    }
  }

  /**
   * Approve audit report
   */
  async approveReport (id: number): Promise<Audit> {
    const response = await api.post<{ data: Audit }>(`${this.basePath}/${id}/approve`)
    return response.data.data
  }

  /**
   * Update audit status
   */
  async updateStatus (id: number, status: AuditStatus): Promise<Audit> {
    const response = await api.put<{ data: Audit } | Audit>(`${this.basePath}/${id}/status`, { status })
    return (response.data as any)?.data || (response.data as Audit)
  }

  /**
   * Download audit report
   */
  async downloadReport (id: number, filename?: string): Promise<void> {
    await api.download(`${this.basePath}/${id}/download-report`, filename)
  }

  /**
   * Get audit report blob for inline preview or custom download flows.
   */
  async getReportBlob (id: number, options?: { format?: 'pdf' | 'docx', external?: boolean }): Promise<Blob> {
    const response = await api.get(`${this.basePath}/${id}/download-report`, {
      params: {
        format: options?.format,
        external: options?.external ? 1 : undefined,
      },
      responseType: 'blob',
    })

    return response.data as Blob
  }

  /**
   * Upload external audit report
   */
  async uploadExternalReport (id: number, file: File, version?: number): Promise<Audit> {
    const formData = new FormData()
    formData.append('report_file', file)
    if (version) {
      formData.append('version', String(version))
    }

    const response = await api.post<{ data: Audit }>(`${this.basePath}/${id}/upload-external-report`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    // Backend currently returns `{ message, data }` for this endpoint.
    return (response.data as any)?.data || (response.data as any)
  }
}

export const auditsService = new AuditsService()
