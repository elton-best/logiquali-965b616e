import { beforeEach, describe, expect, it, vi } from 'vitest'
import apiClient from '@/api/client'
import { pdfExportService } from '@/services/pdfExportService'

vi.mock('@/api/client', () => ({
  default: {
    post: vi.fn(),
  },
  apiClient: {
    post: vi.fn(),
  },
}))

describe('pdfExportService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('exports document with correct payload', async () => {
    const mockBlob = new Blob(['pdf content'], { type: 'application/pdf' })
    vi.mocked(apiClient.post).mockResolvedValue({ data: mockBlob })

    const payload = {
      document_type: 'audit_report',
      document_id: 1,
      content: {
        html: '<h1>Test</h1>',
        title: 'Test Document',
      },
      layout: 'professional' as const,
    }

    const result = await pdfExportService.exportDocument(payload)

    expect(apiClient.post).toHaveBeenCalledWith(
      '/pdf/export',
      payload,
      { responseType: 'blob' },
    )
    expect(result).toBeInstanceOf(Blob)
  })

  it('downloads pdf with correct filename', () => {
    const mockBlob = new Blob(['pdf'], { type: 'application/pdf' })
    const createElementSpy = vi.spyOn(document, 'createElement')
    const mockLink = {
      href: '',
      download: '',
      click: vi.fn(),
      remove: vi.fn(),
    }
    createElementSpy.mockReturnValue(mockLink as any)

    pdfExportService.downloadPdf(mockBlob, 'test.pdf')

    expect(mockLink.download).toBe('test.pdf')
    expect(mockLink.click).toHaveBeenCalled()
  })
})
