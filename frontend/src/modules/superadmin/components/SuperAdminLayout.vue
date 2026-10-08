<template>
  <v-app>
    <!-- Sidebar -->
    <v-navigation-drawer
      v-model="drawer"
      elevation="1"
      permanent
      :rail="rail"
      :width="280"
    >
      <!-- Logo Section -->
      <div
        class="pa-6 border-b cursor-pointer"
        role="link"
        tabindex="0"
        @click="_router.push('/superadmin/dashboard')"
        @keydown.enter.prevent="_router.push('/superadmin/dashboard')"
        @keydown.space.prevent="_router.push('/superadmin/dashboard')"
      >
        <div class="d-flex align-center">
          <div v-show="!rail">
            <AppLogo show-text subtitle="Super Admin" variant="mark" />
          </div>
          <div v-show="rail">
            <AppLogo variant="mark" />
          </div>
        </div>
      </div>

      <!-- Pending KYC Alert -->
      <div v-show="!rail" class="pa-4">
        <v-card v-if="pendingKYC > 0" class="card-warning-custom" variant="flat">
          <v-card-text class="pa-3">
            <div class="d-flex align-center">
              <v-icon class="mr-2" color="warning" size="20">mdi-alert-circle</v-icon>
              <span class="text-caption font-weight-medium">
                <strong>{{ pendingKYC }}</strong> {{ pendingKYC > 1 ? 'inscriptions en attente' : 'inscription en attente' }}
              </span>
            </div>
          </v-card-text>
        </v-card>
      </div>

      <!-- Navigation Menu -->
      <v-list class="px-4 sidebar-nav" density="compact" nav>
        <template v-for="section in menuSections" :key="section.id">
          <v-list-subheader
            v-if="!rail && section.label"
            class="text-uppercase text-caption font-weight-bold menu-section"
          >
            {{ section.label }}
          </v-list-subheader>

          <v-list-item
            v-for="item in section.items"
            :key="item.id"
            :active="currentPage === item.id"
            :aria-label="item.label"
            class="mb-1 menu-item"
            color="primary"
            role="menuitem"
            rounded="lg"
            :to="item.route"
            :value="item.id"
          >
            <template #prepend>
              <v-icon>{{ item.icon }}</v-icon>
            </template>
            <v-list-item-title v-show="!rail">{{ item.label }}</v-list-item-title>
            <template v-if="item.badge && !rail" #append>
              <v-badge color="error" :content="item.badge" inline />
            </template>
          </v-list-item>

          <v-divider v-if="section.divider" class="my-3" />
        </template>
      </v-list>

    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar class="border-b" elevation="0" height="64">
      <v-container class="d-flex align-center px-6" fluid>
        <!-- Toggle rail -->
        <v-btn
          aria-label="Toggle menu"
          class="mr-4 ripple-btn"
          icon
          variant="text"
          @click="rail = !rail"
        >
          <v-icon>mdi-menu</v-icon>
        </v-btn>

        <!-- Breadcrumbs -->
        <v-breadcrumbs
          v-if="breadcrumbs.length > 0"
          class="pa-0"
          density="compact"
          :items="breadcrumbs"
        >
          <template #divider>
            <v-icon>mdi-chevron-right</v-icon>
          </template>
        </v-breadcrumbs>

        <v-spacer />

        <!-- Search -->
        <v-menu
          v-model="searchOpen"
          :close-on-content-click="false"
          location="bottom"
          offset="8"
        >
          <template #activator="{ props }">
            <v-text-field
              v-model="searchQuery"
              v-bind="props"
              aria-label="Rechercher"
              class="mr-4"
              clearable
              density="compact"
              hide-details
              :loading="isSearching"
              placeholder="Rechercher une entreprise, un abonnement..."
              prepend-inner-icon="mdi-magnify"
              single-line
              style="max-width: 400px;"
              variant="outlined"
              @update:model-value="debouncedSearch"
            />
          </template>
          <v-card max-width="560" min-width="380">
            <v-card-text>
              <div v-if="Object.keys(searchResults).length === 0" class="text-caption text-medium-emphasis">
                Aucun resultat
              </div>
              <div v-else>
                <div v-for="(items, key) in searchResults" :key="key" class="mb-3">
                  <div class="text-caption text-medium-emphasis mb-2">{{ key }}</div>
                  <v-list class="bg-transparent" density="compact">
                    <v-list-item
                      v-for="item in items"
                      :key="`${item.type}-${item.id}`"
                      :to="item.route"
                      @click="searchOpen = false"
                    >
                      <v-list-item-title class="font-weight-medium">{{ item.title }}</v-list-item-title>
                      <v-list-item-subtitle>{{ item.subtitle }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-menu>

        <!-- Notifications -->
        <NotificationCenter class="mr-2" />

        <LanguageSwitcher class="mr-2" />

        <!-- Dark mode toggle -->
        <v-btn class="mr-2" icon variant="text" @click="toggleDarkMode">
          <v-icon>{{ darkMode ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent' }}</v-icon>
        </v-btn>

        <!-- User menu -->
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-avatar class="cursor-pointer" color="primary" size="40" v-bind="props">
              <span class="text-body-2">{{ userInitials }}</span>
            </v-avatar>
          </template>
          <v-card min-width="250">
            <v-list>
              <v-list-item class="mb-2">
                <template #prepend>
                  <v-avatar color="primary" size="48">
                    <span class="text-h6">{{ userInitials }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold">{{ userName }}</v-list-item-title>
                <v-list-item-subtitle>{{ userEmail }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
            <v-divider />
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-account" to="/superadmin/profile">
                Mon profil
              </v-list-item>
              <v-list-item prepend-icon="mdi-cog" to="/superadmin/settings">
                Paramètres
              </v-list-item>
              <v-list-item prepend-icon="mdi-lock" @click="handleLockSession">
                Verrouiller ma session
              </v-list-item>
            </v-list>
            <v-divider />
            <v-list density="compact">
              <v-list-item class="text-error" prepend-icon="mdi-logout" @click="handleLogout">
                Déconnexion
              </v-list-item>
            </v-list>
          </v-card>
        </v-menu>
      </v-container>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="main-content">
      <div class="page-wrapper">
        <slot />
      </div>

      <!-- Scroll to top button -->
      <v-btn
        v-if="true"
        aria-label="Retour en haut"
        class="scroll-to-top"
        color="primary"
        elevation="4"
        icon
        size="large"
        @click="scrollToTop()"
      >
        <v-icon>mdi-chevron-up</v-icon>
      </v-btn>
    </v-main>

    <v-dialog v-model="mfaDialog" max-width="420" persistent>
      <v-card>
        <v-card-title class="text-h6">Vérification requise</v-card-title>
        <v-card-text>
          <p class="text-body-2 mb-4">
            {{ mfaMessage }}
          </p>
          <p v-if="mfaCountdownLabel" class="text-caption text-medium-emphasis mb-4">
            {{ mfaCountdownLabel }}
          </p>
          <v-text-field
            v-model="mfaCode"
            autocomplete="one-time-code"
            label="Code de vérification"
            prepend-inner-icon="mdi-shield-key-outline"
            variant="outlined"
          />
          <div class="d-flex align-center justify-space-between mt-2">
            <v-btn
              :disabled="mfaResendLoading || mfaResendCooldown > 0"
              size="small"
              variant="text"
              @click="handleResendMfa"
            >
              {{ mfaResendLabel }}
            </v-btn>
          </div>
          <v-alert
            v-if="mfaError"
            class="mt-2"
            color="error"
            density="compact"
            type="error"
            variant="tonal"
          >
            {{ mfaError }}
          </v-alert>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer />
          <v-btn variant="text" @click="closeMfaDialog">Annuler</v-btn>
          <v-btn
            color="primary"
            :disabled="mfaExpired"
            :loading="mfaLoading"
            @click="submitMfa"
          >
            Vérifier
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, onUnmounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useTheme } from 'vuetify'
  import api, { clearPendingMfaRequest, retryPendingMfaRequest } from '@/api/client'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue'
  import { useAuth } from '@/composables/useAuth'
  import { useDebounce } from '@/modules/shared/composables/useDebounce'
  import { useSmoothScroll } from '@/modules/shared/composables/useSmoothScroll'
  import { useToast } from '@/modules/shared/composables/useToast'
  import authService from '@/services/authService'
  import superAdminService from '@/services/superAdminService'
  import { useLockScreenStore } from '@/stores/lockScreen'
  import NotificationCenter from './NotificationCenter.vue'

  const _props = defineProps<{
    currentPage: string
  }>()

  const _router = useRouter()
  const route = useRoute()
  const theme = useTheme()
  const toast = useToast()
  const { logout } = useAuth()
  const { scrollToTop } = useSmoothScroll()

  const lockScreenStore = useLockScreenStore()
  const drawer = ref(true)
  const rail = ref(false)
  const darkMode = ref(theme.global.current.value.dark)
  const searchQuery = ref('')
  const isSearching = ref(false)
  const searchResults = ref<Record<string, Array<{
    type: string
    id: number
    title: string
    subtitle?: string
    meta?: string
    route?: string
  }>>>({})
  const searchOpen = ref(false)
  const mfaDialog = ref(false)
  const mfaCode = ref('')
  const mfaToken = ref('')
  const defaultMfaMessage = 'Pour protéger votre compte, veuillez confirmer cette action avec un code envoyé par email. Cela ne prendra que quelques secondes.'
  const mfaMessage = ref(defaultMfaMessage)
  const mfaError = ref('')
  const mfaLoading = ref(false)
  const mfaExpiresAt = ref<number | null>(null)
  const mfaExpired = ref(false)
  const mfaCountdownLabel = ref('')
  const mfaResendCooldown = ref(0)
  const mfaResendLoading = ref(false)
  const mfaResendLabel = computed(() => {
    if (mfaResendCooldown.value > 0) {
      return `Renvoyer le code (${mfaResendCooldown.value}s)`
    }
    return 'Renvoyer le code'
  })
  let mfaCountdownTimer: number | null = null
  let mfaResendTimer: number | null = null

  // User data
  const user = computed(() => {
    try {
      return JSON.parse(localStorage.getItem('user') || '{}')
    } catch {
      return {}
    }
  })

  const userName = computed(() => user.value.name || 'Super Admin')
  const userEmail = computed(() => user.value.email || 'admin@BestQHSE.com')
  const userInitials = computed(() => {
    const names = userName.value.split(' ')
    return names.map((name: string) => name[0]).join('').toUpperCase().slice(0, 2)
  })

  // Menu items - Super Admin spécifique
  const menuSections = computed(() => [
    {
      id: 'overview',
      label: 'Vue generale',
      divider: true,
      items: [
        {
          id: 'dashboard',
          label: 'Tableau de bord',
          icon: 'mdi-view-dashboard-outline',
          route: '/superadmin/dashboard',
        },
      ],
    },
    {
      id: 'ops',
      label: 'Operations',
      divider: true,
      items: [
        {
          id: 'kyc',
          label: 'Validations KYC',
          icon: 'mdi-file-document-check-outline',
          route: '/superadmin/kyc',
          badge: pendingKYC.value > 0 ? String(pendingKYC.value) : undefined,
        },
        {
          id: 'enterprises',
          label: 'Entreprises',
          icon: 'mdi-office-building-outline',
          route: '/superadmin/enterprises',
        },
        {
          id: 'subscriptions',
          label: 'Abonnements',
          icon: 'mdi-credit-card-outline',
          route: '/superadmin/subscriptions',
        },
        {
          id: 'offers',
          label: 'Offres',
          icon: 'mdi-package-variant-closed',
          route: '/superadmin/offers',
        },
        {
          id: 'norms',
          label: 'Normes ISO',
          icon: 'mdi-certificate-outline',
          route: '/superadmin/norms',
        },
      ],
    },
    {
      id: 'admin',
      label: 'Administration',
      divider: false,
      items: [
        {
          id: 'users',
          label: 'Utilisateurs',
          icon: 'mdi-account-group-outline',
          route: '/superadmin/users',
        },
        {
          id: 'settings',
          label: 'Parametres',
          icon: 'mdi-cog-outline',
          route: '/superadmin/settings',
        },
      ],
    },
  ])

  // Breadcrumbs
  const breadcrumbs = computed(() => {
    const parts = new Set(route.path.split('/').filter(Boolean))
    const crumbs = []

    if (parts.has('superadmin')) {
      crumbs.push({ title: 'Super Admin', disabled: false, href: '/superadmin/dashboard' })

      if (parts.has('kyc')) {
        crumbs.push({ title: 'Validations KYC', disabled: true })
      } else if (parts.has('companies')) {
        crumbs.push({ title: 'Entreprises', disabled: true })
      } else if (parts.has('subscriptions')) {
        crumbs.push({ title: 'Abonnements', disabled: true })
      } else if (parts.has('norms')) {
        crumbs.push({ title: 'Normes ISO', disabled: true })
      } else if (parts.has('users')) {
        crumbs.push({ title: 'Utilisateurs', disabled: true })
      } else if (parts.has('offers')) {
        crumbs.push({ title: 'Offres', disabled: true })
      }
    }

    return crumbs
  })

  // Stats
  const pendingKYC = ref(0)
  const totalRevenue = ref(0)

  async function loadStats () {
    try {
      const stats = await superAdminService.getDashboardStats()
      pendingKYC.value = stats.kyc?.pending || 0
      totalRevenue.value = stats.revenue?.total || 0
    } catch (error) {
      console.error('Failed to load stats:', error)
    }
  }

  onMounted(() => {
    loadStats()
  })

  function toggleDarkMode () {
    darkMode.value = !darkMode.value
    theme.global.name.value = darkMode.value ? 'dark' : 'light'
    toast.info(darkMode.value ? 'Mode sombre activé' : 'Mode clair activé', undefined, 2000)
  }

  function handleLockSession () {
    lockScreenStore.lockSession(route.path, userEmail.value)
    _router.push('/lock-screen')
  }

  async function handleLogout () {
    await logout()
  }

  function updateMfaCountdown () {
    if (!mfaExpiresAt.value) {
      mfaCountdownLabel.value = ''
      mfaExpired.value = false
      return
    }

    const remainingMs = mfaExpiresAt.value - Date.now()
    if (remainingMs <= 0) {
      mfaCountdownLabel.value = 'Code expire. Demandez un nouveau.'
      mfaExpired.value = true
      if (mfaCountdownTimer) {
        window.clearInterval(mfaCountdownTimer)
        mfaCountdownTimer = null
      }
      return
    }

    const totalSeconds = Math.ceil(remainingMs / 1000)
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60
    mfaCountdownLabel.value = `Expire dans ${minutes.toString().padStart(2, '0')}:${seconds
      .toString()
      .padStart(2, '0')}`
    mfaExpired.value = false
  }

  function openMfaDialog (payload: any) {
    if (mfaDialog.value && !mfaExpired.value) {
      return
    }
    const expiresAtRaw = payload?.expires_at
    const expiresAt = expiresAtRaw ? Date.parse(String(expiresAtRaw)) : Number.NaN
    mfaExpiresAt.value = Number.isFinite(expiresAt) ? expiresAt : null
    mfaToken.value = String(payload?.token || '')
    mfaMessage.value = defaultMfaMessage
    mfaCode.value = ''
    mfaError.value = ''
    mfaExpired.value = false
    mfaResendCooldown.value = 0
    updateMfaCountdown()
    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
    }
    mfaCountdownTimer = window.setInterval(updateMfaCountdown, 1000)
    mfaDialog.value = true
  }

  function closeMfaDialog () {
    mfaDialog.value = false
    mfaToken.value = ''
    mfaCode.value = ''
    mfaError.value = ''
    clearPendingMfaRequest()
    mfaExpiresAt.value = null
    mfaExpired.value = false
    mfaCountdownLabel.value = ''
    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
      mfaCountdownTimer = null
    }
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
      mfaResendTimer = null
    }
    mfaResendCooldown.value = 0
  }

  function startResendCooldown (seconds = 30) {
    mfaResendCooldown.value = seconds
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
    }
    mfaResendTimer = window.setInterval(() => {
      mfaResendCooldown.value = Math.max(0, mfaResendCooldown.value - 1)
      if (mfaResendCooldown.value === 0 && mfaResendTimer) {
        window.clearInterval(mfaResendTimer)
        mfaResendTimer = null
      }
    }, 1000)
  }

  async function handleResendMfa () {
    if (mfaResendLoading.value || mfaResendCooldown.value > 0 || !mfaToken.value) {
      return
    }
    mfaResendLoading.value = true
    try {
      const response = await authService.resendMfa({ token: mfaToken.value })
      mfaToken.value = response.mfa_token
      mfaMessage.value = response.message || mfaMessage.value
      const expiresAt = response.mfa_expires_at
        ? Date.parse(String(response.mfa_expires_at))
        : Number.NaN
      mfaExpiresAt.value = Number.isFinite(expiresAt) ? expiresAt : null
      updateMfaCountdown()
      startResendCooldown(30)
    } catch (error: any) {
      mfaError.value = error?.response?.data?.message || 'Impossible de renvoyer le code.'
    } finally {
      mfaResendLoading.value = false
    }
  }

  async function submitMfa () {
    if (mfaLoading.value) return
    if (mfaExpired.value) {
      mfaError.value = 'Code expire. Demandez un nouveau.'
      return
    }
    if (!mfaToken.value || !mfaCode.value.trim()) {
      mfaError.value = 'Veuillez saisir le code.'
      return
    }

    mfaLoading.value = true
    mfaError.value = ''
    try {
      await api.post('/auth/mfa/verify', {
        token: mfaToken.value,
        code: mfaCode.value.trim(),
      })
      closeMfaDialog()
      toast.info('Vérification réussie. Veuillez relancer l\'action.')
      await retryPendingMfaRequest()
    } catch (error: any) {
      mfaError.value = error?.response?.data?.message || 'Code invalide ou expire.'
    } finally {
      mfaLoading.value = false
    }
  }

  // Debounced search
  async function performSearch () {
    if (!searchQuery.value.trim()) {
      isSearching.value = false
      searchResults.value = {}
      searchOpen.value = false
      return
    }
    isSearching.value = true
    try {
      searchResults.value = await superAdminService.search(searchQuery.value.trim())
      searchOpen.value = true
    } catch {
      searchResults.value = {}
      searchOpen.value = false
    } finally {
      isSearching.value = false
    }
  }

  const debouncedSearch = useDebounce(performSearch, 500)

  // Smooth scroll on mount
  function mfaListener (event: Event) {
    const detail = (event as CustomEvent).detail || {}
    openMfaDialog(detail)
  }

  onMounted(() => {
    scrollToTop(0)
    if (typeof window !== 'undefined') {
      window.addEventListener('mfa-required', mfaListener)
    }
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('mfa-required', mfaListener)
    }
    if (mfaCountdownTimer) {
      window.clearInterval(mfaCountdownTimer)
      mfaCountdownTimer = null
    }
    if (mfaResendTimer) {
      window.clearInterval(mfaResendTimer)
      mfaResendTimer = null
    }
  })
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.border-t {
  border-top: 1px solid rgba(226, 232, 240, 0.6);
}

.cursor-pointer {
  cursor: pointer;
}

/* Alignement visuel sur ClientA */
.main-content {
  background-color: rgb(var(--v-theme-surface));
}

/* Warning card ultra-subtile */
.card-warning-custom {
  background: linear-gradient(135deg,
    rgba(249, 115, 22, 0.03) 0%,
    rgba(251, 146, 60, 0.02) 100%) !important;
  border: 1px solid rgba(249, 115, 22, 0.12) !important;
  border-radius: var(--radius-lg) !important;
}

/* Navigation drawer & app bar alignes sur ClientA */
:deep(.v-navigation-drawer) {
  background-color: rgb(var(--v-theme-surface)) !important;
}

:deep(.v-app-bar) {
  background-color: rgb(var(--v-theme-surface)) !important;
}

/* Page wrapper avec animation douce */
.page-wrapper {
  animation: fadeInContent 0.5s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

@keyframes fadeInContent {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Menu items - subtils et élégants */
/* Sidebar density & item behavior aligned to ClientA */
.sidebar-nav :deep(.v-list-item) {
  min-height: auto;
  padding-top: 8px;
  padding-bottom: 8px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: visible;
}

.sidebar-nav :deep(.v-list-item-title) {
  white-space: normal !important;
  line-height: 1.4 !important;
  word-wrap: break-word !important;
  overflow-wrap: break-word !important;
}

.sidebar-nav :deep(.v-list-item::before) {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  width: 3px;
  height: 100%;
  background: rgb(var(--v-theme-primary));
  transform: scaleY(0);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-nav :deep(.v-list-item--active::before) {
  transform: scaleY(1);
}

.menu-section {
  letter-spacing: 0.08em;
  opacity: 0.85;
}

.menu-item {
  background: rgba(var(--v-theme-primary), 0.04);
  border: 1px solid rgba(var(--v-theme-primary), 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  min-height: auto !important;
  padding-top: 10px !important;
  padding-bottom: 10px !important;
}

.menu-item::after {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(var(--v-theme-primary), 0.1),
    transparent
  );
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.menu-item:hover {
  background: rgba(var(--v-theme-primary), 0.08);
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.15);
}

.menu-item:hover::after {
  opacity: 1;
}

.menu-item:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.1);
}

/* Standard cards & tables (align with ClientA) */
:deep(.sa-card) {
  border: 1px solid rgb(var(--v-theme-outline-variant));
  border-radius: 16px;
}

:deep(.sa-card .v-card-text),
:deep(.sa-card .v-card-title) {
  padding: 24px;
}

:deep(.sa-filters .v-btn) {
  border-radius: 12px;
}

:deep(.sa-table thead th) {
  font-weight: 600;
  color: rgb(var(--v-theme-on-surface));
}

:deep(.sa-table tbody tr) {
  transition: background 0.2s ease;
}

:deep(.sa-table tbody tr:hover) {
  background: rgba(var(--v-theme-primary), 0.04);
}

:deep(.sa-empty .v-icon) {
  opacity: 0.8;
}

/* Ripple button minimal */
.ripple-btn {
  transition: all 0.2s ease;
  border-radius: var(--radius-md);
}

.ripple-btn:hover {
  background: rgba(68, 113, 196, 0.04);
}

/* Scroll to top - floating premium */
.scroll-to-top {
  position: fixed;
  bottom: var(--space-6);
  right: var(--space-6);
  z-index: 1000;
  background: var(--surface-elevated) !important;
  box-shadow: var(--shadow-lg) !important;
  border: 1px solid rgba(226, 232, 240, 0.6) !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-to-top:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-xl) !important;
  background: var(--gradient-primary) !important;
}

.scroll-to-top:hover :deep(.v-icon) {
  color: white !important;
}

.scroll-to-top:focus-visible {
  outline: 2px solid var(--primary-700);
  outline-offset: 2px;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}

/* Désactiver animations si préférence */
@media (prefers-reduced-motion: reduce) {
  .page-wrapper,
  .menu-item,
  .scroll-to-top,
  .ripple-btn {
    animation: none !important;
    transition: none !important;
  }

  .menu-item:hover,
  .scroll-to-top:hover {
    transform: none !important;
  }
}
</style>
