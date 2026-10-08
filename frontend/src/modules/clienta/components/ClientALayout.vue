<template>
  <v-app>
    <!-- Sidebar -->
    <ClientASidebar
      :collapsed="sidebarCollapsed"
      :drawer="drawer"
      :is-mobile="isMobile"
      @update:drawer="drawer = $event"
    >
      <!-- Logo Section -->
      <SidebarBrand
        :collapsed="sidebarCollapsed"
        :company-logo-url="companyLogoUrl"
        :company-name="companyName"
        :is-mobile="isMobile"
        :user-title="sidebarUserTitle"
        @toggle-collapse="toggleSidebarCollapse"
      />

      <!-- Trial Alert -->
      <TrialAlertBanner
        :days-remaining="subscriptionStore.daysRemaining"
        :is-in-trial="subscriptionStore.isInTrial"
      />

      <v-alert
        v-if="subscriptionBlocked && !sidebarCollapsed"
        class="mx-4 mb-3"
        density="compact"
        prominent
        type="warning"
        variant="tonal"
      >
        <div class="text-body-2">{{ blockingMessage }}</div>
        <ul v-if="blockingReasons.length > 0" class="pl-4 mt-2">
          <li
            v-for="reason in blockingReasons"
            :key="reason"
            class="text-caption mb-1"
          >
            {{ reason }}
          </li>
        </ul>
        <div class="mt-2">
          <v-btn
            color="warning"
            size="small"
            variant="text"
            @click="_router.push('/company/subscription')"
          >
            Gérer les abonnements
          </v-btn>
        </div>
      </v-alert>

      <!-- Navigation Menu -->
      <v-list
        aria-label="Menu principal"
        class="px-4 sidebar-nav"
        density="compact"
        nav
        role="navigation"
      >
        <v-list-item
          v-for="item in primaryVisibleMenuItems"
          :key="item.key"
          :active="currentPage === item.pageKey"
          class="mb-1 menu-item-animated menu-item"
          color="primary"
          :prepend-icon="item.icon"
          rounded="lg"
          :to="item.to"
        >
          <v-list-item-title v-if="!sidebarCollapsed">{{
            item.label
          }}</v-list-item-title>
          <template #append>
            <v-badge
              v-if="item.key === 'my-tasks' && workflowCounts.myTasksCount > 0"
              :content="workflowCounts.myTasksCount > 99 ? '99+' : workflowCounts.myTasksCount"
              color="error"
              inline
            />
            <v-badge
              v-else-if="item.key === 'verification' && workflowCounts.pendingVerification > 0"
              :content="workflowCounts.pendingVerification > 99 ? '99+' : workflowCounts.pendingVerification"
              color="warning"
              inline
            />
            <v-badge
              v-else-if="item.key === 'approbation' && workflowCounts.pendingApproval > 0"
              :content="workflowCounts.pendingApproval > 99 ? '99+' : workflowCounts.pendingApproval"
              color="info"
              inline
            />
          </template>
          <v-tooltip activator="parent" location="end">{{
            item.label
          }}</v-tooltip>
        </v-list-item>

        <v-divider v-if="primaryVisibleMenuItems.length > 0" class="my-3" />

        <!-- ISO 9001 Section Header -->
        <!-- <v-list-subheader class="text-uppercase text-caption font-weight-bold menu-section">
          ISO 9001:2015
        </v-list-subheader> -->

        <NormativeModuleMenu
          v-for="moduleItem in visibleNormativeMenus"
          :key="moduleItem.menuKey"
          :collapsed="sidebarCollapsed"
          :icon="moduleItem.icon"
          :menu-value="moduleItem.menuValue"
          :module-code="moduleItem.moduleCode"
          :title="moduleItem.title"
        />

        <v-divider v-if="secondaryVisibleMenuItems.length > 0" class="my-3" />

        <v-list-item
          v-for="item in secondaryVisibleMenuItems"
          :key="item.key"
          :active="currentPage === item.pageKey"
          class="mb-1 menu-item"
          color="primary"
          :prepend-icon="item.icon"
          rounded="lg"
          :to="item.to"
        >
          <v-list-item-title v-if="!sidebarCollapsed">{{
            item.label
          }}</v-list-item-title>
          <v-tooltip v-if="sidebarCollapsed" activator="parent" location="end">{{
            item.label
          }}</v-tooltip>
        </v-list-item>
      </v-list>

      <!-- Secondary Menu removed - now in main menu -->
      <template #append />
    </ClientASidebar>

    <!-- App Bar -->
    <ClientAAppBar
      :breadcrumbs="breadcrumbs"
      :dark-mode="darkMode"
      :is-mobile="isMobile"
      :is-searching="isSearching"
      :search-query="searchQuery"
      :selected-site="selectedSite"
      :signature-missing="signatureMissing"
      :sites="sites"
      :user-email="userEmail"
      :user-initials="userInitials"
      :user-name="userName"
      @lock-session="handleLockSession"
      @logout="handleLogout"
      @select-site="selectSite"
      @toggle-dark-mode="toggleDarkMode"
      @toggle-drawer="drawer = !drawer"
      @update:search-query="
        searchQuery = $event;
        debouncedSearch();
      "
    />

    <!-- Main Content -->
    <v-main class="main-content">
      <div class="page-wrapper">
        <slot />
      </div>

      <!-- Scroll to top button -->
      <ScrollToTopButton :show="true" @scroll-to-top="scrollToTop()" />
    </v-main>

    <v-dialog v-model="showExpiredNormsDialog" max-width="680">
      <v-card v-if="expiredNormsNotice" rounded="xl">
        <v-card-title class="d-flex align-center ga-2">
          <v-icon color="warning">mdi-alert-circle-outline</v-icon>
          <span>Abonnement à renouveler</span>
        </v-card-title>
        <v-card-text>
          <p class="mb-3">
            Certaines normes du site courant sont expirées. Les modules associés
            sont verrouillés jusqu'au réabonnement.
          </p>
          <div class="d-flex flex-wrap ga-2 mb-3">
            <v-chip
              v-for="norm in expiredNormsNotice.norms"
              :key="`${norm.id}-${norm.code}`"
              color="warning"
              size="small"
              variant="tonal"
            >
              {{ norm.code || norm.name }}
            </v-chip>
          </div>
          <ul v-if="expiredNormsNotice.reasons.length > 0" class="pl-4">
            <li
              v-for="reason in expiredNormsNotice.reasons"
              :key="reason"
              class="text-body-2 mb-1"
            >
              {{ reason }}
            </li>
          </ul>
        </v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-btn
            variant="text"
            @click="showExpiredNormsDialog = false"
          >Plus tard</v-btn>
          <v-spacer />
          <v-btn color="primary" @click="goToSubscriptionFromExpiredNorms">
            Gérer les abonnements
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useDisplay, useTheme } from 'vuetify'
  import api from '@/api/client'
  import { useAuth } from '@/composables/useAuth'
  import { useDynamicSidebar } from '@/modules/clienta/composables/useDynamicSidebar'
  import { useSectionAccess } from '@/modules/clienta/composables/useSectionAccess'
  import { useSubModuleAccess } from '@/modules/clienta/composables/useSubModuleAccess'
  import { useSubscribedModules } from '@/modules/clienta/composables/useSubscribedModules'
  import { getNavigationPermissionNames } from '@/modules/clienta/utils/userPermissions'
  import { useDebounce } from '@/modules/shared/composables/useDebounce'
  import { useSmoothScroll } from '@/modules/shared/composables/useSmoothScroll'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { subscriptionService } from '@/services/subscriptionService'
  import { useAuthStore } from '@/stores/auth'
  import { useGlobalLoaderStore } from '@/stores/globalLoader'
  import { useLockScreenStore } from '@/stores/lockScreen'
  import { useSubscriptionStore } from '@/stores/subscriptionStore'
  import { getBlockingState } from '@/utils/blockingAccess'
  import {
    expandPermissionAliases,
  } from '@/utils/permissions'
  import { useWorkflowCountsStore } from '@/stores/workflowCounts'
  import ClientAAppBar from './ClientAAppBar.vue'
  import ClientALoadingOverlay from './ClientALoadingOverlay.vue'
  import ClientASidebar from './ClientASidebar.vue'
  import NormativeModuleMenu from './NormativeModuleMenu.vue'
  import ScrollToTopButton from './ScrollToTopButton.vue'
  import SidebarBrand from './SidebarBrand.vue'
  import TrialAlertBanner from './TrialAlertBanner.vue'

  interface Site {
    id: string
    name: string
    location: string
  }

  interface SidebarMenuItem {
    key: string
    pageKey: string
    label: string
    icon: string
    to: string
    permissions?: string[]
    alwaysVisible?: boolean
  }

  const _props = withDefaults(
    defineProps<{
      currentPage?: string
      currentSite?: string
    }>(),
    {
      currentPage: '',
    },
  )

  const _router = useRouter()
  const route = useRoute()
  const theme = useTheme()
  const toast = useToast()
  const { logout } = useAuth()
  const { scrollToTop } = useSmoothScroll()

  const authStore = useAuthStore()
  const globalLoader = useGlobalLoaderStore()
  const lockScreenStore = useLockScreenStore()
  const subscriptionStore = useSubscriptionStore()
  const workflowCounts = useWorkflowCountsStore()
  const { modules, fetchModules, clearModulesCache } = useSubscribedModules()
  const { fetchAccessibleSubModules } = useSubModuleAccess()
  const { fetchAccessibleSections } = useSectionAccess()
  const { visibleItems, hasPermission, activeNorms, fetchSidebar } = useDynamicSidebar()
  const drawer = ref(true)
  const activeTasksCount = ref(0)
  const { mdAndDown } = useDisplay()
  const isMobile = computed(() => mdAndDown.value)
  const darkMode = ref(theme.global.current.value.dark)
  const searchQuery = ref('')
  const isSearching = ref(false)
  const sidebarCollapsed = ref(false)

  // Company data from auth store
  const companyName = computed(
    () => authStore.enterpriseName || 'Mon Entreprise',
  )
  const companyLogoUrl = computed(() => authStore.enterpriseLogoUrl || '')
  const sidebarUserTitle = computed(() => {
    const user = authStore.user as any
    if (!user) {
      return 'Utilisateur'
    }

    const jobTitle = String(user.job_title || user.position || '').trim()
    if (jobTitle) {
      return jobTitle
    }

    const role = String(user.role || '').trim()
    if (role) {
      return role
    }

    const roleNames = Array.isArray(user.role_names) ? user.role_names : []
    if (roleNames.length > 0) {
      const primaryRole = String(roleNames[0] || '').trim()
      if (primaryRole === 'admin_entreprise') {
        return 'Admin entreprise'
      }
      if (primaryRole === 'site_manager') {
        return 'Responsable de site'
      }
      if (primaryRole) {
        return primaryRole
      }
    }

    return user.user_type === 'company' ? 'Collaborateur' : 'Utilisateur'
  })
  const _subscriptionStatus = computed(() => authStore.subscriptionStatus)

  // Dynamic counts - will be loaded from API
  const nonConformitiesCount = ref(0)
  const newReclamationsCount = ref(0)
  const daysUntilExpiration = ref<number>(0)
  const _hasExpiringSubscription = computed(
    () => daysUntilExpiration.value > 0 && daysUntilExpiration.value < 15,
  )
  const passwordChangeRequired = computed(
    () => !!(authStore.user as any)?.must_change_password,
  )
  const signatureMissing = computed(() => {
    const user = authStore.user as any
    if (!user || user.user_type !== 'company') {
      return false
    }
    return !String(user.signature_path || user.signature_url || '').trim()
  })
  const subscriptionBlocked = ref(false)
  const blockingMessage = ref('Le site courant n’a pas de souscription active.')
  const blockingReasons = ref<string[]>([])
  const showExpiredNormsDialog = ref(false)
  const expiredNormsNotice = ref<{
    siteId: number
    norms: Array<{ id: number, code: string, name: string }>
    reasons: string[]
  } | null>(null)
  const lastSubscriptionCheck = ref<{ siteId: number, checkedAt: number } | null>(
    null,
  )

  // Sites data
  const sites = ref<Site[]>([])

  const selectedSite = ref<Site | null>(null)
  let refreshInterval: ReturnType<typeof setInterval> | null = null
  let workflowInterval: ReturnType<typeof setInterval> | null = null
  let accessRefreshPromise: Promise<void> | null = null
  let dynamicDataPromise: Promise<void> | null = null
  const DYNAMIC_CACHE_KEY = 'clienta_dynamic_data_cache_v1'
  const scheduleAccessRefresh = useDebounce(() => {
    refreshAccessData(false)
    loadDynamicData(false)
  }, 250)
  const handleCompanyAccessChanged = () => scheduleAccessRefresh()
  function handleCommandPaletteShortcut (e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
      e.preventDefault()
      openCommandPalette()
    }
  }

  async function loadActiveTasksCount (): Promise<void> {
    await workflowCounts.fetchCounts()
    activeTasksCount.value = workflowCounts.myTasksCount
  }

  const visibleNormativeMenus = computed(() => {
    if (subscriptionBlocked.value) {
      return []
    }

    const dedupedByCode = new Map<
      string,
      { id?: number, icon?: string, code: string, name: string, order: number }
    >()
    for (const module of modules.value as Array<{
      id?: number
      icon?: string
      code: string
      name: string
      order: number
    }>) {
      const normalizedCode = String(module.code || '')
        .trim()
        .toLowerCase()
      if (!normalizedCode) {
        continue
      }

      const existing = dedupedByCode.get(normalizedCode)
      if (!existing || module.order < existing.order) {
        dedupedByCode.set(normalizedCode, {
          ...module,
          code: normalizedCode,
        })
      }
    }

    return [...dedupedByCode.values()]
      .toSorted((a: { order: number }, b: { order: number }) => a.order - b.order)
      .map(
        (
          module: { id?: number, icon?: string, code: string, name: string },
          index: number,
        ) => ({
          icon: module.icon || 'mdi-view-grid-outline',
          moduleCode: module.code,
          title: module.name,
          menuKey: `${module.code}-${module.id ?? index}`,
          menuValue: `module-${module.code}-${module.id ?? index}`,
        }),
      )
  })

  function hasRole (roleName: string): boolean {
    const roleNames = Array.isArray((authStore.user as any)?.role_names)
      ? (authStore.user as any).role_names
      : []
    if (roleNames.includes(roleName)) {
      return true
    }

    if (
      typeof (authStore.user as any)?.access_role === 'string'
      && (authStore.user as any).access_role === roleName
    ) {
      return true
    }

    const roles = (authStore.user as any)?.roles
    if (!Array.isArray(roles)) {
      return false
    }

    return roles.some((role: any) => {
      if (typeof role === 'string') {
        return role === roleName
      }
      if (typeof role?.name === 'string') {
        return role.name === roleName
      }
      if (typeof role?.attributes?.name === 'string') {
        return role.attributes.name === roleName
      }
      return false
    })
  }

  function canAccessMenuItem (requiredPermissions: string[]): boolean {
    const userType = authStore.user?.user_type
    if (userType === 'super_admin' || hasRole('admin_entreprise')) {
      return true
    }

    const navigationPermissions = new Set(getNavigationPermissionNames(authStore.user))
    const rawPermissionNames = new Set(
      [
        ...(Array.isArray((authStore.user as any)?.active_scoped_permissions) ? (authStore.user as any).active_scoped_permissions : []),
        ...(Array.isArray((authStore.user as any)?.effective_permissions) ? (authStore.user as any).effective_permissions : []),
        ...(Array.isArray((authStore.user as any)?.all_permissions) ? (authStore.user as any).all_permissions : []),
      ]
        .map((entry: any) => {
          if (typeof entry === 'string') return entry
          if (typeof entry?.name === 'string') return entry.name
          if (typeof entry?.attributes?.name === 'string') return entry.attributes.name
          return null
        })
        .filter((value): value is string => typeof value === 'string')
        .map(value => value.trim().toLowerCase()),
    )

    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => navigationPermissions.has(alias))
      || rawPermissionNames.has(permission.trim().toLowerCase()),
    )
  }

  function isMenuItemVisible (item: SidebarMenuItem): boolean {
    if (item.alwaysVisible) {
      return true
    }
    if (!item.permissions || item.permissions.length === 0) {
      return true // Items without permissions are always visible
    }
    // Use dynamic sidebar permissions if available, otherwise fall back to legacy permission check
    return item.permissions.some(permission => hasPermission.value(permission)) || canAccessMenuItem(item.permissions)
  }

  const primaryMenuItems = computed<SidebarMenuItem[]>(() => {
    if (subscriptionBlocked.value) {
      return []
    }

    return [
      {
        key: 'dashboard',
        pageKey: 'dashboard',
        label: 'Vue d\'ensemble',
        icon: 'mdi-view-dashboard-outline',
        to: '/company/dashboard',
        permissions: ['dashboard.read'],
      },
      {
        key: 'my-tasks',
        pageKey: 'my-tasks',
        label: 'Mes Tâches',
        icon: 'mdi-checkbox-marked-outline',
        to: '/company/my-tasks',
        permissions: [],
      },
      {
        key: 'my-actions',
        pageKey: 'my-actions',
        label: 'Mes actions',
        icon: 'mdi-format-list-checks',
        to: '/company/my-actions',
        permissions: [],
      },
      {
        key: 'sites',
        pageKey: 'sites',
        label: 'Sites',
        icon: 'mdi-office-building-outline',
        to: '/company/sites',
        permissions: ['sites.read'],
      },
      {
        key: 'verification',
        pageKey: 'verification',
        label: 'Vérification',
        icon: 'mdi-check-circle-outline',
        to: '/company/documents/verification',
        permissions: ['verify_documents'],
      },
      {
        key: 'approbation',
        pageKey: 'approbation',
        label: 'Approbation',
        icon: 'mdi-check-decagram-outline',
        to: '/company/documents/approbation',
        permissions: ['approve_documents'],
      },
      {
        key: 'norm-library',
        pageKey: 'norm-library',
        label: 'Bibliothèque des normes',
        icon: 'mdi-book-open-page-variant-outline',
        to: '/company/norm-library',
        permissions: ['norm_library.read'],
      },
    ]
  })
  const primaryVisibleMenuItems = computed(() =>
    primaryMenuItems.value.filter(isMenuItemVisible),
  )

  const secondaryMenuItems = computed<SidebarMenuItem[]>(() => {
    const items: SidebarMenuItem[] = []

    if (!subscriptionBlocked.value) {
      items.push({
        key: 'settings',
        pageKey: 'settings',
        label: 'Paramètres',
        icon: 'mdi-cog-outline',
        to: '/company/settings',
        permissions: ['settings.read'],
      }, {
        key: 'security-audit',
        pageKey: 'security-audit',
        label: 'Journal sécurité',
        icon: 'mdi-shield-search',
        to: '/company/security-audit-logs',
        permissions: ['security_audit.read'],
      })
    }

    if (authStore.user?.user_type === 'company') {
      items.push({
        key: 'subscription',
        pageKey: 'subscription',
        label: 'Abonnement',
        icon: 'mdi-credit-card-outline',
        to: '/company/subscription',
        alwaysVisible: true,
      })
    }

    return items
  })
  const secondaryVisibleMenuItems = computed(() =>
    secondaryMenuItems.value.filter(isMenuItemVisible),
  )

  function getCurrentSiteId (): number | null {
    if (authStore.currentSiteId) {
      return Number(authStore.currentSiteId)
    }
    if (selectedSite.value?.id) {
      const parsed = Number(selectedSite.value.id)
      return Number.isFinite(parsed) ? parsed : null
    }
    const raw = localStorage.getItem('current_site_id')
    if (!raw) {
      return null
    }
    const parsed = Number(raw)
    return Number.isFinite(parsed) ? parsed : null
  }

  function normalizeBlockingReasons (reasons?: unknown): string[] {
    if (!Array.isArray(reasons)) {
      return []
    }

    return Array.from(
      new Set(
        reasons.map(reason => String(reason || '').trim()).filter(Boolean),
      ),
    )
  }

  function buildBlockingMessage (reasons: string[]): string {
    if (reasons.length > 0) {
      return 'Accès bloqué pour ce site. Vérifiez les informations d’abonnement ci-dessous.'
    }
    return 'Ce site n’a pas de souscription active. Veuillez souscrire avant de continuer.'
  }

  function applySubscriptionBlockedState (siteId: number, reasons?: string[]) {
    subscriptionBlocked.value = true
    clearModulesCache()
    const normalizedReasons = normalizeBlockingReasons(reasons)
    blockingReasons.value = normalizedReasons
    blockingMessage.value = buildBlockingMessage(normalizedReasons)

    if (route.path !== '/company/subscription') {
      _router.push('/company/subscription')
    }
  }

  function clearSubscriptionBlockedState (): void {
    subscriptionBlocked.value = false
    blockingMessage.value = ''
    blockingReasons.value = []
  }

  function shouldSkipSubscriptionCheck (): boolean {
    return passwordChangeRequired.value || !authStore.token
  }

  function isSubscriptionCheckRecentlyCached (
    siteId: number,
    force: boolean,
  ): boolean {
    const cached = lastSubscriptionCheck.value
    if (force || !cached) {
      return false
    }

    return cached.siteId === siteId && Date.now() - cached.checkedAt < 10_000
  }

  function updateSubscriptionCheckCache (siteId: number): void {
    lastSubscriptionCheck.value = { siteId, checkedAt: Date.now() }
  }

  function getSubscriptionErrorMeta (error: any) {
    return {
      statusCode: error?.status || error?.response?.status,
      code: error?.code || error?.response?.data?.code,
      reasons: error?.response?.data?.blocking_reasons,
      backendRedirect: error?.response?.data?.redirect,
    }
  }

  function maybeRedirectBlockedAccess (
    statusCode: number,
    code: string,
    backendRedirect?: string,
  ): void {
    if (statusCode !== 423) {
      return
    }

    const blockingState = getBlockingState(code, authStore.user)
    const redirectPath = backendRedirect || blockingState?.redirectPath
    if (redirectPath && route.fullPath !== redirectPath) {
      _router.push(redirectPath)
    }
  }

  function isSubscriptionRequiredCode (statusCode: number, code: string): boolean {
    return (
      statusCode === 423
      && (code === 'SUBSCRIPTION_REQUIRED'
        || code === 'SUBSCRIPTION_REQUIRED_FOR_SITE')
    )
  }

  function isAllowedBlockingCode (statusCode: number, code: string): boolean {
    return (
      statusCode === 423
      && (code === 'PASSWORD_CHANGE_REQUIRED' || code === 'COMPANY_SETUP_REQUIRED')
    )
  }

  function handleSubscriptionAccessError (siteId: number, error: any): boolean {
    const meta = getSubscriptionErrorMeta(error)
    const statusCode = Number(meta.statusCode || 0)
    const code = String(meta.code || '')
    const backendRedirect
      = typeof meta.backendRedirect === 'string' ? meta.backendRedirect : undefined

    maybeRedirectBlockedAccess(statusCode, code, backendRedirect)

    if (isSubscriptionRequiredCode(statusCode, code)) {
      applySubscriptionBlockedState(siteId, meta.reasons)
      return false
    }

    if (isAllowedBlockingCode(statusCode, code)) {
      return true
    }

    return true
  }

  async function ensureSubscriptionAccess (force = false): Promise<boolean> {
    if (shouldSkipSubscriptionCheck()) {
      return true
    }

    const siteId = getCurrentSiteId()
    if (!siteId) {
      clearSubscriptionBlockedState()
      return true
    }

    if (isSubscriptionCheckRecentlyCached(siteId, force)) {
      return !subscriptionBlocked.value
    }

    try {
      const status = await subscriptionService.checkStatus(siteId)
      updateSubscriptionCheckCache(siteId)
      const hasAccess = Boolean(
        status?.hasActiveSubscription || status?.can_access_dashboard,
      )

      if (hasAccess) {
        clearSubscriptionBlockedState()
        maybeShowExpiredNormsWarning(siteId, status)
        return true
      }

      applySubscriptionBlockedState(siteId, status?.blocking_reasons)
      return false
    } catch (error: any) {
      return handleSubscriptionAccessError(siteId, error)
    }
  }

  function maybeShowExpiredNormsWarning (siteId: number, status: any): void {
    const expiredNorms = Array.isArray(status?.expired_norms)
      ? status.expired_norms
      : []
    if (expiredNorms.length === 0) {
      expiredNormsNotice.value = null
      return
    }

    if (!(hasRole('admin_entreprise') || hasRole('site_manager'))) {
      return
    }

    const signature = expiredNorms
      .map((norm: any) => String(norm?.code || norm?.name || norm?.id || ''))
      .filter(Boolean)
      .toSorted()
      .join('|')

    const key = `expired_norm_notice:${siteId}:${signature}`
    if (sessionStorage.getItem(key)) {
      return
    }

    expiredNormsNotice.value = {
      siteId,
      norms: expiredNorms,
      reasons: Array.isArray(status?.blocking_reasons)
        ? status.blocking_reasons
        : [],
    }
    showExpiredNormsDialog.value = true
    sessionStorage.setItem(key, '1')
  }

  function goToSubscriptionFromExpiredNorms (): void {
    showExpiredNormsDialog.value = false
    _router.push('/company/subscription')
  }

  async function refreshAccessData (force = false) {
    if (passwordChangeRequired.value) {
      return
    }

    const canAccess = await ensureSubscriptionAccess(force)
    if (!canAccess) {
      clearModulesCache()
      return
    }

    if (accessRefreshPromise) {
      return accessRefreshPromise
    }

    accessRefreshPromise = (async () => {
      if (force) {
        clearModulesCache()
      }
      await Promise.all([
        fetchModules(force),
        subscriptionStore.fetchSubscription(),
        fetchAccessibleSubModules(force),
        fetchAccessibleSections(force),
      ])
    })()

    try {
      await accessRefreshPromise
    } finally {
      accessRefreshPromise = null
    }
  }

  function selectSite (site: Site) {
    selectedSite.value = site
    const siteId = Number(site.id)
    if (Number.isFinite(siteId)) {
      authStore.setCurrentSite(siteId)
    }
    toast.success(`Site "${site.name}" sélectionné`, undefined, 2000)
  }

  // Load dynamic data from API
  async function loadDynamicData (force = false) {
    if (passwordChangeRequired.value) {
      return
    }

    // Don't load if not authenticated
    if (!authStore.token) {
      return
    }

    const canAccess = await ensureSubscriptionAccess(force)
    if (!canAccess) {
      nonConformitiesCount.value = 0
      newReclamationsCount.value = 0
      daysUntilExpiration.value = 0
      return
    }

    if (!force) {
      const cachedRaw = sessionStorage.getItem(DYNAMIC_CACHE_KEY)
      if (cachedRaw) {
        try {
          const cached = JSON.parse(cachedRaw)
          if (Date.now() - cached.timestamp < 60_000) {
            nonConformitiesCount.value = cached.nonConformitiesCount ?? 0
            newReclamationsCount.value = cached.newReclamationsCount ?? 0
            daysUntilExpiration.value = cached.daysUntilExpiration ?? 0
            sites.value = cached.sites ?? []
            if (!selectedSite.value && sites.value.length > 0) {
              const firstSite = sites.value[0]
              if (firstSite) {
                selectedSite.value = firstSite
              }
            }
            return
          }
        } catch {
          sessionStorage.removeItem(DYNAMIC_CACHE_KEY)
        }
      }
    }

    if (dynamicDataPromise) {
      return dynamicDataPromise
    }

    dynamicDataPromise = (async () => {
      // Load NC count (silent fail - optional stats)
      try {
        const ncResponse = await api.get('/non-conformities', {
          params: { status: 'open' },
        })
        nonConformitiesCount.value
          = ncResponse.data.data?.length || ncResponse.data.total || 0
      } catch {
        // Silent fail for NC count - not critical
        nonConformitiesCount.value = 0
      }

      // Load Reclamations count (silent fail - optional stats)
      try {
        const reclamResponse = await api.get('/reclamations', {
          params: { status: 'new' },
        })
        newReclamationsCount.value
          = reclamResponse.data.data?.length || reclamResponse.data.total || 0
      } catch {
        // Silent fail for reclamations - not critical
        newReclamationsCount.value = 0
      }

      // Load Subscription data (silent fail - optional)
      try {
        const subResponse = await api.get('/enterprise-subscriptions')
        // Get the first active subscription
        const activeSubscription = subResponse.data.data?.find(
          (sub: any) => sub.is_active,
        )
        if (activeSubscription?.expiration_date) {
          const expiresAt = new Date(activeSubscription.expiration_date)
          const today = new Date()
          const diffTime = expiresAt.getTime() - today.getTime()
          const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
          daysUntilExpiration.value = Math.max(diffDays, 0)
        }
      } catch {
        // Silent fail for subscription - not critical
        daysUntilExpiration.value = 0
      }

      // Load Sites (important - show error if fails)
      try {
        const authSites = Array.isArray(authStore.availableSites)
          ? authStore.availableSites
          : []
        let loadedSites = authSites.map((s: any) => ({
          id: String(s.id),
          name: s.name || s.attributes?.name || 'Site sans nom',
          location: s.address || s.attributes?.address || '',
        }))

        if (loadedSites.length === 0) {
          const sitesResponse = await api.get('/sites')
          const sitesData = sitesResponse.data
          loadedSites
            = sitesData.data?.map((s: any) => ({
              id: String(s.id),
              name: s.name || s.attributes?.name || 'Site sans nom',
              location: s.address || s.attributes?.address || '',
            })) || []
        }

        if (loadedSites.length > 0) {
          sites.value = loadedSites
          // Select first site if none selected
          if (!selectedSite.value) {
            selectedSite.value = loadedSites[0]
          }
        }
      } catch {
      // Silent fail for sites - non bloquant pendant le chargement du shell.
      }

      sessionStorage.setItem(
        DYNAMIC_CACHE_KEY,
        JSON.stringify({
          timestamp: Date.now(),
          nonConformitiesCount: nonConformitiesCount.value,
          newReclamationsCount: newReclamationsCount.value,
          daysUntilExpiration: daysUntilExpiration.value,
          sites: sites.value,
        }),
      )
    })()

    try {
      await dynamicDataPromise
    } catch {
    // Silent fail: les compteurs dynamiques ne doivent pas bloquer l'UI.
    } finally {
      dynamicDataPromise = null
    }
  }

  // Load data on mount
  onMounted(() => {
    if (passwordChangeRequired.value) {
      return
    }

    ensureSubscriptionAccess(false).then(canAccess => {
      if (canAccess) {
        refreshAccessData(false)
        loadDynamicData(false)
        loadActiveTasksCount()
        // Chantier 8: Load dynamic sidebar
        fetchSidebar().catch(error => {
          console.warn('Failed to load dynamic sidebar:', error)
        })
      }
    })

    // Refresh data every 5 minutes
    refreshInterval = setInterval(loadDynamicData, 5 * 60 * 1000)

    // Refresh workflow counts every 3 minutes
    workflowInterval = setInterval(() => workflowCounts.fetchCounts(true), 3 * 60 * 1000)
    window.addEventListener('company-access-changed', handleCompanyAccessChanged)
  })

  onBeforeUnmount(() => {
    if (refreshInterval) {
      clearInterval(refreshInterval)
      refreshInterval = null
    }
    if (workflowInterval) {
      clearInterval(workflowInterval)
      workflowInterval = null
    }
    window.removeEventListener(
      'company-access-changed',
      handleCompanyAccessChanged,
    )
    window.removeEventListener('keydown', handleCommandPaletteShortcut)
  })

  watch(
    isMobile,
    value => {
      drawer.value = !value
    },
    { immediate: true },
  )

  // User data
  const user = computed(() => authStore.user || {})

  const userName = computed(() => authStore.userName || 'Utilisateur')
  const userEmail = computed(
    () => authStore.userEmail || (user.value as any)?.email || '',
  )
  const userInitials = computed(() => {
    const names = userName.value.split(' ')
    return names
      .map((name: string) => name[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  })

  // Non-conformités count

  // Breadcrumbs
  const breadcrumbs = computed(() => {
    const parts = route.path.split('/').filter(Boolean)
    const crumbs = []

    if (parts.includes('company')) {
      crumbs.push({
        title: companyName.value,
        disabled: false,
        href: '/company/dashboard',
      })

      const pageTitles: Record<string, string> = {
        'dashboard': 'Dashboard',
        'sites': 'Sites',
        'subscriptions': 'Abonnements',
        'collaborators': 'Collaborateurs',
        'processes': 'Processus',
        'documents': 'Documents',
        'norm-library': 'Bibliothèque des normes',
        'reports': 'Rapports',
        'nonconformities': 'Non-conformités',
        'audits': 'Audits',
        'indicators': 'Indicateurs',
        'objectives': 'Objectifs',
        'risks': 'Risques & Opportunités',
        'reclamations': 'Réclamations clients',
        'profile': 'Profil',
        'settings': 'Paramètres',
        'security-audit-logs': 'Journal sécurité',
      }

      const currentPageKey = parts[parts.indexOf('company') + 1]
      if (currentPageKey && pageTitles[currentPageKey]) {
        crumbs.push({ title: pageTitles[currentPageKey], disabled: true })
      }
    }

    return crumbs
  })

  // ============================================================================
  // DARK MODE
  // ============================================================================

  function toggleDarkMode () {
    darkMode.value = !darkMode.value
    theme.global.name.value = darkMode.value ? 'dark' : 'light'
    toast.info(
      darkMode.value ? 'Mode sombre activé' : 'Mode clair activé',
      undefined,
      2000,
    )
  }

  function handleLockSession () {
    lockScreenStore.lockSession(route.path, userEmail.value)
    _router.push('/lock-screen')
  }

  async function handleLogout () {
    await logout()
  }

  // Debounced search
  function performSearch () {
    if (!searchQuery.value.trim()) {
      isSearching.value = false
      return
    }
    isSearching.value = true
    setTimeout(() => {
      isSearching.value = false
    }, 1000)
  }

  const debouncedSearch = useDebounce(performSearch, 500)

  function toggleSidebarCollapse () {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  const commandPaletteOpen = ref(false)

  function openCommandPalette () {
    commandPaletteOpen.value = true
  }

  function _focusSearch () {
    openCommandPalette()
  }

  onMounted(() => {
    scrollToTop(0)
    window.addEventListener('keydown', handleCommandPaletteShortcut)
  })
</script>

<style scoped>
/* Import du thème ClientA moderne */
@import "../assets/clienta-theme.css";

.main-content {
  background-color: rgb(var(--v-theme-surface));
}

/* Harmonisation globale des modals Client A (référence: modal création de site) */
:deep(.v-overlay__content .v-dialog > .v-card) {
  border-radius: 18px !important;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.28) !important;
  box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2) !important;
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92)) !important;
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-title) {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 20px 24px !important;
  border-bottom: 1px solid #e2e8f0;
  font-size: 1.05rem !important;
  font-weight: 700 !important;
  color: #0f172a !important;
  background:
    radial-gradient(circle at top right, rgba(96, 165, 250, 0.14), transparent 44%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(248, 250, 252, 0.84));
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-text) {
  padding: 18px 22px !important;
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions) {
  padding: 14px 20px !important;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.74), rgba(255, 255, 255, 0.92));
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn) {
  border-radius: 10px !important;
  text-transform: none !important;
  font-weight: 600 !important;
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn--variant-text) {
  color: #334155 !important;
}

:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn--variant-elevated),
:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn--variant-flat),
:deep(.v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn--variant-tonal) {
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.18) !important;
}

.stats-card {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

:deep(.v-list-item-title),
:deep(.v-list-item-subtitle) {
  color: rgb(var(--v-theme-on-surface)) !important;
}

:deep(.v-navigation-drawer) {
  background-color: rgb(var(--v-theme-surface)) !important;
}

:deep(.v-app-bar) {
  background-color: rgb(var(--v-theme-surface)) !important;
}

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

.sidebar-nav :deep(.v-list-item-subtitle) {
  white-space: normal !important;
  line-height: 1.3 !important;
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

.menu-group :deep(.v-list-group__items) {
  padding-left: 0;
  margin-left: 0;
}

.menu-group :deep(.v-list-group__items .v-list-item) {
  padding-left: 48px !important;
  position: relative;
}

.menu-group :deep(.v-list-group__items .v-list-item::before) {
  content: "";
  position: absolute;
  left: 20px;
  top: 50%;
  width: 4px;
  height: 4px;
  background: rgba(var(--v-theme-primary), 0.4);
  border-radius: 50%;
  transform: translateY(-50%);
}

.group-activator :deep(.v-list-item-title) {
  font-weight: 700;
  white-space: normal !important;
  line-height: 1.4 !important;
  word-wrap: break-word !important;
}

.submenu-item {
  background: transparent;
  border: 1px solid transparent;
  opacity: 0.9;
  margin-bottom: 2px !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  min-height: auto !important;
  padding-top: 8px !important;
  padding-bottom: 8px !important;
}

.submenu-item::before {
  content: "";
  position: absolute;
  left: 0;
  top: 50%;
  width: 0;
  height: 2px;
  background: rgb(var(--v-theme-primary));
  transform: translateY(-50%);
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.submenu-item:hover {
  background: rgba(var(--v-theme-primary), 0.06);
  border-color: rgba(var(--v-theme-primary), 0.12);
  transform: translateX(4px);
}

.submenu-item:hover::before {
  width: 3px;
}

.submenu-item:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

:deep(.v-list-item--active) {
  background: rgba(var(--v-theme-primary), 0.12) !important;
  border-color: rgba(var(--v-theme-primary), 0.35) !important;
  box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.2);
  transform: translateX(2px);
}

:deep(.v-list-item--active::after) {
  opacity: 1;
}

.submenu-item :deep(.v-list-item-title) {
  font-weight: 500;
  font-size: 0.875rem;
  white-space: normal !important;
  line-height: 1.4 !important;
  word-wrap: break-word !important;
  overflow-wrap: break-word !important;
}

.submenu-item :deep(.v-list-item-subtitle) {
  color: rgba(var(--v-theme-on-surface), 0.6);
  font-size: 0.75rem;
  white-space: normal !important;
  line-height: 1.3 !important;
  word-wrap: break-word !important;
}

.nested-group :deep(.v-list-group__items) {
  padding-left: 16px !important;
}

.nested-item {
  padding-left: 64px !important;
  background: transparent;
  border: 1px solid transparent;
  opacity: 0.9;
  margin-bottom: 2px !important;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  min-height: auto !important;
  padding-top: 8px !important;
  padding-bottom: 8px !important;
}

.nested-item :deep(.v-list-item-title) {
  white-space: normal !important;
  line-height: 1.4 !important;
  word-wrap: break-word !important;
}

.nested-item:hover {
  background: rgba(var(--v-theme-primary), 0.06);
  border-color: rgba(var(--v-theme-primary), 0.12);
  transform: translateX(4px);
}

.nested-item::before {
  content: "";
  position: absolute;
  left: 48px;
  top: 50%;
  width: 4px;
  height: 4px;
  background: rgba(var(--v-theme-primary), 0.4);
  border-radius: 50%;
  transform: translateY(-50%) scale(1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nested-item:hover::before {
  background: rgb(var(--v-theme-primary));
  transform: translateY(-50%) scale(1.5);
}

.nested-item:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

@media (max-width: 600px) {
  /* Mobile styles handled by components */
}

/* Page wrapper with fade-in animation */
.page-wrapper {
  animation: fadeIn 0.3s ease-in;
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

@media (max-width: 960px) {
  .page-wrapper {
    padding: 18px;
  }
}

@media (max-width: 600px) {
  .page-wrapper {
    padding: 12px;
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Menu item animations */
.menu-item-animated {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.menu-item-animated::after {
  content: "";
  position: absolute;
  right: 12px;
  top: 50%;
  width: 6px;
  height: 6px;
  background: rgb(var(--v-theme-primary));
  border-radius: 50%;
  transform: translateY(-50%) scale(0);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.menu-item-animated:hover {
  transform: translateX(4px);
  box-shadow: 0 2px 8px rgba(var(--v-theme-primary), 0.15);
}

.menu-item-animated:hover::after {
  transform: translateY(-50%) scale(1);
}

.menu-item-animated:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
  box-shadow: 0 0 0 4px rgba(var(--v-theme-primary), 0.1);
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>

<style>
/* Modal design global Client A (compatible avec les dialogs téléportés) */
body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card {
  border-radius: 18px !important;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.28) !important;
  box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2) !important;
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.92)) !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card > .v-card-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 20px 24px !important;
  border-bottom: 1px solid #e2e8f0;
  font-size: 1.05rem !important;
  font-weight: 700 !important;
  color: #0f172a !important;
  background:
    radial-gradient(circle at top right, rgba(96, 165, 250, 0.14), transparent 44%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.94), rgba(248, 250, 252, 0.84));
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card > .v-card-text {
  padding: 18px 22px !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card > .v-card-actions {
  padding: 14px 20px !important;
  border-top: 1px solid #e2e8f0;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.74), rgba(255, 255, 255, 0.92));
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card > .v-card-actions .v-btn {
  border-radius: 10px !important;
  text-transform: none !important;
  font-weight: 600 !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field {
  border-radius: 12px !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field__overlay {
  background: rgba(255, 255, 255, 0.88) !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field--variant-outlined .v-field__outline {
  color: rgba(148, 163, 184, 0.62) !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field:hover .v-field__outline {
  color: rgba(59, 130, 246, 0.62) !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field--focused .v-field__outline {
  color: rgba(59, 130, 246, 0.9) !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-field-label,
body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-label {
  color: #475569 !important;
  font-weight: 600 !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-input input,
body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-input textarea,
body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-select__selection-text {
  color: #0f172a !important;
}

body.clienta-layout-active .v-overlay-container .v-overlay__content .v-dialog > .v-card .v-card--variant-outlined {
  border-color: rgba(148, 163, 184, 0.38) !important;
  background: rgba(255, 255, 255, 0.82) !important;
}
</style>
