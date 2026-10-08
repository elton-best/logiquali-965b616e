import type { Offer } from '@/types/api'
import api from '@/api/client'

class PublicService {
  /**
   * Get active offers for landing page (no auth required)
   */
  async getPublicOffers (): Promise<Offer[]> {
    const { data } = await api.get('/public/offers')

    // Support both response formats:
    // 1) raw array: Offer[]
    // 2) wrapped payload: { success: boolean, data: Offer[] }
    if (Array.isArray(data)) {
      return data as Offer[]
    }

    if (data && Array.isArray((data as any).data)) {
      return (data as any).data as Offer[]
    }

    return []
  }
}

export default new PublicService()
