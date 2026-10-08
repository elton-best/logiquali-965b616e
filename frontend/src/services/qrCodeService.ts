import type { QRCodeStats, QRCodeVerification } from '@/types/enterprise-config'
import apiClient from '@/api/client'

const BASE_URL = '/qr-codes'

export const qrCodeService = {
  // Vérifier un QR code (public)
  async verify (hash: string) {
    const { data } = await apiClient.get(`/verify/${hash}`)
    return data as QRCodeVerification
  },

  // Récupérer les statistiques de scan (authentifié)
  async getStats (documentId: number, documentType: string) {
    const { data } = await apiClient.get(`${BASE_URL}/stats`, {
      params: { document_id: documentId, document_type: documentType },
    })
    return data as QRCodeStats
  },
}
