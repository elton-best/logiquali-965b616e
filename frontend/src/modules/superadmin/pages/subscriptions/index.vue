<template>
  <SuperAdminLayout current-page="subscriptions">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center flex-wrap ga-4">
            <div>
              <div class="d-flex align-center mb-2">
                <v-icon class="mr-3" color="primary" size="32">mdi-file-document-outline</v-icon>
                <h1 class="text-h4 font-weight-bold text-primary">Gestion des Abonnements</h1>
              </div>
              <p class="text-subtitle-1 text-grey-darken-1">
                Gérez tous les abonnements actifs, expirés et suspendus
              </p>
            </div>
            <div class="d-flex align-center ga-3">
              <v-btn-toggle v-model="viewMode" density="compact" mandatory>
                <v-btn value="table" variant="outlined">
                  <v-icon start>mdi-table</v-icon>
                  Table
                </v-btn>
                <v-btn value="cards" variant="outlined">
                  <v-icon start>mdi-view-grid</v-icon>
                  Cartes
                </v-btn>
              </v-btn-toggle>
              <v-btn
                color="primary"
                :loading="loading"
                prepend-icon="mdi-refresh"
                variant="outlined"
                @click="loadSubscriptions"
              >
                Actualiser
              </v-btn>
            </div>
          </div>
        </v-col>
      </v-row>

      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col
          v-for="stat in stats"
          :key="stat.label"
          cols="12"
          lg="4"
          sm="6"
        >
          <StatCard
            :color="stat.color"
            :icon="stat.icon"
            :label="stat.label"
            :value="stat.value"
          />
        </v-col>
      </v-row>

      <!-- Filters -->
      <v-card class="mb-6 sa-card sa-filters" elevation="2">
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="5">
              <v-text-field
                v-model="filters.search"
                clearable
                density="comfortable"
                hide-details
                label="Rechercher"
                placeholder="Entreprise, site..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                @update:model-value="debouncedSearch"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                density="comfortable"
                hide-details
                :items="statusOptions"
                label="Statut"
                variant="outlined"
                @update:model-value="loadSubscriptions"
              />
            </v-col>
            <v-col class="d-flex align-center" cols="12" md="4">
              <v-btn
                block
                color="grey-darken-1"
                prepend-icon="mdi-filter-off-outline"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Subscriptions Table -->
      <v-card v-if="viewMode === 'table'" class="sa-card sa-table-card" elevation="2">
        <v-card-text class="pa-0">
          <!-- Loading State -->
          <v-skeleton-loader v-if="loading" type="table" />

          <!-- Data Table -->
          <v-data-table
            v-else
            class="subscriptions-table sa-table"
            density="compact"
            :headers="headers"
            hover
            :items="subscriptions"
            :items-per-page="20"
          >
            <template #no-data>
              <EmptyState
                description="Aucun resultat ne correspond a vos filtres."
                icon="mdi-credit-card-outline"
                title="Aucun abonnement trouve"
              />
            </template>
            <!-- Enterprise Column -->
            <template #item.enterprise="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar
                  class="mr-3"
                  color="primary"
                  size="40"
                  variant="tonal"
                >
                  <v-icon color="primary">mdi-office-building-outline</v-icon>
                </v-avatar>
                <div>
                  <div class="font-weight-bold text-body-1">
                    {{ (item.site as any)?.enterprise?.name || 'N/A' }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ item.site?.name || 'Site non renseigné' }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Offer Column -->
            <template #item.offer="{ item }">
              <div>
                <div class="text-body-1 font-weight-medium">
                  {{ item.offer?.name || 'N/A' }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ formatPrice(item.offer?.price) }} / {{ item.offer?.duration_months }} mois
                </div>
              </div>
            </template>

            <!-- Period Column -->
            <template #item.period="{ item }">
              <div class="text-body-2">
                <div>{{ formatDate(item.start_date) }}</div>
                <div class="text-caption text-medium-emphasis">
                  → {{ formatDate(getSubscriptionEndDate(item)) }}
                </div>
              </div>
            </template>

            <!-- Days Remaining -->
            <template #item.days_remaining="{ item }">
              <v-chip
                :color="getDaysColor(item)"
                size="x-small"
                variant="tonal"
              >
                {{ getDaysRemaining(item) }}
              </v-chip>
            </template>

            <!-- Status Column -->
            <template #item.status="{ item }">
              <v-chip
                :color="getStatusColor(item.status)"
                size="x-small"
                variant="tonal"
              >
                <v-icon v-if="item.status === 'active'" size="14" start>mdi-check-circle</v-icon>
                <v-icon v-else-if="item.status === 'suspended'" size="14" start>mdi-pause-circle</v-icon>
                <v-icon v-else-if="item.status === 'expired'" size="14" start>mdi-clock-alert</v-icon>
                <v-icon v-else size="14" start>mdi-close-circle</v-icon>
                {{ getStatusLabel(item.status) }}
              </v-chip>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
              <div class="d-flex ga-2">
                <v-menu>
                  <template #activator="{ props }">
                    <v-tooltip location="top" text="Actions">
                      <template #activator="{ props: tooltipProps }">
                        <v-btn
                          color="grey-darken-1"
                          icon="mdi-dots-vertical"
                          size="small"
                          variant="tonal"
                          v-bind="{ ...props, ...tooltipProps }"
                        />
                      </template>
                    </v-tooltip>
                  </template>
                  <v-list density="compact">
                    <v-list-item @click="$router.push(`/superadmin/subscriptions/${item.id}`)">
                      <template #prepend>
                        <v-icon color="primary" size="20">mdi-eye-outline</v-icon>
                      </template>
                      <v-list-item-title>Voir détails</v-list-item-title>
                    </v-list-item>
                    <v-divider />
                    <v-list-item
                      v-if="item.status === 'suspended'"
                      @click="confirmReactivate(item)"
                    >
                      <template #prepend>
                        <v-icon color="success" size="20">mdi-check-circle-outline</v-icon>
                      </template>
                      <v-list-item-title>Réactiver</v-list-item-title>
                    </v-list-item>
                    <v-list-item
                      v-if="item.status === 'active'"
                      @click="confirmSuspend(item)"
                    >
                      <template #prepend>
                        <v-icon color="warning" size="20">mdi-pause-circle-outline</v-icon>
                      </template>
                      <v-list-item-title>Suspendre</v-list-item-title>
                    </v-list-item>
                    <v-divider />
                    <v-list-item @click="confirmDelete(item)">
                      <template #prepend>
                        <v-icon color="error" size="20">mdi-delete-outline</v-icon>
                      </template>
                      <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
              </div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="subscriptions.length === 0" cols="12">
          <EmptyState
            description="Aucun resultat ne correspond a vos filtres."
            icon="mdi-credit-card-outline"
            title="Aucun abonnement trouve"
          />
        </v-col>
        <v-col
          v-for="subscription in subscriptions"
          :key="subscription.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div class="font-weight-bold">{{ (subscription.site as any)?.enterprise?.name || 'N/A' }}</div>
                <div class="text-caption text-medium-emphasis">{{ subscription.site?.name || 'Site non renseigné' }}</div>
              </div>
              <v-chip :color="getStatusColor(subscription.status)" size="x-small" variant="tonal">
                {{ getStatusLabel(subscription.status) }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-1">{{ subscription.offer?.name || 'N/A' }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ formatDate(subscription.start_date) }} → {{ formatDate(getSubscriptionEndDate(subscription)) }}
              </div>
              <div class="mt-2">
                <v-chip :color="getDaysColor(subscription)" size="x-small" variant="tonal">
                  {{ getDaysRemaining(subscription) }}
                </v-chip>
              </div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-menu>
                <template #activator="{ props }">
                  <v-tooltip location="top" text="Actions">
                    <template #activator="{ props: tooltipProps }">
                      <v-btn v-bind="{ ...props, ...tooltipProps }" icon="mdi-dots-vertical" variant="text" />
                    </template>
                  </v-tooltip>
                </template>
                <v-list density="compact">
                  <v-list-item @click="$router.push(`/superadmin/subscriptions/${subscription.id}`)">
                    <template #prepend>
                      <v-icon color="primary" size="20">mdi-eye-outline</v-icon>
                    </template>
                    <v-list-item-title>Voir détails</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item
                    v-if="subscription.status === 'suspended'"
                    @click="confirmReactivate(subscription)"
                  >
                    <template #prepend>
                      <v-icon color="success" size="20">mdi-check-circle-outline</v-icon>
                    </template>
                    <v-list-item-title>Réactiver</v-list-item-title>
                  </v-list-item>
                  <v-list-item
                    v-if="subscription.status === 'active'"
                    @click="confirmSuspend(subscription)"
                  >
                    <template #prepend>
                      <v-icon color="warning" size="20">mdi-pause-circle-outline</v-icon>
                    </template>
                    <v-list-item-title>Suspendre</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item @click="confirmDelete(subscription)">
                    <template #prepend>
                      <v-icon color="error" size="20">mdi-delete-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-error">Supprimer</v-list-item-title>
                  </v-list-item>
                </v-list>
              </v-menu>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <StatusDialog
      v-model="suspendDialog"
      v-model:reason="actionReason"
      confirm-color="warning"
      confirm-label="Suspendre"
      icon="mdi-pause-circle-outline"
      :loading="submitting"
      :message="`Confirmez la suspension de <strong>${subscriptionLabel}</strong>.`"
      :reason-error="reasonError"
      reason-label="Raison de la suspension *"
      reason-placeholder="Expliquez pourquoi cet abonnement est suspendu..."
      show-reason
      title="Suspendre l'abonnement"
      @cancel="suspendDialog = false"
      @confirm="handleSuspend"
    />

    <StatusDialog
      v-model="reactivateDialog"
      confirm-color="success"
      confirm-label="Réactiver"
      icon="mdi-check-circle-outline"
      :loading="submitting"
      :message="`Confirmez la réactivation de <strong>${subscriptionLabel}</strong>.`"
      title="Réactiver l'abonnement"
      @cancel="reactivateDialog = false"
      @confirm="handleReactivate"
    />

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: l'abonnement sera supprime et ne pourra pas etre reactive."
      :loading="submitting"
      message="Etes-vous sur de vouloir supprimer cet abonnement ?"
      title="Supprimer l'abonnement"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Subscription } from '@/types/api'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import StatusDialog from '@/modules/superadmin/components/StatusDialog.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const toast = useToast()

  // State
  const loading = ref(true)
  const submitting = ref(false)
  const subscriptions = ref<Subscription[]>([])
  const viewMode = ref<'table' | 'cards'>(localStorage.getItem('sa_view_subscriptions') === 'cards' ? 'cards' : 'table')

  // Filters
  const filters = ref({
    search: '',
    status: 'all',
  })

  const statusOptions = [
    { title: 'Tous', value: 'all' },
    { title: 'Actifs', value: 'active' },
    { title: 'Suspendus', value: 'suspended' },
    { title: 'Expirés', value: 'expired' },
    { title: 'Annulés', value: 'cancelled' },
  ]

  // Dialogs
  const suspendDialog = ref(false)
  const reactivateDialog = ref(false)
  const deleteDialog = ref(false)
  const subscriptionToAction = ref<Subscription | null>(null)
  const actionReason = ref('')
  const reasonError = ref('')
  const { run: runLocked } = useActionLock()

  const subscriptionLabel = computed(() => {
    const subscription = subscriptionToAction.value
    if (!subscription) return 'cet abonnement'
    const enterpriseName = (subscription.site as any)?.enterprise?.name
    const siteName = subscription.site?.name
    if (enterpriseName && siteName) {
      return `${enterpriseName} (${siteName})`
    }
    return enterpriseName || siteName || 'cet abonnement'
  })

  // Table headers
  const headers = [
    { title: 'Entreprise / Site', key: 'enterprise', sortable: false },
    { title: 'Offre', key: 'offer', sortable: false },
    { title: 'Période', key: 'period', sortable: false },
    { title: 'Reste', key: 'days_remaining', sortable: false },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' as const },
  ] as const

  // Stats computed
  const stats = computed(() => {
    const active = subscriptions.value.filter(s => s.status === 'active').length
    const suspended = subscriptions.value.filter(s => s.status === 'suspended').length
    const expired = subscriptions.value.filter(s => s.status === 'expired').length

    return [
      {
        label: 'Total Abonnements',
        value: subscriptions.value.length,
        icon: 'mdi-file-document-multiple-outline',
        color: 'primary',
      },
      {
        label: 'Abonnements Actifs',
        value: active,
        icon: 'mdi-check-circle-outline',
        color: 'success',
      },
      {
        label: 'Suspendus',
        value: suspended,
        icon: 'mdi-pause-circle-outline',
        color: 'warning',
      },
      {
        label: 'Expirés',
        value: expired,
        icon: 'mdi-clock-alert-outline',
        color: 'error',
      },
    ]
  })

  // Load data
  onMounted(async () => {
    await loadSubscriptions()
  })

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_subscriptions', mode)
  })

  async function loadSubscriptions () {
    loading.value = true
    try {
      const params: any = {}

      if (filters.value.status && filters.value.status !== 'all') {
        params.status = filters.value.status
      }

      if (filters.value.search) {
        params.search = filters.value.search
      }

      const response = await superAdminService.getSubscriptions(params)
      subscriptions.value = response.data
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de charger les abonnements.')
    } finally {
      loading.value = false
    }
  }

  // Debounced search
  let searchTimeout: any = null
  function debouncedSearch () {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      loadSubscriptions()
    }, 500)
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: 'all',
    }
    loadSubscriptions()
  }

  // Actions
  function confirmSuspend (subscription: Subscription) {
    subscriptionToAction.value = subscription
    actionReason.value = ''
    reasonError.value = ''
    suspendDialog.value = true
  }

  function confirmReactivate (subscription: Subscription) {
    subscriptionToAction.value = subscription
    reactivateDialog.value = true
  }

  function confirmDelete (subscription: Subscription) {
    subscriptionToAction.value = subscription
    deleteDialog.value = true
  }

  async function handleSuspend () {
    if (!subscriptionToAction.value) return

    if (!actionReason.value || actionReason.value.length < 10) {
      reasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    await runLocked('subscription-suspend', async () => {
      submitting.value = true
      try {
        await superAdminService.suspendSubscription(subscriptionToAction.value!.id, actionReason.value)
        toast.success('Abonnement suspendu')
        suspendDialog.value = false
        await loadSubscriptions()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suspension impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleReactivate () {
    if (!subscriptionToAction.value) return

    await runLocked('subscription-reactivate', async () => {
      submitting.value = true
      try {
        await superAdminService.reactivateSubscription(subscriptionToAction.value!.id)
        toast.success('Abonnement réactivé')
        reactivateDialog.value = false
        await loadSubscriptions()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Réactivation impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleDelete () {
    if (!subscriptionToAction.value) return

    await runLocked('subscription-delete', async () => {
      submitting.value = true
      try {
        await superAdminService.deleteSubscription(subscriptionToAction.value!.id)
        toast.success('Abonnement supprimé')
        deleteDialog.value = false
        await loadSubscriptions()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suppression impossible pour cet abonnement.')
      } finally {
        submitting.value = false
      }
    })
  }

  function getSubscriptionEndDate (subscription: Subscription): string {
    return (subscription as any).expiration_date || subscription.end_date || ''
  }

  // Helpers
  function getDaysRemaining (subscription: Subscription): string {
    const end = new Date(getSubscriptionEndDate(subscription))
    if (Number.isNaN(end.getTime())) return 'N/A'
    const now = new Date()
    const diff = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

    if (diff < 0) return 'Expiré'
    if (diff === 0) return 'Expire aujourd\'hui'
    return `${diff} jour${diff > 1 ? 's' : ''}`
  }

  function getDaysColor (subscription: Subscription): string {
    const end = new Date(getSubscriptionEndDate(subscription))
    if (Number.isNaN(end.getTime())) return 'default'
    const now = new Date()
    const diff = Math.ceil((end.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

    if (diff < 0) return 'error'
    if (diff <= 7) return 'warning'
    if (diff <= 30) return 'info'
    return 'success'
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      active: 'success',
      suspended: 'warning',
      expired: 'error',
      cancelled: 'error',
    }
    return colors[status] || 'default'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      active: 'Actif',
      suspended: 'Suspendu',
      expired: 'Expiré',
      cancelled: 'Annulé',
    }
    return labels[status] || status
  }

  function formatPrice (price?: number): string {
    if (!price) return 'N/A'
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'XOF',
      minimumFractionDigits: 0,
    }).format(price)
  }

  function formatDate (date?: string): string {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  }
</script>

<style scoped>
.stat-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.subscriptions-table :deep(tbody tr) {
  transition: background-color 0.2s ease;
}

.subscriptions-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}
</style>
