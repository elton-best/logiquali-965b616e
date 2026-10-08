import { ref, watch } from 'vue'
import { apiClient } from '@/api/client'

export interface CodeGenerationContext {
  siteId?: number
  type: string
  processId?: number
  processus?: string
}

export function useDocumentCodeGeneration () {
  const codePreview = ref<string>('')
  const loading = ref(false)
  const error = ref<string | null>(null)

  const generateCodePreview = async (context: CodeGenerationContext) => {
    if (!context.type) {
      codePreview.value = ''
      return
    }

    loading.value = true
    error.value = null

    try {
      const params: any = {
        type: context.type,
      }

      if (context.siteId) {
        params.site_id = context.siteId
      }

      if (context.processId) {
        params.process_id = context.processId
      }

      if (context.processus) {
        params.processus = context.processus
      }

      const response = await apiClient.get('/documents/preview-code', { params })
      codePreview.value = response.data.data?.code || ''
      return response.data.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la génération du code'
      codePreview.value = ''
      throw error_
    } finally {
      loading.value = false
    }
  }

  const clearPreview = () => {
    codePreview.value = ''
    error.value = null
  }

  return {
    codePreview,
    loading,
    error,
    generateCodePreview,
    clearPreview,
  }
}
