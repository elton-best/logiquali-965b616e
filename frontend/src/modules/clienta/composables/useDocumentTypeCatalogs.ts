import type { DocumentTypeCatalog } from '../types/document.types'
import { ref } from 'vue'
import { documentTypeCatalogsApi } from '@/api/documents'

export function useDocumentTypeCatalogs () {
  const items = ref<DocumentTypeCatalog[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchItems = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeCatalogsApi.getAll(params)
      items.value = response.data
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  const createItem = async (data: Partial<DocumentTypeCatalog>) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeCatalogsApi.create(data)
      items.value.push(response.data)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateItem = async (id: number, data: Partial<DocumentTypeCatalog>) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeCatalogsApi.update(id, data)
      const index = items.value.findIndex(item => item.id === id)
      if (index !== -1) {
        items.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteItem = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await documentTypeCatalogsApi.delete(id)
      items.value = items.value.filter(item => item.id !== id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    loading,
    error,
    fetchItems,
    createItem,
    updateItem,
    deleteItem,
  }
}
