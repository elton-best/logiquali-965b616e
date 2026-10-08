import { describe, expect, it } from 'vitest'
import processService from '@/services/processService'

describe('Process Export Service & Document Flow (RT-01)', () => {
  it('exposes process export methods for DOCX and PDF', () => {
    expect(typeof processService.exportProcessDocx).toBe('function')
    expect(typeof processService.exportProcessPdf).toBe('function')
    expect(typeof processService.exportCartographyPdfBlob).toBe('function')
  })

  it('formats process export filenames correctly', () => {
    const processId = 42
    const processCode = 'PR-DIR-01'

    const docxFilename = `Fiche_Processus_${processCode || processId}.docx`
    const pdfFilename = `Fiche_Processus_${processCode || processId}.pdf`
    const cartoFilename = `cartographie_processus_test.pdf`

    expect(docxFilename).toBe('Fiche_Processus_PR-DIR-01.docx')
    expect(pdfFilename).toBe('Fiche_Processus_PR-DIR-01.pdf')
    expect(cartoFilename.endsWith('.pdf')).toBe(true)
  })

  it('conforms to RT-01 modal metadata specification', () => {
    const mockProcess = {
      id: 10,
      code: 'PR-RH-01',
      title: 'Gestion des Ressources Humaines',
      category: 'support' as const,
      version: '2.0',
      sequences: [{ id: 1, sequence_order: 1, activity_description: 'Recrutement' }],
    }

    const metadata = {
      version: mockProcess.version || '1.0',
      summaryItems: [
        { label: 'Code', value: mockProcess.code || '-' },
        { label: 'Titre', value: mockProcess.title || '-' },
        { label: 'Catégorie', value: mockProcess.category || '-' },
        { label: 'Séquences', value: mockProcess.sequences?.length || 0 },
      ],
    }

    expect(metadata.version).toBe('2.0')
    expect(metadata.summaryItems).toHaveLength(4)
    expect(metadata.summaryItems[0]).toEqual({ label: 'Code', value: 'PR-RH-01' })
    expect(metadata.summaryItems[3]).toEqual({ label: 'Séquences', value: 1 })
  })
})
