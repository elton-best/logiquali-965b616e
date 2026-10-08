import { ref } from 'vue'
import api from '@/api/client'

interface MyTasksParams {
  month?: string
  status?: string
  type?: string | string[]
  process_id?: string | number
}

export function useMyTasks () {
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchMyTasks = async (params: MyTasksParams = {}) => {
    loading.value = true
    error.value = null

    try {
      const queryParams = new URLSearchParams()

      if (params.month) {
        queryParams.append('month', params.month)
      }
      if (params.status) {
        queryParams.append('status', params.status)
      }
      if (params.type) {
        if (Array.isArray(params.type)) {
          for (const t of params.type) {
            queryParams.append('type', t)
          }
        } else {
          queryParams.append('type', params.type)
        }
      }
      if (params.process_id) {
        queryParams.append('process_id', String(params.process_id))
      }

      const response = await api.get(`/my-tasks?${queryParams.toString()}`)
      return response.data?.data || []
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message
      throw new Error(error.value ?? 'Erreur inconnue')
    } finally {
      loading.value = false
    }
  }

  const submitTracking = async (type: string, id: number, form: { status: string, progress_rate: number, notes?: string }) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/my-tasks/${type}/${id}/tracking`, {
        status: form.status,
        progress_rate: form.progress_rate,
        notes: form.notes,
      })
      return response.data?.data || response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message
      throw new Error(error.value ?? 'Erreur inconnue')
    } finally {
      loading.value = false
    }
  }

  const fetchTaskHistory = async (type: string, id: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/my-tasks/${type}/${id}/history`)
      return response.data?.data || []
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message
      throw new Error(error.value ?? 'Erreur inconnue')
    } finally {
      loading.value = false
    }
  }

  const downloadReport = async (type: string, id: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/my-tasks/${type}/${id}/report`, {
        responseType: 'blob',
      })

      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `rapport-${type}-${id}.docx`)
      document.body.append(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message
      throw new Error(error.value ?? 'Erreur inconnue')
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchMyTasks,
    submitTracking,
    fetchTaskHistory,
    downloadReport,
  }
}
