<template>
  <ClientALayout current-page="settings">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-h4 font-weight-bold mb-2">
          <v-icon class="mr-2">mdi-cog</v-icon>
          Paramètres entreprise
        </h1>
        <p class="text-body-2 text-medium-emphasis">
          Gérez vos informations entreprise, branding, documents et préférences
        </p>
      </div>

      <v-alert
        v-if="blockingMessage"
        class="mb-6"
        color="warning"
        icon="mdi-alert-circle-outline"
        variant="tonal"
      >
        <v-alert-title>Action requise</v-alert-title>
        {{ blockingMessage }}
      </v-alert>

      <v-alert
        v-else-if="signatureMissing"
        class="mb-6"
        color="info"
        icon="mdi-draw-pen"
        variant="tonal"
      >
        <v-alert-title>Signature recommandée</v-alert-title>
        Ajoutez votre signature dans l’onglet Profil pour accélérer les validations documentaires.
      </v-alert>

      <!-- Tabs -->
      <v-card>
        <v-tabs v-model="currentTab" bg-color="transparent">
          <v-tab value="profil">
            <v-icon start>mdi-account</v-icon>
            Profil
          </v-tab>
          <v-tab value="entreprise">
            <v-icon start>mdi-domain</v-icon>
            Entreprise
          </v-tab>
          <v-tab v-if="canUpdateSettings" value="configuration">
            <v-icon start>mdi-palette</v-icon>
            Configuration
          </v-tab>
          <v-tab value="securite">
            <v-icon start>mdi-shield-lock</v-icon>
            Sécurité
          </v-tab>
          <v-tab value="notifications">
            <v-icon start>mdi-bell</v-icon>
            Notifications
          </v-tab>
        </v-tabs>

        <v-divider />

        <v-card-text class="pa-6">
          <div class="settings-loader-scope">
            <div v-if="isTabBusy" class="settings-loader-overlay">
              <UnifiedLoader
                centered
                :message="busyMessage"
                size="sm"
                variant="spinner"
              />
            </div>

            <v-window v-model="currentTab">
              <!-- Tab 1: Profil -->
              <v-window-item value="profil">
                <ProfileTab
                  :can-update-settings="canUpdateSettings"
                  :languages="languages"
                  :photo-preview-url="photoPreviewUrl"
                  :photo-uploading="photoUploading"
                  :profile-form="profileForm"
                  :profile-saving="profileSaving"
                  :signature-preview-url="signaturePreviewUrl"
                  :signature-uploading="signatureUploading"
                  @photo-selected="handlePhotoSelected"
                  @save="saveProfileSettings"
                  @signature-selected="handleSignatureSelected"
                />
              </v-window-item>

              <!-- Tab 2: Entreprise -->
              <v-window-item value="entreprise">
                <EnterpriseTab
                  :activity-domains="activityDomains"
                  :available-cities="availableCities"
                  :available-countries="availableCountries"
                  :can-update-settings="canUpdateSettings"
                  :company-form="companyForm"
                  :company-logo-url="companyLogoUrl"
                  :company-saving="companySaving"
                  :is-company-form-valid="isCompanyFormValid"
                  :is-france-country="isFranceCountry"
                  :logo-uploading="logoUploading"
                  @logo-selected="handleCompanyLogoSelected"
                  @save="saveCompanySettings"
                />
              </v-window-item>

              <!-- Tab 3: Configuration avancée -->
              <v-window-item v-if="canUpdateSettings" value="configuration">
                <ConfigurationTab
                  :current-country="enterpriseConfig?.contact?.country || companyForm.country"
                  :enterprise-config="enterpriseConfig"
                  :enterprise-id="enterpriseId || 0"
                  @refresh="loadEnterpriseConfig"
                />
              </v-window-item>

              <!-- Tab 4: Sécurité -->
              <v-window-item value="securite">
                <SecurityTab @go-profile="currentTab = 'profil'" />
              </v-window-item>

              <!-- Tab 5: Notifications -->
              <v-window-item value="notifications">
                <NotificationsTab @open-center="router.push('/company/notifications')" />
              </v-window-item>
            </v-window>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import { COUNTRY_OPTIONS } from '@/constants/countries'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ConfigurationTab from '@/modules/clienta/pages/settings/components/ConfigurationTab.vue'
  import EnterpriseTab from '@/modules/clienta/pages/settings/components/EnterpriseTab.vue'
  import NotificationsTab from '@/modules/clienta/pages/settings/components/NotificationsTab.vue'
  import ProfileTab from '@/modules/clienta/pages/settings/components/ProfileTab.vue'
  import SecurityTab from '@/modules/clienta/pages/settings/components/SecurityTab.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { enterpriseConfigService } from '@/services/enterpriseConfigService'
  import { geoCatalogService, normalizeCountryCode } from '@/services/geoCatalogService'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { getBlockingMessage } from '@/utils/blockingAccess'
  import { expandPermissionAliases } from '@/utils/permissions'

  const currentTab = ref('profil')
  const toast = useToast()
  const authStore = useAuthStore()
  const route = useRoute()
  const router = useRouter()
  const profileSaving = ref(false)
  const companySaving = ref(false)
  const enterpriseConfig = ref<any>(null)
  const logoUploading = ref(false)
  const photoUploading = ref(false)
  const signatureUploading = ref(false)
  const signatureLocalPreviewUrl = ref('')
  const photoLocalPreviewUrl = ref('')
  const availableCountries = ref(COUNTRY_OPTIONS.map(country => ({
    code: country.code,
    name: country.name,
  })))
  const availableCities = ref<Array<{ name: string }>>([])
  const activityDomains = [
    'Qualité',
    'Environnement',
    'Santé & Sécurité',
    'Agroalimentaire',
    'Services',
    'Industrie',
    'Construction',
    'Transport',
    'Énergie',
    'Autre',
  ]

  const languages = ['Français', 'English', 'Español', 'Deutsch']

  const profileForm = ref({
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    position: '',
    language: 'Français',
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  })

  const companyForm = ref({
    name: '',
    sigle: '',
    codification_mode: 'standard',
    recode_equipements: false,
    rccm_number: '',
    ifu_number: '',
    siret: '',
    vat_number: '',
    address: '',
    city: '',
    postalCode: '',
    country: 'BJ',
    phone: '',
    email: '',
    domaineActivite: '',
  })

  const isCompanyFormValid = computed(() => {
    return Boolean(
      String(companyForm.value.name || '').trim() && String(companyForm.value.email || '').trim() && String(companyForm.value.domaineActivite || '').trim(),
    )
  })

  const isFranceCountry = computed(() => {
    const country = normalizeCountryCode(String(companyForm.value.country || ''))
    return country === 'FR' || country === 'FRANCE'
  })
  const isTabBusy = computed(() =>
    profileSaving.value
    || companySaving.value
    || logoUploading.value
    || photoUploading.value
    || signatureUploading.value,
  )
  const busyMessage = computed(() => {
    if (signatureUploading.value) return 'Import de la signature en cours...'
    if (photoUploading.value) return 'Téléversement de la photo en cours...'
    if (logoUploading.value) return 'Téléversement du logo en cours...'
    if (companySaving.value) return 'Enregistrement des paramètres entreprise...'
    if (profileSaving.value) return 'Enregistrement du profil...'
    return 'Chargement...'
  })

  const enterpriseId = computed(() => authStore.user?.enterprise?.id)
  const companyLogoPath = computed(() => {
    const brandingLogo = enterpriseConfig.value?.branding?.logo_path
    const enterpriseLogo = (authStore.user as any)?.enterprise?.logo_path
    return brandingLogo || enterpriseLogo || ''
  })
  const companyLogoUrl = computed(() => {
    const raw = String(companyLogoPath.value || '').trim()
    if (!raw) {
      return ''
    }
    if (/^https?:\/\//i.test(raw)) {
      return raw
    }
    const apiBase = String(import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1')
      .replace(/\/api\/v1\/?$/, '')
    return `${apiBase}/storage/${raw.replace(/^\/+/, '')}`
  })
  const signaturePreviewUrl = computed(() => {
    if (signatureLocalPreviewUrl.value) {
      return signatureLocalPreviewUrl.value
    }
    const user = authStore.user as any
    const raw = String(
      user?.signature_url
      || user?.signature_path
        || user?.signature
      || user?.attributes?.signature_url
        || user?.attributes?.signature_path
      || user?.attributes?.signature
        || '',
    ).trim()
    if (!raw) {
      return ''
    }
    if (/^https?:\/\//i.test(raw)) {
      return raw
    }

    const apiBase = String(import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1')
      .replace(/\/api\/v1\/?$/, '')

    return `${apiBase}/storage/${raw.replace(/^\/+/, '')}`
  })
  const photoPreviewUrl = computed(() => {
    if (photoLocalPreviewUrl.value) {
      return photoLocalPreviewUrl.value
    }
    const user = authStore.user as any
    const raw = String(
      user?.photo_url
      || user?.photo_path
        || user?.avatar_url
      || user?.avatar
        || user?.attributes?.photo_url
      || user?.attributes?.photo_path
        || user?.attributes?.avatar_url
      || user?.attributes?.avatar
        || '',
    ).trim()
    if (!raw) {
      return ''
    }
    if (/^https?:\/\//i.test(raw)) {
      return raw
    }

    const apiBase = String(import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1')
      .replace(/\/api\/v1\/?$/, '')

    return `${apiBase}/storage/${raw.replace(/^\/+/, '')}`
  })
  const signatureMissing = computed(() => !signaturePreviewUrl.value)
  const blockingMessage = computed(() => getBlockingMessage(
    typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
  ))
  const canUpdateSettings = computed(() => {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return ['settings.update'].some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  })

  const allowedTabs = new Set(['profil', 'entreprise', 'configuration', 'securite', 'notifications'])

  function hasActiveSubscriptionFromUser (user: any): boolean {
    const siteSubscriptions = Array.isArray(user?.site?.subscriptions) ? user.site.subscriptions : []
    if (siteSubscriptions.some((subscription: any) => Boolean(subscription?.is_active))) {
      return true
    }

    const enterprise = user?.enterprise
    const enterpriseSubscriptions = [
      ...(Array.isArray(enterprise?.subscriptions) ? enterprise.subscriptions : []),
      ...(Array.isArray(enterprise?.enterprise_subscriptions) ? enterprise.enterprise_subscriptions : []),
    ]

    return enterpriseSubscriptions.some((subscription: any) => Boolean(subscription?.is_active))
  }

  function sanitizeTab (tab: unknown): string {
    if (typeof tab !== 'string') {
      return 'profil'
    }
    if (tab === 'configuration' && !canUpdateSettings.value) {
      return 'profil'
    }
    return allowedTabs.has(tab) ? tab : 'profil'
  }

  function hydrateProfileForm () {
    const user = authStore.user as any
    if (!user) {
      return
    }

    const rawName = (user.name || '').trim()
    const explicitFirst = (user.first_name || '').trim()
    const explicitLast = (user.last_name || '').trim()

    const derivedParts = rawName.split(' ').filter(Boolean)
    const firstName = explicitFirst || derivedParts[0] || ''
    const lastName = explicitLast || derivedParts.slice(1).join(' ') || ''

    profileForm.value.firstName = firstName
    profileForm.value.lastName = lastName
    profileForm.value.email = user.email || ''
    profileForm.value.phone = user.phone || ''
    profileForm.value.position = user.job_title || user.role || ''
  }

  function hydrateCompanyForm () {
    const enterprise = (authStore.user as any)?.enterprise
    if (!enterprise) return
    companyForm.value.name = enterprise.name || companyForm.value.name
    companyForm.value.sigle = enterprise.sigle || companyForm.value.sigle
    companyForm.value.codification_mode = enterprise.codification_mode || companyForm.value.codification_mode
    companyForm.value.recode_equipements = false
    companyForm.value.email = enterprise.email || companyForm.value.email
    companyForm.value.phone = enterprise.phone || companyForm.value.phone
    companyForm.value.address = enterprise.address || companyForm.value.address
    companyForm.value.city = enterprise.city || companyForm.value.city
    companyForm.value.country = normalizeCountryCode(String(enterprise.country || 'BJ'))
    companyForm.value.domaineActivite = enterprise.field || enterprise.domaine_activite || companyForm.value.domaineActivite
    companyForm.value.rccm_number = enterprise.rccm_number || ''
    companyForm.value.ifu_number = enterprise.ifu_number || ''
    companyForm.value.siret = enterprise.siret || companyForm.value.siret
    companyForm.value.vat_number = enterprise.vat_number || companyForm.value.vat_number
  }

  async function hydrateLegalInfo () {
    if (!enterpriseId.value) return

    try {
      const config = await enterpriseConfigService.getConfiguration(enterpriseId.value)
      const legalRaw = config.legal_raw || {}
      const contact = config.contact || {}

      companyForm.value.rccm_number = legalRaw.rccm_number || companyForm.value.rccm_number
      companyForm.value.ifu_number = legalRaw.ifu_number || companyForm.value.ifu_number
      companyForm.value.siret = legalRaw.siret || companyForm.value.siret
      companyForm.value.vat_number = legalRaw.vat_number || companyForm.value.vat_number
      companyForm.value.country = normalizeCountryCode(String(contact.country || legalRaw.country || companyForm.value.country || 'BJ'))

      if ((companyForm.value.rccm_number || companyForm.value.ifu_number) && !companyForm.value.country) {
        companyForm.value.country = 'BJ'
      }
    } catch {
      // non bloquant
    }
  }

  async function loadEnterpriseConfig () {
    if (!enterpriseId.value) {
      enterpriseConfig.value = null
      return
    }

    try {
      enterpriseConfig.value = await enterpriseConfigService.getConfiguration(enterpriseId.value)
      const summary = (enterpriseConfig.value?.enterprise || {}) as Record<string, any>
      const branding = (enterpriseConfig.value?.branding || {}) as Record<string, any>

      applyEnterpriseSummaryToCompanyForm(summary)
      syncEnterpriseBrandingToAuthUser(summary, branding)
    } catch {
      // non bloquant pour garder la page utilisable
      enterpriseConfig.value = null
    }
  }

  function applyEnterpriseSummaryToCompanyForm (summary: Record<string, any>): void {
    if (!summary || typeof summary !== 'object') {
      return
    }

    companyForm.value.name = summary.name || companyForm.value.name
    companyForm.value.sigle = summary.sigle || companyForm.value.sigle
    companyForm.value.codification_mode = summary.codification_mode || companyForm.value.codification_mode
    companyForm.value.recode_equipements = false
    companyForm.value.email = summary.email || companyForm.value.email
    companyForm.value.phone = summary.phone || companyForm.value.phone
    companyForm.value.address = summary.address || companyForm.value.address
    companyForm.value.city = summary.city || companyForm.value.city
    companyForm.value.country = normalizeCountryCode(String(summary.country || companyForm.value.country || 'BJ'))
    companyForm.value.domaineActivite = summary.field || companyForm.value.domaineActivite
  }

  function syncEnterpriseBrandingToAuthUser (summary: Record<string, any>, branding: Record<string, any>): void {
    const currentUser = authStore.user as any
    if (!currentUser?.enterprise) {
      return
    }

    authStore.updateUser({
      enterprise: {
        ...currentUser.enterprise,
        ...summary,
        branding: {
          ...currentUser.enterprise.branding,
          ...branding,
        },
        logo_path: branding.logo_path || summary.logo_path || currentUser.enterprise.logo_path,
        logo_url: branding.logo_url || summary.logo_url || currentUser.enterprise.logo_url,
      },
    } as any)
  }

  function resolveProfilePayload (user: any) {
    const fullName = `${profileForm.value.firstName} ${profileForm.value.lastName}`.trim()
    return {
      first_name: profileForm.value.firstName || null,
      last_name: profileForm.value.lastName || null,
      name: fullName || user?.name || '',
      email: profileForm.value.email,
      phone: profileForm.value.phone || null,
    } as Record<string, any>
  }

  function attachPasswordPayloadIfNeeded (payload: Record<string, any>): string | null {
    if (!profileForm.value.newPassword) {
      return null
    }

    if (profileForm.value.newPassword !== profileForm.value.confirmPassword) {
      return 'La confirmation du mot de passe ne correspond pas.'
    }

    payload.password = profileForm.value.newPassword
    payload.password_confirmation = profileForm.value.confirmPassword
    return null
  }

  function resetProfilePasswordFields () {
    profileForm.value.currentPassword = ''
    profileForm.value.newPassword = ''
    profileForm.value.confirmPassword = ''
  }

  function applyUpdatedProfileUser (updatedUser: any, payload: Record<string, any>) {
    if (updatedUser) {
      authStore.updateUser({
        ...(updatedUser as any),
        must_change_password: false,
      } as any)
      return
    }

    authStore.updateUser({
      name: payload.name,
      email: payload.email,
      phone: payload.phone,
      must_change_password: false,
    } as any)
  }

  async function saveProfileSettings () {
    if (!canUpdateSettings.value) {
      return
    }
    try {
      profileSaving.value = true
      const userId = authStore.user?.id
      if (!userId) {
        toast.error('Utilisateur introuvable.')
        return
      }

      const payload = resolveProfilePayload(authStore.user)
      const passwordError = attachPasswordPayloadIfNeeded(payload)
      if (passwordError) {
        toast.error(passwordError)
        return
      }

      const response = await api.put(`/users/${userId}`, payload)
      const updatedUser = response?.data?.data?.attributes || response?.data?.data || null

      applyUpdatedProfileUser(updatedUser, payload)
      resetProfilePasswordFields()

      toast.success('Profil mis à jour')
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Erreur lors de la mise à jour du profil'
      toast.error(message)
    } finally {
      profileSaving.value = false
    }
  }

  function buildCompanyPayload () {
    return {
      name: companyForm.value.name,
      sigle: companyForm.value.sigle || null,
      codification_mode: companyForm.value.codification_mode || 'standard',
      recode_equipements: companyForm.value.recode_equipements || false,
      email: companyForm.value.email,
      phone: companyForm.value.phone,
      address: companyForm.value.address,
      city: companyForm.value.city,
      country: companyForm.value.country,
      field: companyForm.value.domaineActivite || null,
    }
  }

  function buildCompanyLegalPayload () {
    return {
      country: companyForm.value.country,
      rccm_number: companyForm.value.rccm_number || null,
      ifu_number: companyForm.value.ifu_number || null,
      siret: companyForm.value.siret || null,
      vat_number: companyForm.value.vat_number || null,
    }
  }

  function updateAuthEnterprise (updated: any) {
    authStore.updateUser({
      enterprise: {
        ...authStore.user?.enterprise,
        ...updated,
      },
    })
  }

  async function syncUserAfterCompanySave (cameFromCompanySetupBlocking: boolean) {
    try {
      const me = await api.get('/auth/me')
      if (!me?.data?.user) {
        return false
      }

      authStore.updateUser(me.data.user)
      if (cameFromCompanySetupBlocking && !hasActiveSubscriptionFromUser(me.data.user)) {
        await router.replace('/company/subscription?blocking=SUBSCRIPTION_REQUIRED_FOR_SITE')
        toast.success('Configuration entreprise enregistrée. Veuillez finaliser la souscription.')
        return true
      }
    } catch {
      // non bloquant
    }

    return false
  }

  async function clearCompanyBlockingQueryIfNeeded () {
    if (route.query.blocking !== 'COMPANY_SETUP_REQUIRED') {
      return
    }

    await router.replace({
      query: {
        ...route.query,
        blocking: undefined,
      },
    })
  }

  async function saveCompanySettings () {
    if (!canUpdateSettings.value) {
      return
    }
    if (!enterpriseId.value) {
      toast.error('Entreprise introuvable.')
      return
    }

    if (!String(companyForm.value.domaineActivite || '').trim()) {
      toast.error('Le domaine d’activité est obligatoire.')
      currentTab.value = 'entreprise'
      return
    }

    const cameFromCompanySetupBlocking = route.query.blocking === 'COMPANY_SETUP_REQUIRED'

    try {
      companySaving.value = true
      const payload = buildCompanyPayload()

      const response = await api.put(`/enterprises/${enterpriseId.value}`, payload)
      const updated = response.data?.data?.attributes || response.data?.data || {}

      await api.put(`/enterprises/${enterpriseId.value}/legal-info`, buildCompanyLegalPayload())
      updateAuthEnterprise(updated)
      companyForm.value.recode_equipements = false

      const redirectedToSubscription = await syncUserAfterCompanySave(cameFromCompanySetupBlocking)
      if (redirectedToSubscription) {
        return
      }

      await clearCompanyBlockingQueryIfNeeded()
      toast.success('Informations entreprise mises à jour')
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Erreur lors de la mise à jour'
      toast.error(message)
    } finally {
      companySaving.value = false
    }
  }

  onMounted(() => {
    currentTab.value = sanitizeTab(route.query.tab)
    if (route.query.blocking === 'COMPANY_SETUP_REQUIRED') {
      currentTab.value = 'entreprise'
    }
    hydrateProfileForm()
    hydrateCompanyForm()
    hydrateLegalInfo()
    loadEnterpriseConfig()
  })

  async function handleCompanyLogoSelected (event: Event) {
    if (!canUpdateSettings.value) {
      return
    }
    if (!enterpriseId.value) {
      return
    }
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file) {
      return
    }

    try {
      logoUploading.value = true
      const formData = new FormData()
      formData.append('logo', file)
      await api.put(`/enterprises/${enterpriseId.value}/branding`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      await loadEnterpriseConfig()
      toast.success('Logo mis à jour')
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Erreur lors de la mise à jour du logo'
      toast.error(message)
    } finally {
      logoUploading.value = false
      target.value = ''
    }
  }

  async function handleSignatureSelected (event: Event) {
    if (!canUpdateSettings.value) {
      return
    }
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file) {
      return
    }

    if (signatureLocalPreviewUrl.value) {
      URL.revokeObjectURL(signatureLocalPreviewUrl.value)
    }
    signatureLocalPreviewUrl.value = URL.createObjectURL(file)

    const userId = authStore.user?.id
    if (!userId) {
      toast.error('Utilisateur introuvable.')
      target.value = ''
      return
    }

    const allowedTypes = new Set(['image/png', 'image/jpeg'])
    if (!allowedTypes.has(file.type)) {
      toast.error('Format invalide. Utilise uniquement PNG ou JPG.')
      target.value = ''
      return
    }

    const maxSizeBytes = 1 * 1024 * 1024
    if (file.size > maxSizeBytes) {
      toast.error('Fichier trop volumineux. Taille maximale: 1 Mo.')
      target.value = ''
      return
    }

    try {
      signatureUploading.value = true
      const formData = new FormData()
      formData.append('signature', file)

      const response = await api.post(`/users/${userId}/signature`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const payload = response?.data?.data || {}
      authStore.updateUser({
        signature_path: payload.signature_path,
        signature_url: payload.signature_url,
        signature_uploaded_at: payload.signature_uploaded_at,
      } as any)

      if (route.query.blocking === 'SIGNATURE_UPLOAD_REQUIRED') {
        await router.replace({
          query: {
            ...route.query,
            blocking: undefined,
          },
        })
      }

      toast.success('Signature importée avec succès.')
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Erreur lors de l’import de la signature'
      toast.error(message)
    } finally {
      signatureUploading.value = false
      target.value = ''
    }
  }

  async function handlePhotoSelected (event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (!file) {
      return
    }

    if (photoLocalPreviewUrl.value) {
      URL.revokeObjectURL(photoLocalPreviewUrl.value)
    }
    photoLocalPreviewUrl.value = URL.createObjectURL(file)

    const userId = authStore.user?.id
    if (!userId) {
      toast.error('Utilisateur introuvable.')
      target.value = ''
      return
    }

    const allowedTypes = new Set(['image/png', 'image/jpeg', 'image/gif'])
    if (!allowedTypes.has(file.type)) {
      toast.error('Format invalide. Utilise uniquement PNG, JPG ou GIF.')
      target.value = ''
      return
    }

    const maxSizeBytes = 2 * 1024 * 1024
    if (file.size > maxSizeBytes) {
      toast.error('Fichier trop volumineux. Taille maximale: 2 Mo.')
      target.value = ''
      return
    }

    try {
      photoUploading.value = true
      const formData = new FormData()
      formData.append('photo', file)

      const response = await api.post(`/users/${userId}/photo`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })

      const payload = response?.data?.data || {}
      authStore.updateUser({
        photo_path: payload.photo_path,
        photo_url: payload.photo_url,
      } as any)

      toast.success('Photo de profil mise à jour.')
    } catch (error: any) {
      const message = error?.response?.data?.message || 'Erreur lors de l’import de la photo'
      toast.error(message)
    } finally {
      photoUploading.value = false
      target.value = ''
    }
  }

  watch(() => companyForm.value.country, async countryCode => {
    const normalized = normalizeCountryCode(String(countryCode || ''))
    if (!normalized) {
      availableCities.value = []
      companyForm.value.city = ''
      return
    }

    const cities = await geoCatalogService.getCitiesByCountry(normalized)
    availableCities.value = cities
    if (companyForm.value.city && !cities.some(city => city.name === companyForm.value.city)) {
      companyForm.value.city = ''
    }
  }, { immediate: true })

  onMounted(async () => {
    const countries = await geoCatalogService.getCountries()
    availableCountries.value = countries.map(country => ({
      code: country.code,
      name: country.name,
    }))
  })

  watch(() => route.query.tab, tab => {
    const nextTab = sanitizeTab(tab)
    if (nextTab !== currentTab.value) {
      currentTab.value = nextTab
    }
  })

  watch(currentTab, tab => {
    const safeTab = sanitizeTab(tab)
    if (route.query.tab === safeTab) {
      return
    }
    router.replace({
      query: {
        ...route.query,
        tab: safeTab,
      },
    })
  })
</script>

<style scoped>
.settings-loader-scope {
  position: relative;
}

.settings-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}
</style>
