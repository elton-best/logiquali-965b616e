import type { SubscriptionStatus, TrialAlert } from '../types/subscription.types'
import { ref } from 'vue'
import { subscriptionApi } from '@/api/subscription'

export function useSubscription () {
  const subscription = ref<SubscriptionStatus | null>(null)
  const alerts = ref<TrialAlert[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchCurrentSubscription = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await subscriptionApi.getCurrent()
      subscription.value = response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la récupération de la souscription'
      subscription.value = null
    } finally {
      loading.value = false
    }
  }

  const fetchAlerts = async () => {
    try {
      const response = await subscriptionApi.getAlerts()
      alerts.value = response.data.alerts
    } catch (error_) {
      console.error('Erreur lors de la récupération des alertes', error_)
    }
  }

  const subscribe = async (offerId: number, siteId: number, isTrial = false) => {
    loading.value = true
    error.value = null
    try {
      const response = await subscriptionApi.subscribe({ offer_id: offerId, site_id: siteId, is_trial: isTrial })
      await fetchCurrentSubscription()
      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur lors de la souscription'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getDaysRemaining = () => subscription.value?.days_remaining ?? 0
  const isInTrial = () => subscription.value?.is_trial ?? false
  const isExpired = () => subscription.value?.is_expired ?? false
  const hasActiveSubscription = () => !!subscription.value && !subscription.value.is_expired

  return {
    subscription,
    alerts,
    loading,
    error,
    fetchCurrentSubscription,
    fetchAlerts,
    subscribe,
    getDaysRemaining,
    isInTrial,
    isExpired,
    hasActiveSubscription,
  }
}
