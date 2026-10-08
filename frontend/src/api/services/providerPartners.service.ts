import api from '@/api/client'

export interface ProviderPartner {
  id: number
  enterprise_id: number
  reference: string
  designation: string
  provider_type: string
  legal_form?: string | null
  service_offers?: string | null
  phone_primary?: string | null
  phone_secondary?: string | null
  email?: string | null
  ifu?: string | null
  experience_years?: number | null
  evaluation_observation?: string | null
  metadata?: Record<string, unknown> | null
  created_at: string
  updated_at: string
  contract?: ProviderContract | null
  files?: ProviderPartnerFile[]
}

export interface ProviderPartnerFile {
  id: number
  enterprise_id: number
  provider_partner_id: number
  title?: string | null
  category?: string | null
  note?: string | null
  file_path: string
  file_name: string
  file_mime?: string | null
  file_size?: number | null
  download_url?: string | null
  created_at: string
  updated_at: string
}

export interface ProviderContractTemplate {
  id: number
  enterprise_id: number
  title: string
  content: string
  placeholders?: string[]
  created_at: string
  updated_at: string
}

export interface ProviderContract {
  id: number
  enterprise_id: number
  provider_partner_id: number
  contract_reference?: string | null
  template_title?: string | null
  template_content?: string | null
  filled_content?: string | null
  start_date?: string | null
  end_date?: string | null
  amount?: string | null
  currency?: string | null
  payment_terms?: string | null
  signed_at?: string | null
  signed_file_path?: string | null
  signed_file_name?: string | null
  signed_file_url?: string | null
  generated_file_path?: string | null
  generated_file_name?: string | null
  generated_file_url?: string | null
  generated_at?: string | null
  history?: ProviderContractArchiveEntry[]
  status: 'draft' | 'ready' | 'signed' | 'archived'
  meta?: Record<string, unknown> | null
}

export interface ProviderContractArchiveEntry {
  id: string
  archived_at?: string | null
  archived_by?: number | null
  note?: string | null
  status?: string | null
  contract_reference?: string | null
  template_title?: string | null
  start_date?: string | null
  end_date?: string | null
  amount?: string | number | null
  currency?: string | null
  payment_terms?: string | null
  signed_file_name?: string | null
  signed_file_url?: string | null
  generated_file_name?: string | null
  generated_file_url?: string | null
}

export interface ProviderPartnerPayload {
  designation: string
  provider_type?: string
  legal_form?: string | null
  service_offers?: string | null
  phone_primary?: string | null
  phone_secondary?: string | null
  email?: string | null
  ifu?: string | null
  experience_years?: number | null
  evaluation_observation?: string | null
}

export interface ProviderContractPayload {
  contract_reference?: string | null
  template_title?: string | null
  template_content?: string | null
  filled_content?: string | null
  start_date?: string | null
  end_date?: string | null
  amount?: number | null
  currency?: string | null
  payment_terms?: string | null
  status?: 'draft' | 'ready' | 'signed' | 'archived'
  meta?: Record<string, unknown> | null
}

class ProviderPartnersService {
  private readonly basePath = '/provider-partners'

  async list (params: { search?: string, page?: number, per_page?: number } = {}) {
    const response = await api.get(this.basePath, { params })
    return response.data
  }

  async create (payload: ProviderPartnerPayload): Promise<ProviderPartner> {
    const response = await api.post<{ data: ProviderPartner }>(this.basePath, payload)
    return response.data.data
  }

  async update (id: number, payload: Partial<ProviderPartnerPayload>): Promise<ProviderPartner> {
    const response = await api.put<{ data: ProviderPartner }>(`${this.basePath}/${id}`, payload)
    return response.data.data
  }

  async remove (id: number): Promise<void> {
    await api.delete(`${this.basePath}/${id}`)
  }

  async importFile (file: File): Promise<{ created: number, updated: number, message: string }> {
    const formData = new FormData()
    formData.append('file', file)
    const response = await api.post(this.basePath + '/import-file', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  }

  async exportXlsx (params: { search?: string } = {}): Promise<Blob> {
    const response = await api.get(this.basePath + '/export-xlsx', {
      params,
      responseType: 'blob',
    })
    return response.data
  }

  async getTemplate (): Promise<ProviderContractTemplate> {
    const response = await api.get<{ data: ProviderContractTemplate }>('/provider-contract-template')
    return response.data.data
  }

  async saveTemplate (payload: { title: string, content: string }): Promise<ProviderContractTemplate> {
    const response = await api.put<{ data: ProviderContractTemplate }>('/provider-contract-template', payload)
    return response.data.data
  }

  async getContract (providerId: number): Promise<ProviderContract> {
    const response = await api.get<{ data: ProviderContract }>(`${this.basePath}/${providerId}/contract`)
    return response.data.data
  }

  async saveContract (providerId: number, payload: ProviderContractPayload): Promise<ProviderContract> {
    const response = await api.put<{ data: ProviderContract }>(`${this.basePath}/${providerId}/contract`, payload)
    return response.data.data
  }

  async uploadSignedContract (providerId: number, file: File): Promise<ProviderContract> {
    const formData = new FormData()
    formData.append('file', file)
    const response = await api.upload<{ data: ProviderContract }>(`${this.basePath}/${providerId}/contract/upload`, formData)
    return response.data.data
  }

  async generateContractPdf (providerId: number): Promise<ProviderContract> {
    const response = await api.post<{ data: ProviderContract }>(`${this.basePath}/${providerId}/contract/generate-pdf`)
    return response.data.data
  }

  async archiveContract (providerId: number, payload: { note?: string | null } = {}): Promise<ProviderContract> {
    const response = await api.post<{ data: ProviderContract }>(`${this.basePath}/${providerId}/contract/archive`, payload)
    return response.data.data
  }

  async previewContract (
    providerId: number,
    payload: {
      content?: string
      draft?: {
        start_date?: string | null
        end_date?: string | null
        amount?: number | null
        currency?: string | null
        payment_terms?: string | null
        contract_reference?: string | null
      }
    } = {},
  ): Promise<{ template: string, rendered: string }> {
    const response = await api.post<{ data: { template: string, rendered: string } }>(
      `${this.basePath}/${providerId}/contract/preview`,
      payload,
    )
    return response.data.data
  }

  async listFiles (providerId: number): Promise<ProviderPartnerFile[]> {
    const response = await api.get<{ data: ProviderPartnerFile[] }>(`${this.basePath}/${providerId}/files`)
    return Array.isArray(response.data.data) ? response.data.data : []
  }

  async uploadFile (
    providerId: number,
    payload: { file: File, title?: string | null, category?: string | null, note?: string | null },
  ): Promise<ProviderPartnerFile> {
    const formData = new FormData()
    formData.append('file', payload.file)
    if (payload.title) {
      formData.append('title', payload.title)
    }
    if (payload.category) {
      formData.append('category', payload.category)
    }
    if (payload.note) {
      formData.append('note', payload.note)
    }

    const response = await api.upload<{ data: ProviderPartnerFile }>(`${this.basePath}/${providerId}/files`, formData)
    return response.data.data
  }

  async removeFile (providerId: number, fileId: number): Promise<void> {
    await api.delete(`${this.basePath}/${providerId}/files/${fileId}`)
  }
}

export const providerPartnersService = new ProviderPartnersService()
