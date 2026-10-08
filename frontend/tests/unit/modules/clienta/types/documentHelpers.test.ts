import type { Document, DocumentInventory, UnifiedDocument } from '@/modules/clienta/types/document-unified.types'
import { describe, expect, it } from 'vitest'
import { DocumentHelpers } from '@/modules/clienta/types/document-unified.types'

describe('DocumentHelpers', () => {
  describe('getTitle', () => {
    it('returns title from new Document format', () => {
      const doc: Document = {
        id: 1,
        title: 'New Format Title',
        code: 'DOC-001',
        type: 'PRC',
        status: 'draft',
        file_path: 'test.pdf',
        author_id: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getTitle(doc)).toBe('New Format Title')
    })

    it('returns nom from legacy DocumentInventory format', () => {
      const doc: DocumentInventory = {
        id: 1,
        nom: 'Legacy Format Name',
        code: 'DOC-001',
        type: 'PRC',
        statut: 'brouillon',
        fichier: 'test.pdf',
        created_by: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getTitle(doc)).toBe('Legacy Format Name')
    })

    it('returns empty string when both title and nom are missing', () => {
      const doc: Partial<UnifiedDocument> = {
        id: 1,
        code: 'DOC-001',
      }

      expect(DocumentHelpers.getTitle(doc as UnifiedDocument)).toBe('')
    })
  })

  describe('getFilePath', () => {
    it('returns file_path from new Document format', () => {
      const doc: Document = {
        id: 1,
        title: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        status: 'draft',
        file_path: 'documents/new.pdf',
        author_id: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getFilePath(doc)).toBe('documents/new.pdf')
    })

    it('returns fichier from legacy DocumentInventory format', () => {
      const doc: DocumentInventory = {
        id: 1,
        nom: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        statut: 'brouillon',
        fichier: 'documents/legacy.pdf',
        created_by: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getFilePath(doc)).toBe('documents/legacy.pdf')
    })
  })

  describe('getStatus', () => {
    it('returns status from new Document format', () => {
      const doc: Document = {
        id: 1,
        title: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        status: 'approved',
        file_path: 'test.pdf',
        author_id: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getStatus(doc)).toBe('approved')
    })

    it('maps statut to status for legacy format', () => {
      const doc: DocumentInventory = {
        id: 1,
        nom: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        statut: 'valide',
        fichier: 'test.pdf',
        created_by: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getStatus(doc)).toBe('approved')
    })
  })

  describe('getStatut', () => {
    it('returns statut from legacy format', () => {
      const doc: DocumentInventory = {
        id: 1,
        nom: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        statut: 'valide',
        fichier: 'test.pdf',
        created_by: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getStatut(doc)).toBe('valide')
    })

    it('maps status to statut for new format', () => {
      const doc: Document = {
        id: 1,
        title: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        status: 'approved',
        file_path: 'test.pdf',
        author_id: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.getStatut(doc)).toBe('valide')
    })
  })

  describe('isMigrated', () => {
    it('returns true for new Document format', () => {
      const doc: Document = {
        id: 1,
        title: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        status: 'draft',
        file_path: 'test.pdf',
        author_id: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.isMigrated(doc)).toBe(true)
    })

    it('returns false for legacy DocumentInventory format', () => {
      const doc: DocumentInventory = {
        id: 1,
        nom: 'Test',
        code: 'DOC-001',
        type: 'PRC',
        statut: 'brouillon',
        fichier: 'test.pdf',
        created_by: 1,
        site_id: 1,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
      }

      expect(DocumentHelpers.isMigrated(doc)).toBe(false)
    })
  })
})
