import type { Nomenclature } from '../types/document.types'
import { ref } from 'vue'
import { nomenclaturesApi } from '@/api/documents'

export function useNomenclatures () {
  const nomenclatures = ref<Nomenclature[]>([])
  const nomenclature = ref<Nomenclature | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchNomenclatures = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await nomenclaturesApi.getAll(params)
      nomenclatures.value = response.data
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  const fetchNomenclature = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await nomenclaturesApi.getById(id)
      nomenclature.value = response.data
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  const createNomenclature = async (data: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await nomenclaturesApi.create(data)
      nomenclatures.value.push(response.data)
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateNomenclature = async (id: number, data: any) => {
    loading.value = true
    error.value = null
    try {
      const response = await nomenclaturesApi.update(id, data)
      const index = nomenclatures.value.findIndex(n => n.id === id)
      if (index !== -1) {
        nomenclatures.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteNomenclature = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await nomenclaturesApi.delete(id)
      nomenclatures.value = nomenclatures.value.filter(n => n.id !== id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    nomenclatures,
    nomenclature,
    loading,
    error,
    fetchNomenclatures,
    fetchNomenclature,
    createNomenclature,
    updateNomenclature,
    deleteNomenclature,
  }
}
