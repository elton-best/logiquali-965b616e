import type { Certification, EnterpriseCertification } from '@/types/enterprise-config'
import apiClient from '@/api/client'

const BASE_URL = '/enterprises'

export const certificationService = {
  // Récupérer le catalogue des certifications
  async getCatalog () {
    const { data } = await apiClient.get(`${BASE_URL}/certifications/catalog`)
    return data as Certification[]
  },

  // Récupérer les certifications d'une entreprise
  async getEnterpriseCertifications (enterpriseId: number) {
    const { data } = await apiClient.get(`${BASE_URL}/${enterpriseId}/certifications`)
    return data as EnterpriseCertification[]
  },

  // Ajouter une certification
  async addCertification (enterpriseId: number, certification: Partial<EnterpriseCertification>) {
    const { data } = await apiClient.post(
      `${BASE_URL}/${enterpriseId}/certifications`,
      certification,
    )
    return data
  },

  // Mettre à jour une certification
  async updateCertification (
    enterpriseId: number,
    certificationId: number,
    certification: Partial<EnterpriseCertification>,
  ) {
    const { data } = await apiClient.put(
      `${BASE_URL}/${enterpriseId}/certifications/${certificationId}`,
      certification,
    )
    return data
  },

  // Supprimer une certification
  async deleteCertification (enterpriseId: number, certificationId: number) {
    await apiClient.delete(`${BASE_URL}/${enterpriseId}/certifications/${certificationId}`)
  },
}
