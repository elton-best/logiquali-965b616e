import { ref } from 'vue'
import { type DocumentTypeConfiguration, documentTypeConfigurationApi } from '@/api/documentTypeConfiguration'

export function useDocumentTypeConfigurations () {
  const configurations = ref<DocumentTypeConfiguration[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const loadConfigurations = async (filters?: { enterprise_id?: number, site_id?: number, is_active?: boolean }) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeConfigurationApi.list(filters)
      configurations.value = response.data
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getConfiguration = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeConfigurationApi.get(id)
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const createConfiguration = async (data: Omit<DocumentTypeConfiguration, 'id' | 'created_at' | 'updated_at'>) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeConfigurationApi.create(data)
      configurations.value.push(response.data)
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateConfiguration = async (id: number, data: Partial<DocumentTypeConfiguration>) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeConfigurationApi.update(id, data)
      const index = configurations.value.findIndex(c => c.id === id)
      if (index !== -1) {
        configurations.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteConfiguration = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      await documentTypeConfigurationApi.delete(id)
      configurations.value = configurations.value.filter(c => c.id !== id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la suppression'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const toggleActive = async (id: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await documentTypeConfigurationApi.toggleActive(id)
      const index = configurations.value.findIndex(c => c.id === id)
      if (index !== -1) {
        configurations.value[index] = response.data
      }
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors du changement de statut'
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    configurations,
    loading,
    error,
    loadConfigurations,
    getConfiguration,
    createConfiguration,
    updateConfiguration,
    deleteConfiguration,
    toggleActive,
  }
}
