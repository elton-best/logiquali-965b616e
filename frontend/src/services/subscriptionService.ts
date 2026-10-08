import api from '@/api/client'

export interface Offer {
  id: number
  name: string
  description: string
  price: string | number
  duration_months: number
  is_active: boolean
}

export interface Subscription {
  id: number
  offer_id: number
  site_id: number
  start_date: string
  expiration_date: string
  is_active: boolean
  is_trial: boolean
  offer?: Offer
}

export interface SubscriptionStatus {
  hasActiveSubscription: boolean
  hasExpiredSubscription: boolean
  trialUsed: boolean
  currentSubscription?: Subscription
  can_access_dashboard?: boolean
  active_norms?: Array<{ id: number, code: string, name: string }>
  expired_norms?: Array<{ id: number, code: string, name: string, last_expiration_date?: string | null }>
  blocking_reasons?: string[]
}

export interface SubscriptionPayload {
  site_id: number
  offer_ids: number[]
  payment_method?: 'mtn_momo' | 'moov_money' | 'coris_money' | 'yas_money'
  phone_number?: string
  is_trial?: boolean
}

const STATUS_CACHE_TTL_MS = 10_000
const statusCache = new Map<number, { value: SubscriptionStatus, expiresAt: number }>()
const statusInflight = new Map<number, Promise<SubscriptionStatus>>()

function invalidateStatusCache (siteId?: number): void {
  if (typeof siteId === 'number' && Number.isFinite(siteId)) {
    statusCache.delete(siteId)
    statusInflight.delete(siteId)
    return
  }

  statusCache.clear()
  statusInflight.clear()
}

export const subscriptionService = {
  async getEnterpriseSubscriptions (): Promise<{ data: Subscription[] }> {
    const response = await api.get('/enterprise-subscriptions')
    return response.data as any
  },

  async getOffers (): Promise<Offer[]> {
    const response = await api.get('/public/offers')
    const payload = response?.data

    // Support both response formats:
    // 1) raw array: Offer[]
    // 2) wrapped payload: { success: boolean, data: Offer[] }
    if (Array.isArray(payload)) {
      return payload as Offer[]
    }

    if (payload && Array.isArray((payload as any).data)) {
      return (payload as any).data as Offer[]
    }

    return []
  },

  async checkStatus (siteId: number, force = false): Promise<SubscriptionStatus> {
    const cacheKey = Number(siteId)
    if (!force) {
      const cached = statusCache.get(cacheKey)
      if (cached && cached.expiresAt > Date.now()) {
        return cached.value
      }
    }

    const inflight = statusInflight.get(cacheKey)
    if (inflight) {
      return inflight
    }

    const request = api.get('/subscription/status', {
      params: { site_id: siteId },
    }).then(response => {
      const value = response.data as any
      statusCache.set(cacheKey, {
        value,
        expiresAt: Date.now() + STATUS_CACHE_TTL_MS,
      })
      return value
    }).finally(() => {
      statusInflight.delete(cacheKey)
    })

    statusInflight.set(cacheKey, request)
    return request
  },

  async subscribe (payload: SubscriptionPayload) {
    const response = await api.post('/subscription/subscribe', payload)
    invalidateStatusCache(payload.site_id)
    return response.data as any
  },

  async simulatePayment (data: any) {
    const response = await api.post('/payments/simulate', data)
    return response.data as any
  },

  async requestPayment (data: {
    subscription_id: number
    payment_method: 'mtn_momo' | 'moov_money' | 'coris_money' | 'yas_money'
    phone_number: string
    indicatif?: string
  }) {
    const response = await api.post('/payments/request', data)
    return response.data as any
  },

  async checkSubscriptionPayment (data: {
    subscription_id: number
    reference_id: string
  }) {
    const response = await api.post('/payments/check-subscription', data)
    invalidateStatusCache()
    return response.data as any
  },

  async renewSubscription (subscriptionId: number) {
    const response = await api.post(`/enterprise-subscriptions/${subscriptionId}/renew`, {
      duration_months: 1,
    })
    invalidateStatusCache()
    return response.data as any
  },
}
