import api from '@/api/client'

export type ComplianceApiNamespace = 'compliance' | 'product_requirements'
export type ComplianceStatus = 'compliant' | 'partial' | 'non_compliant' | 'not_applicable'
export type RegulatoryChangeStatus = 'existing' | 'new' | 'modified' | null
export type ValidityStatus = 'in_force' | 'obsolete' | null
export type EvaluationFrequency = 'monthly' | 'quarterly' | 'semiannual' | 'annual' | 'on_demand' | null
export type ActionStatus = 'pending' | 'in_progress' | 'done' | 'cancelled'

export interface ComplianceNorm {
  id: number
  code: string
  name: string
}

export interface ComplianceAspect {
  id: number
  site_id: number
  name: string
  description?: string
  norms: ComplianceNorm[]
}

export interface ComplianceTextAction {
  id?: number
  title: string
  responsible_id?: number | null
  responsible_name?: string | null
  due_date?: string
  status?: ActionStatus
  comments?: string
}

export interface ComplianceObligation {
  id: number
  ref?: string
  aspect: ComplianceAspect
  regulatory_reference: string
  description?: string
  applicable_requirement?: string
  watch_source?: string
  entry_into_force_date?: string
  regulatory_change_status?: RegulatoryChangeStatus
  validity_status?: ValidityStatus
  compliance_status: ComplianceStatus
  actions_corrective_preventive?: string
  deadline?: string
  responsible_id?: number | null
  responsible_name?: string | null
  evaluation_frequency?: EvaluationFrequency
  comments?: string
  actions: ComplianceTextAction[]
}

export interface ComplianceObligationFilters {
  site_id?: number
  per_page?: number
  q?: string
  compliance_status?: ComplianceStatus
  validity_status?: ValidityStatus
}

function resolveApiEndpoints (namespace: ComplianceApiNamespace) {
  if (namespace === 'product_requirements') {
    return {
      obligations: '/prod-req-obligations',
      aspects: '/product-service-requirements-obligation-aspects',
      templateXlsx: '/prod-req-obligations/template',
      exportXlsx: '/prod-req-obligations/export-xlsx',
      importXlsx: '/prod-req-obligations/import-xlsx',
    }
  }

  return {
    obligations: '/compliance-obligations',
    aspects: '/compliance-obligation-aspects',
    templateXlsx: '/compliance-obligations/template',
    exportXlsx: '/compliance-obligations/export-xlsx',
    importXlsx: '/compliance-obligations/import-xlsx',
  }
}

export const complianceObligationsService = {
  async list (filters: ComplianceObligationFilters = {}, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.get(endpoints.obligations, { params: filters })
    return {
      data: (response.data?.data || []) as ComplianceObligation[],
      meta: response.data?.meta || {},
    }
  },

  async listAspects (siteId: number, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.get(endpoints.aspects, { params: { site_id: siteId } })
    return (response.data?.data || []) as ComplianceAspect[]
  },

  async createAspect (payload: { site_id: number, name: string, description?: string, norm_ids?: number[] }, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.post(endpoints.aspects, payload)
    return response.data?.data as { id: number, site_id: number, name: string, description?: string }
  },

  async updateAspect (id: number, payload: { name?: string, description?: string, norm_ids?: number[] }, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.put(`${endpoints.aspects}/${id}`, payload)
    return response.data?.data as ComplianceAspect
  },

  async exportXlsx (siteId: number, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.get(endpoints.exportXlsx, {
      params: { site_id: siteId },
      responseType: 'blob',
    })
    const disposition = String(response.headers?.['content-disposition'] || '')
    const matched = disposition.match(/filename="?([^"]+)"?/)
    const filename = matched?.[1] || `registre_veille_reglementaire_${Date.now()}.xlsx`
    return { blob: response.data as Blob, filename }
  },

  async downloadTemplateXlsx (namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.get(endpoints.templateXlsx, {
      responseType: 'blob',
    })
    const disposition = String(response.headers?.['content-disposition'] || '')
    const matched = disposition.match(/filename="?([^"]+)"?/)
    const filename = matched?.[1] || `modele_import_registre_${Date.now()}.xlsx`
    return { blob: response.data as Blob, filename }
  },

  async importXlsx (siteId: number, file: File, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const formData = new FormData()
    formData.append('site_id', String(siteId))
    formData.append('file', file)

    const response = await api.post(endpoints.importXlsx, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    return response.data?.data as {
      imported_rows: number
      created_aspects: number
      updated_texts: number
    }
  },

  async create (payload: Record<string, any>, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.post(endpoints.obligations, payload)
    return response.data?.data as ComplianceObligation
  },

  async update (id: number, payload: Record<string, any>, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    const response = await api.put(`${endpoints.obligations}/${id}`, payload)
    return response.data?.data as ComplianceObligation
  },

  async remove (id: number, namespace: ComplianceApiNamespace = 'compliance') {
    const endpoints = resolveApiEndpoints(namespace)
    await api.delete(`${endpoints.obligations}/${id}`)
  },
}
