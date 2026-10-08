/**
 * Store Pinia pour le module Gestion Documentaire
 */

import type {
  CreateDocumentPayload,
  CreateDocumentVersionPayload,
  Document,
  DocumentCategory,
  DocumentFilters,
  DocumentStatistics,
  DocumentVersion,
} from '@/types/document'
import type { DocumentStatus, PaginationLinks, PaginationMeta } from '@/types/shared'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { documentService } from '@/services/documentService'

export const useDocumentStore = defineStore('document', () => {
  // State
  const documents = ref<Document[]>([])
  const currentDocument = ref<Document | null>(null)
  const versions = ref<DocumentVersion[]>([])
  const categories = ref<DocumentCategory[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)
  const meta = ref<PaginationMeta | null>(null)
  const links = ref<PaginationLinks | null>(null)
  const statistics = ref<DocumentStatistics | null>(null)
  const currentFilters = ref<DocumentFilters>({})

  // Getters
  const documentsByStatus = computed(() => {
    return (status: DocumentStatus) => documents.value.filter(doc => doc.status === status)
  })

  const activeDocuments = computed(() => {
    return documents.value.filter(doc => doc.is_active)
  })

  const reviewOverdueDocuments = computed(() => {
    const today = new Date().toISOString().split('T')[0] ?? ''
    return documents.value.filter(doc =>
      doc.is_active && doc.review_due_date && doc.review_due_date < today,
    )
  })

  const overdueDocuments = computed(() => reviewOverdueDocuments.value)

  const pagination = computed(() => ({
    current_page: meta.value?.current_page || 1,
    last_page: meta.value?.last_page || 1,
    per_page: meta.value?.per_page || 20,
    total: meta.value?.total ?? documents.value.length,
  }))

  const draftDocuments = computed(() => documents.value.filter(doc => doc.status === 'draft'))
  const publishedDocuments = computed(() => documents.value.filter(doc => doc.status === 'approved'))
  const archivedDocuments = computed(() => documents.value.filter(doc => Boolean(doc.archived_at)))

  // Actions
  async function fetchDocuments (filters?: DocumentFilters) {
    loading.value = true
    error.value = null
    try {
      currentFilters.value = { ...currentFilters.value, ...filters }
      const response = await documentService.getAll(currentFilters.value)
      documents.value = response.data
      meta.value = response.meta
      links.value = response.links
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchDocumentById (id: number) {
    loading.value = true
    try {
      const document = await documentService.getById(id)
      currentDocument.value = document
      return document
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchDocument (id: number) {
    return fetchDocumentById(id)
  }

  async function uploadDocument (_file: File, metadata: CreateDocumentPayload) {
    loading.value = true
    try {
      const newDocument = await documentService.create(metadata)
      documents.value.unshift(newDocument)
      return newDocument
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function uploadNewVersion (documentId: number, file: File, data: CreateDocumentVersionPayload) {
    loading.value = true
    try {
      const newVersion = await documentService.uploadVersion(documentId, {
        document_id: documentId,
        file,
        change_summary: data.change_summary || '',
      } as any)
      await fetchDocumentById(documentId)
      return newVersion
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function uploadVersion (documentId: number, file: File, changeSummary?: string) {
    return uploadNewVersion(documentId, file, {
      document_id: documentId,
      change_summary: changeSummary || '',
    } as CreateDocumentVersionPayload)
  }

  async function fetchCategories () {
    loading.value = true
    try {
      const response = await documentService.getCategories()
      categories.value = response
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchStatistics () {
    loading.value = true
    try {
      const stats = await documentService.getStatistics()
      statistics.value = stats
      return stats
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function downloadDocument (id: number, versionId?: number) {
    loading.value = true
    try {
      const doc = currentDocument.value?.id === id
        ? currentDocument.value
        : documents.value.find(d => d.id === id)
      const resolvedVersionId = versionId ?? doc?.current_version?.id
      if (!resolvedVersionId) {
        throw new Error('Aucune version téléchargeable trouvée pour ce document')
      }
      const blob = versionId
        ? await documentService.downloadVersion(id, versionId)
        : await documentService.downloadVersion(id, resolvedVersionId)
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${doc?.title || 'document'}.${doc?.file_extension || 'pdf'}`
      link.click()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function submitForApproval (id: number) {
    loading.value = true
    try {
      const document = await documentService.submitForApproval(id)
      currentDocument.value = document
      const index = documents.value.findIndex(d => d.id === id)
      if (index !== -1) {
        documents.value[index] = document
      }
      return document
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function archiveDocument (id: number) {
    loading.value = true
    try {
      const document = await documentService.archive(id)
      currentDocument.value = document
      const index = documents.value.findIndex(d => d.id === id)
      if (index !== -1) {
        documents.value[index] = document
      }
      return document
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function deleteDocument (id: number) {
    loading.value = true
    try {
      await documentService.delete(id)
      documents.value = documents.value.filter(doc => doc.id !== id)
      if (currentDocument.value?.id === id) {
        currentDocument.value = null
      }
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  function resetFilters () {
    currentFilters.value = {}
  }

  async function setPage (page: number) {
    await fetchDocuments({ ...currentFilters.value, page })
  }

  function reset () {
    documents.value = []
    currentDocument.value = null
    versions.value = []
    categories.value = []
    loading.value = false
    error.value = null
  }

  return {
    documents,
    currentDocument,
    versions,
    categories,
    loading,
    error,
    meta,
    links,
    statistics,
    pagination,
    documentsByStatus,
    activeDocuments,
    reviewOverdueDocuments,
    overdueDocuments,
    draftDocuments,
    publishedDocuments,
    archivedDocuments,
    fetchDocuments,
    fetchDocument,
    fetchDocumentById,
    uploadDocument,
    uploadNewVersion,
    uploadVersion,
    fetchCategories,
    fetchStatistics,
    downloadDocument,
    submitForApproval,
    archiveDocument,
    deleteDocument,
    resetFilters,
    setPage,
    reset,
  }
})
