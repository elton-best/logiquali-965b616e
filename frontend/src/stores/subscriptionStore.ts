import type {
  ModuleWithPermissions,
  SubscriptionStatus,
  TrialAlert,
} from '../types/subscription.types'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { modulesApi, subscriptionApi } from '@/api/subscription'

export const useSubscriptionStore = defineStore('subscription', () => {
  const currentSubscription = ref<SubscriptionStatus | null>(null)
  const accessibleModules = ref<ModuleWithPermissions[]>([])
  const alerts = ref<TrialAlert[]>([])
  const loading = ref(false)
  const lastSubscriptionFetchedAt = ref<number | null>(null)
  let fetchSubscriptionPromise: Promise<void> | null = null

  const isInTrial = computed(
    () => currentSubscription.value?.is_trial ?? false,
  )
  const daysRemaining = computed(
    () => currentSubscription.value?.days_remaining ?? 0,
  )
  const hasActiveSubscription = computed(
    () => !!currentSubscription.value && !currentSubscription.value.is_expired,
  )
  const isExpired = computed(
    () => currentSubscription.value?.is_expired ?? false,
  )

  const fetchSubscription = async () => {
    const now = Date.now()
    if (
      lastSubscriptionFetchedAt.value
      && now - lastSubscriptionFetchedAt.value < 10_000
    ) {
      return
    }

    if (fetchSubscriptionPromise) {
      return fetchSubscriptionPromise
    }

    loading.value = true
    fetchSubscriptionPromise = (async () => {
      try {
        const response = await subscriptionApi.getCurrent()
        currentSubscription.value = response.data
        lastSubscriptionFetchedAt.value = Date.now()
      } catch {
        currentSubscription.value = null
      } finally {
        loading.value = false
        fetchSubscriptionPromise = null
      }
    })()

    return fetchSubscriptionPromise
  }

  const fetchAccessibleModules = async () => {
    try {
      const response = await modulesApi.getAccessible()
      accessibleModules.value = response.data.modules
    } catch {
      accessibleModules.value = []
    }
  }

  const fetchAlerts = async () => {
    try {
      const response = await subscriptionApi.getAlerts()
      alerts.value = response.data.alerts
    } catch {
      alerts.value = []
    }
  }

  const canAccessModule = (
    moduleIdentifier: string,
    action: 'read' | 'create' | 'update' | 'delete' | 'validate' = 'read',
  ) => {
    const module = accessibleModules.value.find(
      m => m.identifier === moduleIdentifier,
    )
    return module?.permissions[action] ?? false
  }

  return {
    currentSubscription,
    accessibleModules,
    alerts,
    loading,
    isInTrial,
    daysRemaining,
    hasActiveSubscription,
    isExpired,
    fetchSubscription,
    fetchAccessibleModules,
    fetchAlerts,
    canAccessModule,
  }
})
