import api from '@/api/client'

export interface QhsePolicy {
  id?: number
  site_id?: number
  mission: string
  vision?: string
  values?: string[]
  axes?: string[]
  commitments?: string[]
  quality_policy?: string
  environmental_policy?: string
  health_safety_policy?: string
  effective_date?: string
  status?: string
  version?: string
  is_current?: boolean
  updated_at?: string
}

export interface OrgChart {
  id: number
  file_name: string
  file_path: string
  file_type: string
  file_size: number
  is_current: boolean
  download_url?: string
  created_at: string
  updated_at?: string
}

function normalizeOrgChartPayload (payload: any): OrgChart | null {
  if (!payload) {
    return null
  }

  const candidate = payload?.data ?? payload
  if (!candidate || typeof candidate !== 'object') {
    return null
  }

  if (typeof candidate.id !== 'number') {
    return null
  }
  return candidate as OrgChart
}

export interface JobDescription {
  id?: number
  user_id?: number
  job_title: string
  replacement_job_title?: string
  department?: string
  reports_to_id?: number
  mission: string
  activities?: string
  main_activities?: string[]
  secondary_activities?: string[]
  internal_relations?: string[]
  external_relations?: string[]
  work_location?: string
  work_schedule?: string
  travel_required?: boolean
  physical_requirements?: string
  required_skills?: string[]
  required_experience?: string
  required_education?: string
  certifications_required?: string[]
}

export interface Responsibility {
  id?: number
  process_id: number
  user_id?: number
  level: string
  roles?: string
  deliverables?: string
  role_title?: string
  responsibilities?: string[]
  authorities?: string[]
  start_date?: string
  end_date?: string
}

export interface JobDescriptionCollaborator {
  id: number
  first_name?: string
  last_name?: string
  full_name: string
  email: string
  job_title?: string
  site_id?: number | null
  site_name?: string | null
  is_headquarter_site?: boolean
  signature_path?: string | null
  signature_url?: string | null
}

export const leadershipService = {
  // QHSE Policies
  async getPolicies (siteId?: number) {
    const response = await api.get('/qhse-policies', {
      params: siteId ? { site_id: siteId } : undefined,
    })
    return response.data
  },

  async getCurrentPolicy (siteId?: number) {
    const response = await api.get('/qhse-policies/current', {
      params: siteId ? { site_id: siteId } : undefined,
    })
    return response.data
  },

  async createPolicy (data: QhsePolicy) {
    const response = await api.post('/qhse-policies', data)
    return response.data
  },

  async updatePolicy (id: number, data: Partial<QhsePolicy>) {
    const response = await api.put(`/qhse-policies/${id}`, data)
    return response.data
  },

  async validatePolicy (id: number) {
    const response = await api.post(`/qhse-policies/${id}/validate`)
    return response.data
  },

  async submitPolicy (id: number) {
    const response = await api.post(`/qhse-policies/${id}/submit`)
    return response.data
  },

  async exportPolicyPdf (id: number, generationContext?: {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }) {
    const response = await api.get(`/qhse-policies/${id}/export-pdf`, {
      params: generationContext,
      responseType: 'blob',
    })
    return response.data
  },

  async exportPolicyDocx (id: number, generationContext?: {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }) {
    const response = await api.get(`/qhse-policies/${id}/export-docx`, {
      params: generationContext,
      responseType: 'blob',
    })
    return response.data
  },

  async exportPolicyDocxWithMeta (id: number, generationContext?: {
    document_type_catalog_id: number
    process_id?: number | null
    process_name?: string
    process_type?: string
    process_abbreviation?: string
  }) {
    const response = await api.get(`/qhse-policies/${id}/export-docx`, {
      params: generationContext,
      responseType: 'blob',
    })
    return {
      blob: response.data as Blob,
      generatedDocumentId: Number(response.headers?.['x-generated-document-id'] || 0) || null,
    }
  },

  async getPolicyHistory (siteId?: number) {
    const response = await api.get('/qhse-policies/history', {
      params: siteId ? { site_id: siteId } : undefined,
    })
    return response.data
  },

  // Org Chart
  async uploadOrgChart (file: File, siteId?: number | null) {
    const formData = new FormData()
    formData.append('file', file)
    if (siteId && Number.isFinite(siteId)) {
      formData.append('site_id', String(siteId))
    }
    const response = await api.post('/org-chart/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return normalizeOrgChartPayload(response.data)
  },

  async getCurrentOrgChart (siteId?: number | null) {
    const response = await api.get('/org-chart/current', {
      params: siteId ? { site_id: siteId } : undefined,
    })
    return normalizeOrgChartPayload(response.data)
  },

  async deleteOrgChart (id: number) {
    if (!Number.isInteger(id) || id <= 0) {
      throw new Error('Identifiant organigramme invalide')
    }
    await api.delete(`/org-chart/${id}`)
  },

  // Job Descriptions
  async getJobDescriptions () {
    const response = await api.get('/job-descriptions')
    return response.data
  },

  async createJobDescription (data: JobDescription) {
    const response = await api.post('/job-descriptions', data)
    return response.data
  },

  async updateJobDescription (id: number, data: Partial<JobDescription>) {
    const response = await api.put(`/job-descriptions/${id}`, data)
    return response.data
  },

  async deleteJobDescription (id: number) {
    await api.delete(`/job-descriptions/${id}`)
  },

  // Responsibilities
  async getResponsibilities () {
    const response = await api.get('/responsibilities')
    return response.data
  },

  async createResponsibility (data: Responsibility) {
    const response = await api.post('/responsibilities', data)
    return response.data
  },

  async updateResponsibility (id: number, data: Partial<Responsibility>) {
    const response = await api.put(`/responsibilities/${id}`, data)
    return response.data
  },

  async deleteResponsibility (id: number) {
    await api.delete(`/responsibilities/${id}`)
  },

  async exportResponsibilitiesPdf () {
    const response = await api.get('/responsibilities/export-pdf', {
      responseType: 'blob',
    })
    return response.data
  },

  async exportResponsibilitiesPdfWithMeta () {
    const response = await api.get('/responsibilities/export-pdf', {
      responseType: 'blob',
    })
    return {
      blob: response.data as Blob,
      generatedDocumentId: Number(response.headers?.['x-generated-document-id'] || 0) || null,
    }
  },

  // Processes
  async getProcesses () {
    const response = await api.get('/processes')
    return response.data
  },

  // Users/Collaborators
  async getCollaborators () {
    const response = await api.get('/users')
    return response.data.data || response.data
  },

  async getJobDescriptionCollaborators (): Promise<
    JobDescriptionCollaborator[]
  > {
    const response = await api.get('/users/job-description-collaborators')
    return response.data?.data || []
  },
}
