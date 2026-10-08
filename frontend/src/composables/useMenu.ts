/**
 * Composable for managing menu items based on user permissions and subscription
 */

import type { MenuItem } from '@/layouts/AppLayout.vue'
import { computed } from 'vue'
import { getMenuItemsBySubscription } from '@/config/menuItems'
import { useAuthStore } from '@/stores/auth'
import { useSubscriptionStore } from '@/stores/subscriptionStore'

export function useMenu () {
  const authStore = useAuthStore()
  const subscriptionStore = useSubscriptionStore()

  /**
   * Get menu items based on user type and subscription status
   */
  const menuItems = computed<MenuItem[]>(() => {
    const user = authStore.user

    if (!user) {
      return []
    }

    // Convert user_type to expected format used by menu config
    let userType: 'clienta' | 'super_admin' | 'client_b'

    switch (user.user_type) {
      case 'super_admin': {
        userType = 'super_admin'

        break
      }
      case 'company': {
        userType = 'clienta'

        break
      }
      case 'clientb': {
        userType = 'client_b'

        break
      }
      default: {
        userType = 'clienta' // default fallback
      }
    }

    // Check if user has active subscription
    // Super admins always have full access
    const hasActiveSubscription = userType === 'super_admin' || subscriptionStore.hasActiveSubscription

    return getMenuItemsBySubscription(userType, hasActiveSubscription)
  })

  return {
    menuItems,
  }
}
