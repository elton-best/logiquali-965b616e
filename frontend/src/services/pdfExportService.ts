import apiClient from '@/api/client'

export interface PdfExportPayload {
  document_type: string
  document_id?: number
  content: {
    html: string
    title: string
    version?: string
    effective_date?: string
    code?: string
  }
  layout?: 'professional' | 'minimal'
  source_updated_at?: string
}

export const pdfExportService = {
  async exportDocument (payload: PdfExportPayload): Promise<Blob> {
    const response = await apiClient.post('/pdf/export', payload, {
      responseType: 'blob',
    })
    return (response as any).data as Blob
  },

  async exportDocumentWithMeta (payload: PdfExportPayload): Promise<{ blob: Blob, generatedDocumentId: number | null }> {
    const response = await apiClient.post('/pdf/export', payload, {
      responseType: 'blob',
    })

    return {
      blob: (response as any).data as Blob,
      generatedDocumentId: Number((response as any).headers?.['x-generated-document-id'] || 0) || null,
    }
  },

  async previewDocument (payload: PdfExportPayload): Promise<string> {
    const response = await apiClient.post('/pdf/preview', payload) as any
    if (typeof response === 'string') {
      return response
    }

    return (
      response?.html
      || response?.data?.html
      || response?.data?.data?.html
      || response?.payload?.html
      || ''
    )
  },

  downloadPdf (blob: Blob, filename: string) {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  },
}
