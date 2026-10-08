import api from '@/api/client'

export const dashboardService = {
  async getGlobal (filters?: any) {
    const { data } = await api.get('/improvement/dashboard/global', { params: filters })
    return data
  },

  async getTrends (filters?: any) {
    const { data } = await api.get('/improvement/dashboard/trends', { params: filters })
    return data
  },

  async getAxesComparison (filters?: any) {
    const { data } = await api.get('/improvement/dashboard/axes-comparison', { params: filters })
    return data
  },

  async getRiskMatrix (filters?: any) {
    const { data } = await api.get('/risks/matrix', { params: filters })
    return data
  },

  async getAlerts (filters?: any) {
    const { data } = await api.get('/improvement/dashboard/alerts', { params: filters })
    return data
  },

  async exportPdf (filters?: any) {
    const { data } = await api.get('/improvement/dashboard/export-pdf', { params: filters })
    return data
  },
}
