import { apiClient } from '../api/client'

export const competenceService = {
  async getCompetencesRequises () {
    const response = await apiClient.get('/competences-requises')
    return response.data.data
  },

  async getCompetencesAcquises (userId?: string) {
    const url = userId ? `/competences-acquises?user_id=${userId}` : '/competences-acquises'
    const response = await apiClient.get(url)
    return response.data.data
  },

  async getMatrix (jobDescriptionId?: string) {
    const url = jobDescriptionId
      ? `/competence-matrix?job_description_id=${jobDescriptionId}`
      : '/competence-matrix'
    const response = await apiClient.get(url)
    return response.data.data
  },

  async getGapAnalysis (userId: string) {
    const response = await apiClient.get(`/competence-matrix/gap-analysis/${userId}`)
    return response.data.data
  },

  async generateTrainingPlan (userId: string) {
    const response = await apiClient.post(`/competence-matrix/training-plan/${userId}`)
    return response.data.data
  },
}
