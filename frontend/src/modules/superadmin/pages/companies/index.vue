<template>
  <SuperAdminLayout current-page="companies">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <v-row class="mb-6">
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center flex-wrap ga-4">
            <div>
              <div class="d-flex align-center mb-2">
                <v-icon class="mr-3" color="primary" size="32">mdi-office-building-outline</v-icon>
                <h1 class="text-h4 font-weight-bold text-primary">Gestion des Entreprises</h1>
              </div>
              <p class="text-subtitle-1 text-grey-darken-1">
                Gérez toutes les entreprises inscrites sur la plateforme BestQHSE
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
                @click="loadEnterprises"
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
          lg="3"
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
                placeholder="Nom, email, RCCM..."
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
                @update:model-value="handleStatusChange"
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

      <!-- Companies Table -->
      <v-card v-if="viewMode === 'table'" class="sa-card sa-table-card" elevation="2">
        <v-card-text class="pa-0">
          <!-- Loading State -->
          <v-skeleton-loader v-if="loading" type="table" />

          <!-- Data Table -->
          <v-data-table
            v-else
            class="companies-table sa-table"
            density="compact"
            :headers="headers"
            hover
            :items="enterprises"
            :items-per-page="meta.per_page || 20"
          >
            <template #no-data>
              <EmptyState
                description="Aucun resultat ne correspond a vos filtres."
                icon="mdi-office-building-outline"
                title="Aucune entreprise trouvee"
              />
            </template>
            <!-- Logo / Name Column -->
            <template #item.name="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar
                  class="mr-3"
                  color="primary"
                  :image="item.logo_url"
                  size="48"
                  variant="tonal"
                >
                  <span v-if="!item.logo_url" class="text-h6">
                    {{ item.name.charAt(0).toUpperCase() }}
                  </span>
                </v-avatar>
                <div>
                  <div class="font-weight-bold text-body-1">{{ item.name }}</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ item.field || 'Secteur non renseigné' }}
                  </div>
                </div>
              </div>
            </template>

            <!-- Email Column -->
            <template #item.email="{ item }">
              <div class="text-body-2">{{ item.email }}</div>
            </template>

            <!-- RCCM Column -->
            <template #item.registration_number="{ item }">
              <div class="text-body-2 font-weight-medium">
                {{ item.registration_number || 'N/A' }}
              </div>
            </template>

            <!-- Subscriptions Count -->
            <template #item.subscriptions_count="{ item }">
              <v-chip
                color="primary"
                size="x-small"
                variant="tonal"
              >
                {{ item.subscriptions_count || 0 }}
              </v-chip>
            </template>

            <!-- Status Column -->
            <template #item.status="{ item }">
              <v-chip
                :color="getStatusColor(item.status)"
                size="x-small"
                variant="tonal"
              >
                {{ getStatusLabel(item.status) }}
              </v-chip>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
              <div class="d-flex ga-2">
                <v-tooltip location="top" text="Voir">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="primary"
                      icon="mdi-eye-outline"
                      size="small"
                      variant="tonal"
                      @click="viewEnterprise(item)"
                    />
                  </template>
                </v-tooltip>
                <v-menu v-if="item.status !== 'rejected'">
                  <template #activator="{ props }">
                    <v-tooltip location="top" text="Actions">
                      <template #activator="{ props: tooltipProps }">
                        <v-btn
                          color="grey-darken-1"
                          icon="mdi-dots-vertical"
                          size="small"
                          variant="tonal"
                          v-bind="tooltipProps"
                        />
                      </template>
                    </v-tooltip>
                  </template>
                  <v-list density="compact">
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

          <!-- Pagination -->
          <div v-if="meta.total > meta.per_page" class="pa-4">
            <v-pagination
              v-model="currentPage"
              :length="meta.last_page"
              @update:model-value="loadEnterprises"
            />
          </div>
        </v-card-text>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="enterprises.length === 0" cols="12">
          <EmptyState
            description="Aucun resultat ne correspond a vos filtres."
            icon="mdi-office-building-outline"
            title="Aucune entreprise trouvee"
          />
        </v-col>
        <v-col
          v-for="enterprise in enterprises"
          :key="enterprise.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div class="font-weight-bold">{{ enterprise.name }}</div>
                <div class="text-caption text-medium-emphasis">{{ enterprise.email }}</div>
              </div>
              <v-chip :color="getStatusColor(enterprise.status)" size="x-small" variant="tonal">
                {{ getStatusLabel(enterprise.status) }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-1">RCCM: {{ enterprise.registration_number || 'N/A' }}</div>
              <div class="text-body-2 text-medium-emphasis">Abonnements: {{ enterprise.subscriptions_count || 0 }}</div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Voir">
                <template #activator="{ props: tooltipProps }">
                  <v-btn v-bind="tooltipProps" icon="mdi-eye-outline" variant="text" @click="viewEnterprise(enterprise)" />
                </template>
              </v-tooltip>
              <v-menu>
                <template #activator="{ props }">
                  <v-tooltip location="top" text="Actions">
                    <template #activator="{ props: tooltipProps }">
                      <v-btn v-bind="{ ...props, ...tooltipProps }" icon="mdi-dots-vertical" variant="text" />
                    </template>
                  </v-tooltip>
                </template>
                <v-list density="compact">
                  <v-list-item @click="viewEnterprise(enterprise)">
                    <template #prepend>
                      <v-icon size="20">mdi-eye-outline</v-icon>
                    </template>
                    <v-list-item-title>Voir détails</v-list-item-title>
                  </v-list-item>
                  <v-divider v-if="enterprise.status !== 'suspended'" />
                  <v-list-item v-if="enterprise.status === 'active'" @click="confirmSuspend(enterprise)">
                    <template #prepend>
                      <v-icon color="warning" size="20">mdi-pause-circle-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-warning">Suspendre</v-list-item-title>
                  </v-list-item>
                  <v-list-item v-if="enterprise.status === 'suspended'" @click="confirmReactivate(enterprise)">
                    <template #prepend>
                      <v-icon color="success" size="20">mdi-play-circle-outline</v-icon>
                    </template>
                    <v-list-item-title class="text-success">Réactiver</v-list-item-title>
                  </v-list-item>
                  <v-divider />
                  <v-list-item @click="confirmDelete(enterprise)">
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
      :message="`Confirmez la suspension de <strong>${enterpriseToAction?.name ?? ''}</strong>.`"
      :reason-error="reasonError"
      reason-label="Raison de la suspension *"
      reason-placeholder="Expliquez pourquoi cette entreprise est suspendue..."
      show-reason
      title="Suspendre l'entreprise"
      @cancel="suspendDialog = false"
      @confirm="handleSuspend"
    />

    <StatusDialog
      v-model="reactivateDialog"
      confirm-color="success"
      confirm-label="Réactiver"
      icon="mdi-check-circle-outline"
      :loading="submitting"
      :message="`Confirmez la réactivation de <strong>${enterpriseToAction?.name ?? ''}</strong>.`"
      title="Réactiver l'entreprise"
      @cancel="reactivateDialog = false"
      @confirm="handleReactivate"
    />

    <ConfirmDialog
      v-model="deleteDialog"
      confirm-label="Supprimer"
      impact="Impact: suppression definitive de l'entreprise, de ses sites et de ses documents dans l'espace super admin."
      :loading="submitting"
      :message="`Etes-vous sur de vouloir supprimer ${enterpriseToAction?.name} ?`"
      title="Supprimer l'entreprise"
      @cancel="deleteDialog = false"
      @confirm="handleDelete"
    />
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise } from '@/types/api'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import ConfirmDialog from '@/modules/superadmin/components/ConfirmDialog.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import StatusDialog from '@/modules/superadmin/components/StatusDialog.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const router = useRouter()
  const toast = useToast()
  interface EnterpriseWithStats extends Enterprise {
    subscriptions_count?: number
    logo_url?: string
  }

  // State
  const loading = ref(true)
  const submitting = ref(false)
  const enterprises = ref<EnterpriseWithStats[]>([])
  const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })
  const currentPage = ref(1)
  const viewMode = ref<'table' | 'cards'>(localStorage.getItem('sa_view_companies') === 'cards' ? 'cards' : 'table')

  // Filters
  const filters = ref({
    search: '',
    status: 'all',
  })

  const statusOptions = [
    { title: 'Tous', value: 'all' },
    { title: 'Actives', value: 'active' },
    { title: 'Suspendues', value: 'suspended' },
    { title: 'En attente', value: 'pending' },
    { title: 'Rejetées', value: 'rejected' },
  ]

  // Dialogs
  const suspendDialog = ref(false)
  const reactivateDialog = ref(false)
  const deleteDialog = ref(false)
  const enterpriseToAction = ref<Enterprise | null>(null)
  const actionReason = ref('')
  const reasonError = ref('')
  const { run: runLocked } = useActionLock()

  // Table headers
  const headers = [
    { title: 'Entreprise', key: 'name', sortable: true },
    { title: 'Email', key: 'email', sortable: true },
    { title: 'RCCM', key: 'registration_number', sortable: false },
    { title: 'Abonnements', key: 'subscriptions_count', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' as const },
  ] as const

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_companies', mode)
  })

  // Stats computed
  const stats = computed(() => {
    const active = enterprises.value.filter(e => e.status === 'active').length
    const suspended = enterprises.value.filter(e => e.status === 'suspended').length
    const pending = enterprises.value.filter(e => e.status === 'pending').length

    return [
      {
        label: 'Total Entreprises',
        value: meta.value.total,
        icon: 'mdi-office-building-outline',
        color: 'primary',
      },
      {
        label: 'Entreprises Actives',
        value: active,
        icon: 'mdi-check-circle-outline',
        color: 'success',
      },
      {
        label: 'Entreprises Suspendues',
        value: suspended,
        icon: 'mdi-pause-circle-outline',
        color: 'warning',
      },
      {
        label: 'En attente',
        value: pending,
        icon: 'mdi-clock-outline',
        color: 'info',
      },
    ]
  })

  // Load data
  onMounted(async () => {
    await loadEnterprises()
    if (typeof window !== 'undefined') {
      window.addEventListener('superadmin-kyc-updated', loadEnterprises)
    }
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('superadmin-kyc-updated', loadEnterprises)
    }
  })

  async function loadEnterprises () {
    loading.value = true
    try {
      const params: any = {
        page: currentPage.value,
        per_page: 20,
      }

      if (filters.value.status && filters.value.status !== 'all') {
        params.status = filters.value.status
      }

      if (filters.value.search) {
        params.search = filters.value.search
      }

      const response = await superAdminService.getEnterprises(params)
      enterprises.value = response.data
      meta.value = response.meta
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de charger les entreprises.')
    } finally {
      loading.value = false
    }
  }

  // Debounced search
  let searchTimeout: any = null
  function debouncedSearch () {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      currentPage.value = 1
      loadEnterprises()
    }, 500)
  }

  function handleStatusChange () {
    currentPage.value = 1
    loadEnterprises()
  }

  function resetFilters () {
    filters.value = {
      search: '',
      status: 'all',
    }
    currentPage.value = 1
    loadEnterprises()
  }

  // Actions
  function viewEnterprise (enterprise: EnterpriseWithStats) {
    router.push(`/superadmin/companies/${enterprise.id}`)
  }

  function confirmSuspend (enterprise: EnterpriseWithStats) {
    enterpriseToAction.value = enterprise
    actionReason.value = ''
    reasonError.value = ''
    suspendDialog.value = true
  }

  function confirmReactivate (enterprise: EnterpriseWithStats) {
    enterpriseToAction.value = enterprise
    reactivateDialog.value = true
  }

  function confirmDelete (enterprise: EnterpriseWithStats) {
    enterpriseToAction.value = enterprise
    deleteDialog.value = true
  }

  async function handleSuspend () {
    if (!enterpriseToAction.value) return

    if (!actionReason.value || actionReason.value.length < 10) {
      reasonError.value = 'La raison doit contenir au moins 10 caractères'
      return
    }

    await runLocked('company-suspend', async () => {
      submitting.value = true
      try {
        await superAdminService.suspendEnterprise(enterpriseToAction.value!.id, actionReason.value)
        toast.success('Entreprise suspendue')
        suspendDialog.value = false
        await loadEnterprises()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suspension impossible pour cette entreprise.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleReactivate () {
    if (!enterpriseToAction.value) return

    await runLocked('company-reactivate', async () => {
      submitting.value = true
      try {
        await superAdminService.reactivateEnterprise(enterpriseToAction.value!.id)
        toast.success('Entreprise réactivée')
        reactivateDialog.value = false
        await loadEnterprises()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Réactivation impossible pour cette entreprise.')
      } finally {
        submitting.value = false
      }
    })
  }

  async function handleDelete () {
    if (!enterpriseToAction.value) return

    await runLocked('company-delete', async () => {
      submitting.value = true
      try {
        await superAdminService.deleteEnterprise(enterpriseToAction.value!.id)
        toast.success('Entreprise supprimée')
        deleteDialog.value = false
        await loadEnterprises()
      } catch (error: any) {
        toast.error(error?.response?.data?.message || 'Suppression impossible pour cette entreprise.')
      } finally {
        submitting.value = false
      }
    })
  }

  // Helpers
  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'warning',
      active: 'success',
      approved: 'success',
      rejected: 'error',
      suspended: 'warning',
    }
    return colors[status] || 'default'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      active: 'Active',
      approved: 'Approuvée',
      rejected: 'Rejetée',
      suspended: 'Suspendue',
    }
    return labels[status] || status
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

.companies-table :deep(tbody tr) {
  transition: background-color 0.2s ease;
}

.companies-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}
</style>
