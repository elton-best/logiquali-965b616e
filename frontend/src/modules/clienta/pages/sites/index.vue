<template>
  <ClientALayout current-page="sites">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <PageHeader
        :primary-action="canCreateSite ? 'Nouveau site' : undefined"
        primary-action-icon="mdi-plus"
        subtitle="Gérez vos sites et emplacements"
        title="Mes Sites"
        @primary-action="openCreateWizard"
      />

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
        v-if="sitesLoadError"
        class="mb-6"
        color="warning"
        icon="mdi-alert-outline"
        variant="tonal"
      >
        {{ sitesLoadError }}
      </v-alert>

      <SitesStats :stats="stats" />

      <SitesFilters
        :filters="filters"
        :status-filter-options="statusFilterOptions"
        @reset="resetFilters"
        @update:search="filters.search = $event"
        @update:status="filters.status = $event"
      />

      <SitesTable
        :can-create-site="canCreateSite"
        :can-delete-site="canDeleteSite"
        :can-manage-subscriptions="canManageSubscriptions"
        :can-update-site="canUpdateSite"
        :get-active-norm-labels="getActiveNormLabels"
        :get-initials="getInitials"
        :has-any-active-subscription="hasAnyActiveSubscription"
        :headers="headers"
        :loading="loading"
        :pagination="pagination"
        :sites="sites"
        @create="openCreateWizard"
        @delete="handleDelete"
        @details="openDetailsDialog"
        @edit="openEditWizard"
        @items-per-page="onItemsPerPageChange"
        @manage-subscription="openSubscriptionDialog"
        @page="onPageChange"
      />

      <SiteDetailsDialog
        v-model="showDetailsDialog"
        :can-update-site="canUpdateSite"
        :format-date="formatDate"
        :get-days-remaining-color="getDaysRemainingColor"
        :selected-site-details="selectedSiteDetails"
        :site-details-loading="siteDetailsLoading"
        :site-subscription="siteSubscription"
        @close="closeDetailsDialog"
        @edit="editFromDetails"
      />

      <!-- Create/Edit Wizard Dialog -->
      <v-dialog
        v-model="showWizard"
        :fullscreen="$vuetify.display.mobile"
        max-width="1200"
        persistent
        scrollable
      >
        <v-card class="wizard-card">
          <div v-if="submitLoading" class="dialog-loader-overlay">
            <UnifiedLoader
              description="Validation des données du site et du responsable"
              :show-skeleton="true"
              title="Enregistrement du site en cours..."
              variant="local"
            />
          </div>

          <v-card-title class="dialog-header">
            <span>{{
              editMode ? "Modifier le site" : "Créer un nouveau site"
            }}</span>
            <button
              class="btn-close"
              :disabled="submitLoading"
              @click="closeWizard"
            >
              <v-icon>mdi-close</v-icon>
            </button>
          </v-card-title>

          <v-divider />

          <v-card-text class="dialog-content">
            <TabsContainer v-model="wizardTab" :tabs="wizardSteps" />

            <div class="wizard-step-content">
              <SiteInfoStep
                v-if="wizardTab === 'info'"
                v-model="wizardData.siteInfo"
              />

              <ManagerStep
                v-if="wizardTab === 'manager'"
                v-model="wizardData.managerData"
                :collaborators="collaborators"
                :loading="loadingCollaborators"
                :manager-type="managerType"
                @update:manager-type="onManagerTypeChange"
              />
              <v-alert
                v-if="wizardTab === 'manager' && collaboratorsLoadError"
                class="mt-4"
                type="warning"
                variant="tonal"
              >
                {{ collaboratorsLoadError }}
              </v-alert>

              <RoleStep
                v-if="wizardTab === 'role'"
                v-model="wizardData.role"
                :permissions="wizardData.customPermissions"
                :site-id="editMode ? editingId : null"
                @update:permissions="wizardData.customPermissions = $event"
              />

              <ReviewStep
                v-if="wizardTab === 'review'"
                :collaborators="collaborators"
                :manager-data="wizardData.managerData"
                :permissions="wizardData.customPermissions"
                :role-permissions="selectedRolePermissions"
                :selected-role="wizardData.role"
                :site-info="wizardData.siteInfo"
              />
            </div>
          </v-card-text>

          <v-divider />

          <!-- Actions -->
          <v-card-actions class="dialog-actions">
            <button
              v-if="wizardStepIndex > 0"
              class="btn-secondary"
              :disabled="submitLoading"
              @click="previousStep"
            >
              <v-icon size="20">mdi-chevron-left</v-icon>
              Précédent
            </button>

            <v-spacer />

            <button
              class="btn-secondary"
              :disabled="submitLoading"
              @click="closeWizard"
            >
              Annuler
            </button>

            <button
              v-if="wizardTab !== 'review'"
              class="btn-primary"
              :disabled="submitLoading || !canProceedToNextStep"
              @click="nextStep"
            >
              Suivant
              <v-icon size="20">mdi-chevron-right</v-icon>
            </button>

            <button
              v-else
              class="btn-primary"
              :disabled="submitLoading"
              @click="submitWizard"
            >
              <v-icon size="20">mdi-check</v-icon>
              {{ editMode ? "Modifier" : "Créer" }} le site
            </button>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Subscription Dialog -->
      <SubscriptionDialog
        v-model="showSubscriptionDialog"
        :current-subscription="selectedSiteSubscription"
        :site="selectedSiteForSubscription"
        @subscribed="handleSubscribed"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { QueryParams } from '@/types/api'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/api/client'
  import {
    AppButton,
  } from '@/components/common'
  import TabsContainer from '@/components/leadership/TabsContainer.vue'
  import { useToast } from '@/composables/useToast'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ManagerStep from '@/modules/clienta/components/SiteWizard/ManagerStep.vue'
  import ReviewStep from '@/modules/clienta/components/SiteWizard/ReviewStep.vue'
  import RoleStep from '@/modules/clienta/components/SiteWizard/RoleStep.vue'
  import SiteInfoStep from '@/modules/clienta/components/SiteWizard/SiteInfoStep.vue'
  import SubscriptionDialog from '@/modules/clienta/components/SubscriptionDialog.vue'
  import SiteDetailsDialog from '@/modules/clienta/pages/sites/components/SiteDetailsDialog.vue'
  import SitesFilters from '@/modules/clienta/pages/sites/components/SitesFilters.vue'
  import SitesStats from '@/modules/clienta/pages/sites/components/SitesStats.vue'
  import SitesTable from '@/modules/clienta/pages/sites/components/SitesTable.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import permissionService, { type Role } from '@/services/permissionService'
  import siteService, { type Site } from '@/services/siteService'
  import { useAuthStore } from '@/stores/auth'
  import { useSiteContextStore } from '@/stores/siteContext'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { getBlockingMessage } from '@/utils/blockingAccess'

  const route = useRoute()
  const toast = useToast()
  const authStore = useAuthStore()
  const siteContextStore = useSiteContextStore()
  let siteContextChangeTimer: ReturnType<typeof setTimeout> | null = null
  const blockingMessage = computed(() =>
    getBlockingMessage(
      typeof route.query.blocking === 'string' ? route.query.blocking : undefined,
    ),
  )
  const canCreateSite = computed(() => isEnterpriseAdminUser(authStore.user))
  const canUpdateSite = computed(() => isEnterpriseAdminUser(authStore.user))
  const canDeleteSite = computed(() => isEnterpriseAdminUser(authStore.user))
  const canManageSubscriptions = computed(() => isEnterpriseAdminUser(authStore.user))

  // Data
  const sites = ref<Site[]>([])
  const loading = ref(false)
  const sitesLoadError = ref('')
  const filters = ref({
    search: '',
    status: '',
  })

  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 50,
    total: 0,
  })

  // Wizard state
  const showWizard = ref(false)
  const wizardTab = ref<'info' | 'manager' | 'role' | 'review'>('info')
  const editMode = ref(false)
  const submitLoading = ref(false)
  const editingId = ref<number | null>(null)

  const collaborators = ref<any[]>([])
  const roleCatalog = ref<Role[]>([])
  const loadingCollaborators = ref(false)
  const collaboratorsLoadError = ref('')
  const managerType = ref<'existing' | 'new'>('existing')

  // Details dialog state
  const showDetailsDialog = ref(false)
  const selectedSiteDetails = ref<Site | null>(null)
  const siteSubscription = ref<any>(null)
  const siteDetailsLoading = ref(false)

  // Subscription dialog state
  const showSubscriptionDialog = ref(false)
  const selectedSiteForSubscription = ref<Site | null>(null)
  const selectedSiteSubscription = ref<any>(null)

  const wizardData = ref({
    siteInfo: {
      name: '',
      address: '',
      country: 'BJ',
      city: '',
      phone: '',
      email: '',
      is_headquarter: false,
      is_active: true,
    },
    managerData: {
      manager_id: null as number | null,
      new_manager: {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        position: '',
        address: '',
        start_date: '',
      },
    },
    role: '',
    customPermissions: [] as string[],
  })

  const wizardSteps = [
    { value: 'info', label: 'Informations', icon: 'mdi-office-building' },
    { value: 'manager', label: 'Responsable', icon: 'mdi-account-tie' },
    { value: 'role', label: 'Rôle', icon: 'mdi-shield-account' },
    { value: 'review', label: 'Récapitulatif', icon: 'mdi-check-decagram' },
  ]
  const wizardStepOrder: Array<'info' | 'manager' | 'role' | 'review'> = [
    'info',
    'manager',
    'role',
    'review',
  ]

  const wizardStepIndex = computed(() => {
    return wizardStepOrder.indexOf(wizardTab.value)
  })

  const selectedRolePermissions = computed(() => {
    if (!wizardData.value.role) {
      return []
    }
    const role = roleCatalog.value.find(
      item => item.name === wizardData.value.role,
    )
    return Array.isArray(role?.permissions) ? role.permissions : []
  })

  // Table headers
  const headers = [
    { title: 'Nom', key: 'name', sortable: true },
    { title: 'Localisation', key: 'location', sortable: true },
    { title: 'Responsable de site', key: 'manager', sortable: false },
    { title: 'Statut', key: 'is_active', sortable: true },
    { title: 'Abonnement', key: 'subscription', sortable: false },
    { title: 'Utilisateurs', key: 'users_count', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const statusFilterOptions = [
    { label: 'Actifs', value: 'active' },
    { label: 'Inactifs', value: 'inactive' },
  ]

  // Computed
  const stats = computed(() => ({
    total: pagination.value.total,
    active: sites.value.filter(s => s.is_active).length,
    users: sites.value.reduce((sum, s) => sum + (s.users_count || 0), 0),
    processes: sites.value.reduce((sum, s) => sum + (s.processes_count || 0), 0),
  }))

  const canProceedToNextStep = computed(() => {
    if (wizardTab.value === 'info') {
      const info = wizardData.value.siteInfo
      // Name, address, country and city are required
      return !!(
        info.name.trim()
        && info.address.trim()
        && info.country
        && info.city.trim()
      )
    }
    if (wizardTab.value === 'manager') {
      const info = wizardData.value.siteInfo
      // Pour le siège social, le responsable peut être vide:
      // le backend assignera le responsable de site par défaut.
      if (
        info.is_headquarter
        && !wizardData.value.managerData.manager_id
        && !wizardData.value.managerData.new_manager.email
      ) {
        return true
      }
      if (managerType.value === 'existing') {
        return !!wizardData.value.managerData.manager_id
      } else {
        const newMgr = wizardData.value.managerData.new_manager
        const email = String(newMgr.email || '').trim()
        const isValidEmail = /.+@.+\..+/.test(email)
        return !!(
          String(newMgr.first_name || '').trim()
          && String(newMgr.last_name || '').trim()
          && String(newMgr.position || '').trim()
          && isValidEmail
        )
      }
    }
    return true
  })

  // Methods
  async function loadSites () {
    loading.value = true
    try {
      sitesLoadError.value = ''
      const params: QueryParams = {
        page: pagination.value.current_page,
        per_page: pagination.value.per_page,
      }

      // Add filters to params
      if (filters.value.search) {
        params.search = filters.value.search
      }
      if (filters.value.status === 'active') {
        params.is_active = true
      } else if (filters.value.status === 'inactive') {
        params.is_active = false
      }
      if (
        siteContextStore.activeScope === 'site'
        && siteContextStore.activeSiteId
      ) {
        params.site_id = Number(siteContextStore.activeSiteId)
      }

      const response = await siteService.getAll(params)
      sites.value = response.data
      pagination.value = {
        current_page: response.meta.current_page,
        last_page: response.meta.last_page,
        per_page: response.meta.per_page,
        total: response.meta.total,
      }
    } catch (error: any) {
      sitesLoadError.value
        = error.message
          || 'Le chargement des sites est temporairement indisponible.'
      toast.error(error.message || 'Erreur lors du chargement des sites')
    } finally {
      loading.value = false
    }
  }

  function onPageChange (page: number) {
    pagination.value.current_page = page
    loadSites()
  }

  function onItemsPerPageChange (itemsPerPage: number) {
    pagination.value.per_page = itemsPerPage
    pagination.value.current_page = 1 // Reset to first page
    loadSites()
  }

  async function loadCollaborators () {
    loadingCollaborators.value = true
    try {
      collaboratorsLoadError.value = ''
      const { data } = await api.get('/users', {
        params: {
          per_page: 100,
        },
      })
      collaborators.value = data.data.map((user: any) => {
        const attrs = user.attributes || user
        return {
          id: user.id,
          full_name:
            attrs.name
            || `${attrs.first_name || ''} ${attrs.last_name || ''}`.trim()
            || 'Sans nom',
          email: attrs.email,
          position: attrs.position || 'Non spécifié',
          avatar: attrs.avatar || attrs.photo_path,
        }
      })
    } catch (error) {
      console.error('Erreur chargement collaborateurs:', error)
      collaborators.value = []
      collaboratorsLoadError.value
        = 'Le chargement des collaborateurs est indisponible. Vous pouvez continuer avec un responsable existant.'
    } finally {
      loadingCollaborators.value = false
    }
  }

  async function loadRoleCatalog (retried = false) {
    try {
      roleCatalog.value = await permissionService.getAvailableRoles(
        editMode.value ? editingId.value : null,
      )
    } catch (error: any) {
      if (!retried && error?.response?.status === 429) {
        await new Promise(resolve => setTimeout(resolve, 350))
        await loadRoleCatalog(true)
        return
      }
      console.error('Erreur chargement catalogue rôles:', error)
      roleCatalog.value = []
    }
  }

  function openCreateWizard () {
    if (!canCreateSite.value) {
      toast.error('Seul l\'administrateur d\'entreprise peut créer un site.')
      return
    }

    editMode.value = false
    editingId.value = null
    managerType.value = 'existing'
    resetWizardData()
    showWizard.value = true
    wizardTab.value = 'info'
    loadCollaborators()
    loadRoleCatalog()
  }

  function openEditWizard (site: Site) {
    if (!canUpdateSite.value) {
      toast.error('Seul l\'administrateur d\'entreprise peut modifier un site.')
      return
    }
    editMode.value = true
    editingId.value = site.id
    managerType.value = site.manager_id ? 'existing' : 'new'
    // Map site data to wizard format
    wizardData.value.siteInfo = {
      name: site.name,
      address: site.location || '',
      country: 'BJ',
      city: site.city || '',
      phone: site.phone || '',
      email: site.email || '',
      is_headquarter: site.is_headquarter,
      is_active: site.is_active,
    }
    wizardData.value.managerData = {
      manager_id: site.manager_id ?? null,
      new_manager: {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        position: '',
        address: '',
        start_date: '',
      },
    }
    showWizard.value = true
    wizardTab.value = 'info'
    loadCollaborators()
    loadRoleCatalog()
  }

  function closeWizard () {
    try {
      showWizard.value = false
      wizardTab.value = 'info'
      managerType.value = 'existing'
      resetWizardData()
    } catch (error) {
      console.error('Error closing wizard:', error)
      showWizard.value = false
    }
  }

  function resetWizardData () {
    wizardData.value = {
      siteInfo: {
        name: '',
        address: '',
        country: 'BJ',
        city: '',
        phone: '',
        email: '',
        is_headquarter: false,
        is_active: true,
      },
      managerData: {
        manager_id: null as number | null,
        new_manager: {
          first_name: '',
          last_name: '',
          email: '',
          phone: '',
          position: '',
          address: '',
          start_date: '',
        },
      },
      role: '',
      customPermissions: [],
    }
  }

  async function nextStep () {
    if (!canProceedToNextStep.value) {
      return
    }

    // Ensure role permissions are available before rendering the recap step.
    // Without this refresh, transient catalog failures can show 0 in recap.
    if (wizardTab.value === 'role') {
      await loadRoleCatalog()
    }

    const nextIndex = wizardStepIndex.value + 1
    if (nextIndex < wizardStepOrder.length) {
      wizardTab.value = wizardStepOrder[nextIndex]
    }
  }

  function previousStep () {
    const previousIndex = wizardStepIndex.value - 1
    if (previousIndex >= 0) {
      wizardTab.value = wizardStepOrder[previousIndex]
    }
  }

  function onManagerTypeChange (type: 'existing' | 'new') {
    managerType.value = type
  }

  function ensureCreateAllowed () {
    if (!editMode.value && !canCreateSite.value) {
      toast.error('Seul l\'administrateur d\'entreprise peut créer un site.')
      return false
    }
    return true
  }

  function buildSitePayload () {
    const payload: any = {
      name: wizardData.value.siteInfo.name,
      location: wizardData.value.siteInfo.address,
      city: wizardData.value.siteInfo.city,
      phone: wizardData.value.siteInfo.phone || undefined,
      email: wizardData.value.siteInfo.email || undefined,
      is_headquarter: wizardData.value.siteInfo.is_headquarter,
      is_active: wizardData.value.siteInfo.is_active,
    }

    const hasExplicitManager
      = !!wizardData.value.managerData.manager_id
        || !!wizardData.value.managerData.new_manager.email

    if (
      managerType.value === 'existing'
      && wizardData.value.managerData.manager_id
    ) {
      payload.manager_id = wizardData.value.managerData.manager_id
    } else if (
      managerType.value === 'new'
      && wizardData.value.managerData.new_manager.email
    ) {
      payload.new_manager = wizardData.value.managerData.new_manager
    }

    if (
      (hasExplicitManager || !wizardData.value.siteInfo.is_headquarter)
      && wizardData.value.role
    ) {
      payload.role = wizardData.value.role
    }

    if (wizardData.value.customPermissions.length > 0) {
      payload.permissions = Array.from(
        new Set(wizardData.value.customPermissions),
      )
    }

    return payload
  }

  async function persistSiteFromWizard (sitePayload: any) {
    if (editMode.value && editingId.value) {
      const updated = await siteService.update(editingId.value, sitePayload)
      const index = sites.value.findIndex(s => s.id === editingId.value)
      if (index !== -1) {
        sites.value[index] = updated
      }
      toast.success('Site modifié avec succès')
      return
    }

    const created = await siteService.create(sitePayload)
    sites.value.unshift(created)
    pagination.value.total++
    toast.success('Site créé avec succès')
  }

  function handleSubmitWizardError (error: any) {
    const status = Number(error?.response?.status || 0)
    if (status === 403) {
      toast.error(
        'Action non autorisée. Seul l\'administrateur d\'entreprise peut créer un site.',
      )
      return
    }
    toast.error(error?.response?.data?.message || 'Erreur lors de la soumission')
  }

  async function submitWizard () {
    submitLoading.value = true
    try {
      if (!ensureCreateAllowed()) {
        return
      }

      const sitePayload = buildSitePayload()
      await persistSiteFromWizard(sitePayload)

      // Synchroniser immédiatement le sélecteur de site global (topbar) sans refresh manuel.
      await siteContextStore.loadAvailableSites(true)

      closeWizard()
      await loadSites()
    } catch (error: any) {
      handleSubmitWizardError(error)
    } finally {
      submitLoading.value = false
    }
  }

  async function handleDelete (item: Site) {
    if (!canDeleteSite.value) {
      toast.error('Seul l\'administrateur d\'entreprise peut supprimer un site.')
      return
    }
    if (!confirm(`Êtes-vous sûr de vouloir supprimer le site "${item.name}" ?`)) {
      return
    }

    try {
      await siteService.delete(item.id)
      sites.value = sites.value.filter(s => s.id !== item.id)
      pagination.value.total--
      toast.success('Site supprimé avec succès')
    } catch (error: any) {
      toast.error(error.message || 'Erreur lors de la suppression du site')
    }
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: '',
    }
    pagination.value.current_page = 1
    loadSites()
  }

  // Details dialog functions
  function resolveSubscriptionOfferName (subscription: any) {
    const offerFromAttributes = subscription.attributes?.plan_name
    const offerFromRelationship
      = subscription.relationships?.offer?.attributes?.name
    const offerFromLegacy
      = subscription.attributes?.offer?.name || subscription.offer?.name
    return (
      offerFromAttributes
      || offerFromRelationship
      || offerFromLegacy
      || 'Offre non définie'
    )
  }

  function resolveSubscriptionNormsLabel (subscription: any) {
    const norms = subscription.relationships?.offer?.relationships?.norms || []
    if (!Array.isArray(norms)) {
      return ''
    }

    return norms
      .map((norm: any) => norm?.attributes?.code || norm?.attributes?.name)
      .filter(Boolean)
      .join(', ')
  }

  function resolveSubscriptionDates (subscription: any, today: Date) {
    const expirationDate = new Date(
      subscription.attributes?.expiration_date || subscription.expiration_date,
    )
    const diffTime = expirationDate.getTime() - today.getTime()
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return {
      expiration_date:
        subscription.attributes?.expiration_date || subscription.expiration_date,
      start_date: subscription.attributes?.start_date || subscription.start_date,
      days_remaining: Math.max(diffDays, 0),
    }
  }

  function mapSubscriptionSummary (subscription: any, today: Date) {
    const dates = resolveSubscriptionDates(subscription, today)

    return {
      id: subscription.id,
      offer_name: resolveSubscriptionOfferName(subscription),
      norms_label: resolveSubscriptionNormsLabel(subscription),
      start_date: dates.start_date,
      expiration_date: dates.expiration_date,
      is_active:
        subscription.attributes?.is_active ?? subscription.is_active ?? true,
      days_remaining: dates.days_remaining,
    }
  }

  async function openDetailsDialog (site: Site) {
    selectedSiteDetails.value = site
    showDetailsDialog.value = true
    siteDetailsLoading.value = true
    siteSubscription.value = null

    try {
      // Load subscriptions for this site
      const { data } = await api.get('/enterprise-subscriptions', {
        params: {
          site_id: site.id,
        },
      })

      if (data.data && data.data.length > 0) {
        // Prendre toutes les souscriptions actives
        const today = new Date()
        siteSubscription.value = data.data.map((subscription: any) =>
          mapSubscriptionSummary(subscription, today),
        )
      }
    } catch (error) {
      console.error('Error loading subscription:', error)
    } finally {
      siteDetailsLoading.value = false
    }
  }

  function closeDetailsDialog () {
    showDetailsDialog.value = false
    selectedSiteDetails.value = null
    siteSubscription.value = null
  }

  function editFromDetails () {
    if (!canUpdateSite.value) {
      return
    }
    if (selectedSiteDetails.value) {
      closeDetailsDialog()
      openEditWizard(selectedSiteDetails.value)
    }
  }

  function formatDate (date: string | null | undefined): string {
    if (!date) return 'Non définie'
    return new Date(date).toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
  }

  function getDaysRemainingColor (days: number): string {
    if (days <= 7) return 'error'
    if (days <= 30) return 'warning'
    return 'success'
  }

  // Subscription dialog functions
  function openSubscriptionDialog (site: Site) {
    if (!canManageSubscriptions.value) {
      return
    }
    selectedSiteForSubscription.value = site
    const firstActive
      = site.subscriptions?.find(sub => sub.is_active)
        || site.subscriptions?.[0]
        || null
    selectedSiteSubscription.value = firstActive || site.subscription || null
    showSubscriptionDialog.value = true
  }

  function handleSubscribed () {
    loadSites() // Reload sites to get updated subscription
  }

  // Helper function
  function getInitials (name: string): string {
    if (!name) return '?'
    const parts = name.trim().split(' ')
    if (parts.length >= 2) {
      const first = parts[0]?.[0] || ''
      const lastPart = parts.at(-1)
      const last = lastPart?.[0] || ''
      return (first + last).toUpperCase() || '?'
    }
    return name.slice(0, 2).toUpperCase()
  }

  function hasAnyActiveSubscription (site: Site): boolean {
    if (Array.isArray(site.active_norms) && site.active_norms.length > 0) {
      return true
    }

    if (site.subscriptions?.length) {
      return site.subscriptions.some(sub => sub.is_active)
    }

    return !!site.subscription?.is_active
  }

  function getActiveNormLabels (site: Site): string[] {
    if (Array.isArray(site.active_norms) && site.active_norms.length > 0) {
      return Array.from(new Set(site.active_norms.filter(Boolean)))
    }

    const labels = new Set<string>()

    const addLabelsFromSubscriptions = (
      subscriptions: NonNullable<Site['subscriptions']>,
    ) => {
      for (const sub of subscriptions) {
        const norms = sub.offer?.norms || []
        if (norms.length === 0 && sub.offer?.name) {
          labels.add(sub.offer.name)
          continue
        }

        for (const norm of norms) {
          const label = norm.code || norm.name
          if (label) labels.add(label)
        }
      }
    }

    if (site.subscriptions?.length) {
      const activeSubscriptions = site.subscriptions.filter(
        sub => sub.is_active,
      )
      if (activeSubscriptions.length > 0) {
        addLabelsFromSubscriptions(activeSubscriptions)
      } else {
        // Fallback si le backend ne marque pas correctement is_active mais renvoie les abonnements
        addLabelsFromSubscriptions(site.subscriptions)
      }
    } else if (site.subscription?.is_active) {
      const norms = site.subscription.offer?.norms || []
      if (norms.length === 0 && site.subscription.offer?.name) {
        labels.add(site.subscription.offer.name)
      } else {
        for (const norm of norms) {
          const label = norm.code || norm.name
          if (label) labels.add(label)
        }
      }
    }

    return Array.from(labels)
  }

  // Lifecycle
  function handleSiteContextChanged () {
    if (siteContextChangeTimer) {
      clearTimeout(siteContextChangeTimer)
    }

    siteContextChangeTimer = setTimeout(() => {
      pagination.value.current_page = 1
      loadSites()
    }, 200)
  }

  onMounted(async () => {
    await siteContextStore.loadAvailableSites()
    await loadSites()
    window.addEventListener('site-context-changed', handleSiteContextChanged)
  })

  onUnmounted(() => {
    if (siteContextChangeTimer) {
      clearTimeout(siteContextChangeTimer)
      siteContextChangeTimer = null
    }
    window.removeEventListener('site-context-changed', handleSiteContextChanged)
  })

  // Watch filters for auto-reload
  watch(
    () => filters.value.search,
    () => {
      pagination.value.current_page = 1
      loadSites()
    },
  )

  watch(
    () => filters.value.status,
    () => {
      pagination.value.current_page = 1
      loadSites()
    },
  )
</script>

<style scoped>
.wizard-card {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
}

.dialog-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(2px);
}

.dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.btn-close {
  border: none;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  width: 34px;
  height: 34px;
  border-radius: 8px;
  display: grid;
  place-items: center;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.dialog-content {
  max-height: 70vh;
  overflow-y: auto;
  padding: 18px 22px;
}

.wizard-step-content {
  margin-top: 16px;
}

.dialog-actions {
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: #fff;
  border: none;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(74, 113, 176, 0.25);
}

.btn-primary:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #fff;
  color: #334155;
  border: 1px solid #d0d7e2;
  padding: 10px 14px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #b8c4d6;
}

</style>
