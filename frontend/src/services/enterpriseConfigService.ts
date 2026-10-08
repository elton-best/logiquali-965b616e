import type {
  EnterpriseBranding,
  EnterpriseContact,
  EnterpriseDocumentConfig,
  EnterpriseLegalInfo,
} from '@/types/enterprise-config'
import apiClient from '@/api/client'

const BASE_URL = '/enterprises'

export const enterpriseConfigService = {
  // Récupérer la configuration complète
  async getConfiguration (enterpriseId: number) {
    const response = await apiClient.get(`${BASE_URL}/${enterpriseId}/configuration`) as any
    return response?.data ?? response
  },

  // Mettre à jour le branding
  async updateBranding (enterpriseId: number, branding: EnterpriseBranding) {
    const response = await apiClient.put(
      `${BASE_URL}/${enterpriseId}/branding`,
      branding,
    ) as any
    return response?.data ?? response
  },

  // Mettre à jour les informations légales
  async updateLegalInfo (enterpriseId: number, legalInfo: EnterpriseLegalInfo) {
    const response = await apiClient.put(
      `${BASE_URL}/${enterpriseId}/legal-info`,
      legalInfo,
    ) as any
    return response?.data ?? response
  },

  // Mettre à jour les coordonnées
  async updateContact (enterpriseId: number, contact: EnterpriseContact) {
    const response = await apiClient.put(
      `${BASE_URL}/${enterpriseId}/contact`,
      contact,
    ) as any
    return response?.data ?? response
  },

  // Mettre à jour la configuration des documents
  async updateDocumentConfig (enterpriseId: number, config: EnterpriseDocumentConfig) {
    const response = await apiClient.put(
      `${BASE_URL}/${enterpriseId}/document-config`,
      config,
    ) as any
    return response?.data ?? response
  },
}
