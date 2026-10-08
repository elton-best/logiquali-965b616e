import type { Norm, NormSection, NormVersion } from '@/types/api'
import api from '@/api/client'

class NormService {
  /**
   * Get all norms
   */
  async getNorms (params?: {
    domain?: string
    status?: string
    search?: string
  }): Promise<Norm[]> {
    const { data } = await api.get<{ data: Norm[] }>('/superadmin/norms', { params })
    return data.data
  }

  /**
   * Get single norm with versions and sections
   */
  async getNorm (id: number): Promise<Norm> {
    const { data } = await api.get<{ data: Norm }>(`/superadmin/norms/${id}`)
    return data.data
  }

  /**
   * Get all versions of a norm
   */
  async getVersions (normId: number): Promise<NormVersion[]> {
    const { data } = await api.get<{ data: Norm }>(`/superadmin/norms/${normId}`)
    return data.data.versions || []
  }

  /**
   * Get sections for a version
   * Returns sections as hierarchical tree (not flattened)
   */
  async getSections (normId: number, versionId?: number): Promise<NormSection[]> {
    // Get the norm data which includes the current version with sections
    const { data } = await api.get<{ data: Norm }>(`/superadmin/norms/${normId}`)

    // If versionId specified, try to find that version
    const version: NormVersion | undefined = versionId ? data.data.versions?.find(v => v.id === versionId) : data.data.currentVersion || data.data.versions?.[0]

    if (!version || !version.sections) {
      return []
    }

    // Return sections as-is (already hierarchical from backend)
    return version.sections
  }

  /**
   * Get specific version with full tree
   */
  async getVersion (normId: number, versionId: number): Promise<NormVersion> {
    const { data } = await api.get<{ data: NormVersion }>(
      `/superadmin/norms/${normId}/versions/${versionId}`,
    )
    return data.data
  }

  /**
   * Create norm manually
   */
  async createNorm (payload: {
    code: string
    name: string
    description?: string
    domain: string
    version_code: string
  }): Promise<Norm> {
    const { data } = await api.post<{ data: Norm }>('/superadmin/norms', payload)
    return data.data
  }

  /**
   * Import norm from Excel
   */
  async importExcel (
    file: File,
    metadata: {
      code: string
      name: string
      description?: string
      domain: string
      version_code: string
      action?: 'create' | 'replace' | 'merge'
    },
  ): Promise<{
    norm: Norm
    validation_errors?: string[]
  }> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('code', metadata.code)
    formData.append('name', metadata.name)
    if (metadata.description) {
      formData.append('description', metadata.description)
    }
    formData.append('domain', metadata.domain)
    formData.append('version_code', metadata.version_code)
    if (metadata.action) {
      formData.append('action', metadata.action)
    }

    const { data } = await api.post<{
      data: Norm
      validation_errors?: string[]
    }>('/superadmin/norms/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    return {
      norm: data.data,
      validation_errors: data.validation_errors,
    }
  }

  async uploadNormPdf (id: number, file: File): Promise<Norm> {
    const formData = new FormData()
    formData.append('file', file)

    const { data } = await api.post<{ data: Norm }>(`/superadmin/norms/${id}/pdf`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    return data.data
  }

  async deleteNormPdf (id: number): Promise<Norm> {
    const { data } = await api.delete<{ data: Norm }>(`/superadmin/norms/${id}/pdf`)
    return data.data
  }

  /**
   * Update norm metadata
   */
  async updateNorm (
    id: number,
    payload: {
      name?: string
      description?: string
      domain?: string
      status?: string
    },
  ): Promise<Norm> {
    const { data } = await api.put<{ data: Norm }>(`/superadmin/norms/${id}`, payload)
    return data.data
  }

  /**
   * Delete norm
   */
  async deleteNorm (id: number): Promise<void> {
    const { data } = await api.delete<{ success: boolean, message: string }>(
      `/superadmin/norms/${id}`,
    )
    if (!data.success) {
      throw new Error(data.message)
    }
  }

  /**
   * Publish norm
   */
  async publishNorm (id: number): Promise<Norm> {
    const { data } = await api.post<{ data: Norm }>(`/superadmin/norms/${id}/publish`)
    return data.data
  }

  /**
   * Archive norm
   */
  async archiveNorm (id: number): Promise<Norm> {
    const { data } = await api.post<{ data: Norm }>(`/superadmin/norms/${id}/archive`)
    return data.data
  }

  /**
   * Unarchive norm (archived -> published)
   */
  async unarchiveNorm (id: number): Promise<Norm> {
    const { data } = await api.post<{ data: Norm }>(`/superadmin/norms/${id}/unarchive`)
    return data.data
  }

  /**
   * Create section
   */
  async createSection (
    versionId: number,
    payload: {
      parent_id?: number
      type: string
      number: string
      title?: string
      content?: string
      references?: string[]
    },
  ): Promise<NormSection> {
    const { data } = await api.post<{ data: NormSection }>(
      `/superadmin/norm-versions/${versionId}/sections`,
      payload,
    )
    return data.data
  }

  /**
   * Update section
   */
  async updateSection (
    sectionId: number,
    payload: {
      title?: string
      content?: string
      references?: string[]
    },
  ): Promise<NormSection> {
    const { data } = await api.put<{ data: NormSection }>(
      `/superadmin/norm-sections/${sectionId}`,
      payload,
    )
    return data.data
  }

  /**
   * Delete section
   */
  async deleteSection (sectionId: number): Promise<void> {
    const { data } = await api.delete<{ success: boolean, message: string }>(
      `/superadmin/norm-sections/${sectionId}`,
    )
    if (!data.success) {
      throw new Error(data.message)
    }
  }

  /**
   * Download CSV template for norm import
   */
  async downloadTemplate (): Promise<Blob> {
    const response = await api.get('/superadmin/norms/download-template', {
      responseType: 'blob',
    })
    return response.data
  }

  /**
   * Export norm to CSV
   */
  async exportNorm (id: number): Promise<Blob> {
    const response = await api.get(`/superadmin/norms/${id}/export`, {
      responseType: 'blob',
    })
    return response.data
  }
}

export default new NormService()
