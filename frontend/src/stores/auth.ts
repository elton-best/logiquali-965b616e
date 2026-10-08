import type { LoginRequest, RegisterClientRequest, User } from '@/types/api'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import authService from '@/services/authService'
import { clearBrowserSessionData } from '@/utils/sessionSecurity'

export const useAuthStore = defineStore('auth', () => {
  const USE_HTTPONLY_COOKIE_AUTH = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false') === 'true'

  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const loading = ref(false)
  const currentSiteId = ref<number | null>(null)
  const availableSites = ref<any[]>([])

  // Getters
  const isAuthenticated = computed(() =>
    !!user.value && (USE_HTTPONLY_COOKIE_AUTH || !!token.value),
  )
  const userType = computed(() => user.value?.user_type)
  const isAdmin = computed(() => user.value?.user_type === 'super_admin')
  const isClientA = computed(() => user.value?.user_type === 'company')
  const isClientB = computed(() => user.value?.user_type === 'clientb')
  const userName = computed(
    () => user.value?.username || user.value?.name || '',
  )
  const userEmail = computed(() => user.value?.email || '')

  // Site getters
  const currentSite = computed(() => {
    if (!currentSiteId.value) {
      return null
    }
    return (
      availableSites.value.find(s => s.id === currentSiteId.value) || null
    )
  })

  const hasMultipleSites = computed(() => availableSites.value.length > 1)

  // Enterprise getters
  const enterpriseName = computed(
    () => user.value?.enterprise?.name || 'Mon Entreprise',
  )
  const enterpriseLogoUrl = computed(() => {
    const enterprise = (user.value as any)?.enterprise
    if (!enterprise) {
      return ''
    }

    const branding = enterprise.branding || {}

    const explicitUrl = String(
      enterprise.logo_url || enterprise.logo || branding.logo_url || '',
    ).trim()
    if (explicitUrl) {
      return explicitUrl
    }

    const rawLogoPath = String(
      enterprise.logo_path || branding.logo_path || '',
    ).trim()
    if (!rawLogoPath) {
      return ''
    }

    const normalizedPath = rawLogoPath
      .replace(/^https?:\/\/[^/]+\/storage\//i, '')
      .replace(/^\/?storage\//i, '')
      .replace(/^\/+/, '')
    const apiBase = String(
      import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
    ).replace(/\/api\/v1\/?$/, '')

    return `${apiBase}/storage/${normalizedPath}`
  })
  const enterpriseSubscription = computed(() => {
    const enterprise = (user.value as any)?.enterprise
    if (!enterprise) {
      return null
    }

    const candidates = [
      ...(Array.isArray(enterprise.subscriptions)
        ? enterprise.subscriptions
        : []),
      ...(Array.isArray(enterprise.enterprise_subscriptions)
        ? enterprise.enterprise_subscriptions
        : []),
    ]

    return candidates[0] || null
  })

  const subscriptionPlan = computed(
    () =>
      (enterpriseSubscription.value as any)?.plan?.name
      || (enterpriseSubscription.value as any)?.offer?.name
      || 'Aucun',
  )
  const subscriptionExpiry = computed(
    () =>
      (enterpriseSubscription.value as any)?.expires_at
      || (enterpriseSubscription.value as any)?.expiration_date
      || null,
  )
  const subscriptionStatus = computed(() => {
    const expiry = subscriptionExpiry.value
    if (!expiry) {
      return 'Aucun abonnement'
    }
    const expiryDate = new Date(expiry)
    const now = new Date()
    const daysLeft = Math.floor(
      (expiryDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24),
    )
    if (daysLeft < 0) {
      return 'Abonnement expiré'
    }
    if (daysLeft < 30) {
      return `Expire dans ${daysLeft} jours`
    }
    return 'Abonnement actif'
  })

  // Actions
  function setAuth (userData: User, authToken?: string | null) {
    user.value = userData
    token.value = authToken || null

    // Extraire les sites disponibles
    if (userData.site) {
      availableSites.value = [userData.site]
      currentSiteId.value = userData.site.id
    } else if (userData.enterprise?.sites) {
      availableSites.value = userData.enterprise.sites
      // Sélectionner le siège social par défaut, sinon le premier site
      const headquarter = availableSites.value.find(s => s.is_headquarter)
      currentSiteId.value
        = headquarter?.id || availableSites.value[0]?.id || null
    }

    // Persister dans localStorage
    localStorage.setItem('user', JSON.stringify(userData))
    if (!USE_HTTPONLY_COOKIE_AUTH && authToken) {
      localStorage.setItem('access_token', authToken)
    }
    if (currentSiteId.value) {
      localStorage.setItem('current_site_id', currentSiteId.value.toString())
    }
  }

  function clearAuth () {
    user.value = null
    token.value = null
    currentSiteId.value = null
    availableSites.value = []
    clearBrowserSessionData()
  }

  function updateUser (userData: Partial<User>) {
    if (user.value) {
      user.value = { ...user.value, ...userData }
      localStorage.setItem('user', JSON.stringify(user.value))
    }
  }

  function setAvailableSites (sites: any[]) {
    availableSites.value = Array.isArray(sites) ? sites : []
  }

  function setCurrentSite (siteId: number) {
    if (!Number.isFinite(siteId) || siteId <= 0) {
      return
    }

    currentSiteId.value = siteId
    localStorage.setItem('current_site_id', siteId.toString())
    localStorage.removeItem('cached_sub_modules')
    localStorage.removeItem('cached_sub_modules_timestamp')
    localStorage.removeItem('cached_sections')
    localStorage.removeItem('cached_sections_timestamp')
    if (typeof window !== 'undefined') {
      window.dispatchEvent(
        new CustomEvent('company-site-changed', { detail: { siteId } }),
      )
      window.dispatchEvent(
        new CustomEvent('company-access-changed', {
          detail: { reason: 'site-changed', siteId },
        }),
      )
    }
  }

  function clearCurrentSite () {
    currentSiteId.value = null
    localStorage.removeItem('current_site_id')
    if (typeof window !== 'undefined') {
      window.dispatchEvent(
        new CustomEvent('company-site-changed', { detail: { siteId: null } }),
      )
      window.dispatchEvent(
        new CustomEvent('company-access-changed', {
          detail: { reason: 'site-cleared', siteId: null },
        }),
      )
    }
  }

  function initAuth () {
    // Restaurer depuis localStorage au démarrage
    const storedToken = localStorage.getItem('access_token')
    const storedUser = localStorage.getItem('user')
    const storedSiteId = localStorage.getItem('current_site_id')

    if (storedUser && (USE_HTTPONLY_COOKIE_AUTH || storedToken)) {
      try {
        token.value = USE_HTTPONLY_COOKIE_AUTH ? null : storedToken
        user.value = JSON.parse(storedUser)

        // Restaurer les sites
        if (user.value?.site) {
          availableSites.value = [user.value.site]
        } else if (user.value?.enterprise?.sites) {
          availableSites.value = user.value.enterprise.sites
        }

        // Restaurer le site actuel
        if (storedSiteId) {
          const siteId = Number.parseInt(storedSiteId)
          if (availableSites.value.some(s => s.id === siteId)) {
            currentSiteId.value = siteId
          }
        }

        // Si pas de site actuel mais des sites disponibles
        if (!currentSiteId.value && availableSites.value.length > 0) {
          const headquarter = availableSites.value.find(
            s => s.is_headquarter,
          )
          currentSiteId.value = headquarter?.id || availableSites.value[0].id
        }
      } catch (error) {
        console.error('Erreur lors de la restauration de la session:', error)
        clearAuth()
      }
    }
  }

  function getDashboardRoute (): string {
    if (!user.value) {
      return '/auth/login'
    }

    switch (user.value.user_type) {
      case 'super_admin': {
        return '/superadmin/dashboard'
      }
      case 'company': {
        return '/company/dashboard'
      }
      case 'clientb': {
        return '/clientb/dashboard'
      }
      default: {
        return '/auth/login'
      }
    }
  }

  async function login (credentials: LoginRequest) {
    const response = await authService.login(credentials)
    if (response?.user) {
      setAuth(response.user, response.token ?? null)
    }
    return response
  }

  async function register (payload: RegisterClientRequest) {
    return authService.registerClient(payload)
  }

  function navigateTo (target: string) {
    const routes: Record<string, string> = {
      landing: '/landing',
      login: '/auth/login',
      signup: '/auth/signup',
    }
    const destination = routes[target] || target
    if (typeof window !== 'undefined') {
      window.location.href = destination
    }
  }

  return {
    // State
    user,
    token,
    loading,
    currentSiteId,
    availableSites,

    // Getters
    isAuthenticated,
    userType,
    isAdmin,
    isClientA,
    isClientB,
    userName,
    userEmail,
    currentSite,
    hasMultipleSites,
    enterpriseName,
    enterpriseLogoUrl,
    subscriptionPlan,
    subscriptionExpiry,
    subscriptionStatus,

    // Actions
    setAuth,
    clearAuth,
    updateUser,
    setAvailableSites,
    setCurrentSite,
    clearCurrentSite,
    initAuth,
    getDashboardRoute,
    login,
    register,
    navigateTo,
  }
})
