import type { DocumentStats, UnifiedDocument } from '../types/document-unified.types'
import { ref } from 'vue'
import { documentsApi } from '@/api/documents'

/**
 * Composable unifié pour la gestion des documents
 */
export function useDocuments () {
  const documents = ref<UnifiedDocument[]>([])
  const document = ref<UnifiedDocument | null>(null)
  const stats = ref<DocumentStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchDocuments = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.getAll(params)
      const payload = response.data as any
      documents.value = Array.isArray(payload)
        ? payload
        : (Array.isArray(payload?.data)
            ? payload.data
            : [])
    } catch (error_: any) {
      error.value = error_.message
      documents.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchDocument = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.getById(id)
      document.value = response.data
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  const createDocument = async (data: FormData) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.create(data)
      documents.value.push(response.data)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateDocument = async (id: number, data: FormData) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.update(id, data)
      const index = documents.value.findIndex(d => d.id === id)
      if (index !== -1) {
        documents.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteDocument = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await documentsApi.delete(id)
      documents.value = documents.value.filter(d => d.id !== id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const addVersion = async (id: number, data: FormData) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.addVersion(id, data)
      await fetchDocument(id)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const linkDocument = async (id: number, data: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.linkDocument(id, data)
      await fetchDocument(id)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const unlinkDocument = async (id: number, linkId: number) => {
    loading.value = true
    error.value = null
    try {
      await documentsApi.unlinkDocument(id, linkId)
      await fetchDocument(id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const changeState = async (id: number, statut: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.changeState(id, statut)
      const index = documents.value.findIndex(d => d.id === id)
      if (index !== -1) {
        documents.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const scheduleReview = async (id: number, data: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.scheduleReview(id, data)
      await fetchDocument(id)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const completeReview = async (reviewId: number, commentaire?: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.completeReview(reviewId, commentaire)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const fetchStats = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentsApi.getStats(params)
      stats.value = response.data
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  return {
    documents,
    document,
    stats,
    loading,
    error,
    fetchDocuments,
    fetchDocument,
    createDocument,
    updateDocument,
    deleteDocument,
    addVersion,
    linkDocument,
    unlinkDocument,
    changeState,
    scheduleReview,
    completeReview,
    fetchStats,
  }
}
