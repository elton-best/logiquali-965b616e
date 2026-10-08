import { apiClient } from '../api/client'

export const habilitationService = {
  async getAll () {
    const response = await apiClient.get('/habilitations')
    return response.data.data
  },

  async getById (id: string) {
    const response = await apiClient.get(`/habilitations/${id}`)
    return response.data.data
  },

  async create (data: any) {
    const response = await apiClient.post('/habilitations', data)
    return response.data.data
  },

  async update (id: string, data: any) {
    const response = await apiClient.put(`/habilitations/${id}`, data)
    return response.data.data
  },

  async delete (id: string) {
    await apiClient.delete(`/habilitations/${id}`)
  },

  async getExpiring (days = 30) {
    const response = await apiClient.get(`/habilitations/expiring?days=${days}`)
    return response.data.data
  },

  async renew (id: string, data: any) {
    const response = await apiClient.post(`/habilitations/${id}/renew`, data)
    return response.data.data
  },

  async export (format = 'excel') {
    const response = await apiClient.get(`/habilitations/export?format=${format}`, {
      responseType: 'blob',
    })
    return response.data
  },
}
