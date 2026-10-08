<template>
  <SuperAdminLayout current-page="kyc">
    <v-container class="pa-6" fluid>
      <!-- Header Section -->
      <v-row class="mb-8">
        <v-col cols="12" md="8">
          <div class="d-flex align-center mb-2">
            <v-icon class="mr-3" color="warning" size="32">mdi-file-document-check-outline</v-icon>
            <h1 class="text-h4 font-weight-bold text-primary">
              Validations KYC
            </h1>
          </div>
          <p class="text-subtitle-1 text-grey-darken-1 ml-11">
            Validez ou rejetez les demandes d'inscription des entreprises
          </p>
        </v-col>
        <v-col class="d-flex align-end justify-end" cols="12" md="4">
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
              @click="refreshData"
            >
              Actualiser
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col
          v-for="stat in stats"
          :key="stat.id"
          cols="12"
          md="3"
          sm="6"
        >
          <v-skeleton-loader v-if="loading && !statsLoaded" type="card" />
          <StatCard
            v-else
            :color="stat.color"
            :icon="stat.icon"
            :label="stat.label"
            :value="stat.value"
          />
        </v-col>
      </v-row>

      <!-- Filters & Search -->
      <v-card class="mb-6 sa-card sa-filters" elevation="2">
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="5">
              <v-text-field
                v-model="searchQuery"
                clearable
                density="comfortable"
                hide-details
                placeholder="Rechercher par nom, email ou RCCM..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="statusFilter"
                clearable
                density="comfortable"
                hide-details
                :items="statusOptions"
                label="Statut KYC"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-btn
                block
                :color="hasActiveFilters ? 'primary' : 'grey'"
                :disabled="!hasActiveFilters"
                height="40"
                prepend-icon="mdi-filter-off-outline"
                :variant="hasActiveFilters ? 'flat' : 'outlined'"
                @click="clearFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- KYC Table -->
      <v-card v-if="viewMode === 'table'" class="sa-card sa-table-card" elevation="2">
        <v-card-title class="pa-6 d-flex justify-space-between align-center">
          <div class="d-flex align-center">
            <v-icon class="mr-2" color="primary">mdi-format-list-bulleted</v-icon>
            <span class="font-weight-bold">Liste des entreprises</span>
            <v-chip
              v-if="!loading"
              class="ml-3"
              color="primary"
              size="x-small"
              variant="tonal"
            >
              {{ pagination.total }}
            </v-chip>
          </div>
        </v-card-title>
        <v-divider />

        <!-- Loading State -->
        <v-skeleton-loader v-if="loading && !dataLoaded" type="table" />

        <!-- Table Content -->
        <v-table v-else class="sa-table" hover>
          <thead>
            <tr>
              <th class="text-left font-weight-bold">Entreprise</th>
              <th class="text-left font-weight-bold">RCCM</th>
              <th class="text-left font-weight-bold">Email</th>
              <th class="text-left font-weight-bold">Secteur</th>
              <th class="text-left font-weight-bold">Date de création</th>
              <th class="text-left font-weight-bold">Statut KYC</th>
              <th class="text-center font-weight-bold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Empty State -->
            <tr v-if="enterprises.length === 0">
              <td class="text-center py-12" colspan="7">
                <EmptyState
                  :description="emptyStateDescription"
                  icon="mdi-office-building-outline"
                  :title="emptyStateTitle"
                />
              </td>
            </tr>

            <!-- Data Rows -->
            <tr v-for="enterprise in displayedEnterprises" :key="enterprise.id" class="table-row">
              <td>
                <div class="d-flex align-center py-3">
                  <v-avatar class="mr-3" color="primary" size="40" variant="tonal">
                    <v-img v-if="enterprise.logo_path" :src="enterprise.logo_path" />
                    <v-icon v-else size="20">mdi-office-building</v-icon>
                  </v-avatar>
                  <div>
                    <p class="font-weight-medium mb-0">{{ enterprise.name }}</p>
                    <p v-if="enterprise.sites && enterprise.sites.length > 0" class="text-caption text-grey mb-0">
                      {{ enterprise.sites.length }} site{{ enterprise.sites.length > 1 ? 's' : '' }}
                    </p>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-body-2">{{ enterprise.registration_number || 'N/A' }}</span>
              </td>
              <td>
                <span class="text-body-2">{{ enterprise.email }}</span>
              </td>
              <td>
                <span class="text-body-2">{{ enterprise.field || 'Non spécifié' }}</span>
              </td>
              <td>
                <span class="text-caption text-grey">{{ formatDate(enterprise.created_at) }}</span>
              </td>
              <td>
                <v-chip
                  :color="getStatusColor(resolveKycStatus(enterprise))"
                  size="x-small"
                  variant="tonal"
                >
                  <v-icon size="14" start>{{ getStatusIcon(resolveKycStatus(enterprise)) }}</v-icon>
                  {{ getStatusLabel(resolveKycStatus(enterprise)) }}
                </v-chip>
                <v-chip
                  v-if="resolveKycStatus(enterprise) === 'pending'"
                  class="ml-2"
                  color="warning"
                  size="x-small"
                  variant="tonal"
                >
                  Action requise
                </v-chip>
              </td>
              <td class="text-center">
                <div class="d-flex justify-center ga-2">
                  <v-tooltip location="top" text="Voir KYC">
                    <template #activator="{ props: tooltipProps }">
                      <v-btn
                        v-bind="tooltipProps"
                        color="primary"
                        icon="mdi-shield-account-outline"
                        size="small"
                        :to="`/superadmin/kyc/${enterprise.id}`"
                        variant="flat"
                      />
                    </template>
                  </v-tooltip>
                  <v-tooltip location="top" text="Voir entreprise">
                    <template #activator="{ props: tooltipProps }">
                      <v-btn
                        v-bind="tooltipProps"
                        color="secondary"
                        icon="mdi-office-building-outline"
                        size="small"
                        :to="`/superadmin/companies/${enterprise.id}`"
                        variant="text"
                      />
                    </template>
                  </v-tooltip>
                </div>
              </td>
            </tr>
          </tbody>
        </v-table>

        <!-- Pagination -->
        <v-divider />
        <div v-if="enterprises.length > 0" class="pa-4 d-flex justify-space-between align-center">
          <p class="text-body-2 text-medium-emphasis">
            Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} résultats
          </p>
          <v-pagination
            v-model="currentPage"
            density="comfortable"
            :length="pagination.last_page"
            :total-visible="7"
          />
        </div>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="enterprises.length === 0" cols="12">
          <EmptyState
            :description="emptyStateDescription"
            icon="mdi-office-building-outline"
            :title="emptyStateTitle"
          />
        </v-col>
        <v-col
          v-for="enterprise in displayedEnterprises"
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
              <v-chip
                :color="getStatusColor(resolveKycStatus(enterprise))"
                size="x-small"
                variant="tonal"
              >
                <v-icon size="14" start>{{ getStatusIcon(resolveKycStatus(enterprise)) }}</v-icon>
                {{ getStatusLabel(resolveKycStatus(enterprise)) }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-1">RCCM: {{ enterprise.registration_number || 'N/A' }}</div>
              <div class="text-body-2 text-medium-emphasis">Secteur: {{ enterprise.field || 'Non spécifié' }}</div>
              <div class="text-caption text-medium-emphasis mt-2">Créée le {{ formatDate(enterprise.created_at) }}</div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Voir KYC">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    color="primary"
                    icon="mdi-shield-account-outline"
                    :to="`/superadmin/kyc/${enterprise.id}`"
                    variant="flat"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Voir entreprise">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    color="secondary"
                    icon="mdi-office-building-outline"
                    :to="`/superadmin/companies/${enterprise.id}`"
                    variant="text"
                  />
                </template>
              </v-tooltip>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise, PaginatedResponse } from '@/types/api'
import { computed, onActivated, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  // Router
  const _router = useRouter()

  // State
  const loading = ref(false)
  const dataLoaded = ref(false)
  const statsLoaded = ref(false)
  const enterprises = ref<Enterprise[]>([])
  const toast = useToast()
  const searchQuery = ref('')
  const statusFilter = ref<'pending' | 'approved' | 'rejected' | null>(null)
  const currentPage = ref(1)
  const viewMode = ref<'table' | 'cards'>(localStorage.getItem('sa_view_kyc') === 'cards' ? 'cards' : 'table')

  // Pagination
  const pagination = ref({
    current_page: 1,
    from: 0,
    last_page: 1,
    per_page: 10,
    to: 0,
    total: 0,
  })

  // Stats
  const kycStats = ref({
    total: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
  })

  const stats = computed(() => [
    {
      id: 'pending',
      label: 'KYC en Attente',
      value: kycStats.value.pending,
      color: 'warning',
      icon: 'mdi-clock-alert-outline',
    },
    {
      id: 'approved',
      label: 'KYC Actives',
      value: kycStats.value.approved,
      color: 'success',
      icon: 'mdi-check-circle-outline',
    },
    {
      id: 'rejected',
      label: 'KYC Rejetées',
      value: kycStats.value.rejected,
      color: 'error',
      icon: 'mdi-close-circle-outline',
    },
    {
      id: 'total',
      label: 'Total Entreprises',
      value: kycStats.value.total,
      color: 'info',
      icon: 'mdi-office-building-outline',
    },
  ])

  // Status Options
  const statusOptions = [
    { title: 'Toutes', value: null },
    { title: 'En attente', value: 'pending' },
    { title: 'Approuvées', value: 'approved' },
    { title: 'Rejetées', value: 'rejected' },
  ]

  // Debounce timer for search
  let searchTimeout: ReturnType<typeof setTimeout> | null = null

  // Fetch enterprises
  async function fetchEnterprises () {
    loading.value = true
    try {
      const response: PaginatedResponse<Enterprise> = await superAdminService.getEnterprises({
        page: currentPage.value,
        per_page: 10,
        approval_status: statusFilter.value || undefined,
        search: searchQuery.value?.trim() || undefined,
        kyc_priority: true,
      })

      enterprises.value = response.data
      pagination.value = response.meta
      dataLoaded.value = true
    } catch {
      toast.error('Erreur lors du chargement des entreprises')
      enterprises.value = []
    } finally {
      loading.value = false
    }
  }

  // Calculate stats
  async function calculateStats () {
    try {
      const statsResponse = await superAdminService.getDashboardStats()
      kycStats.value = {
        total: statsResponse.kyc?.total || 0,
        pending: statsResponse.kyc?.pending || 0,
        approved: statsResponse.kyc?.approved || 0,
        rejected: statsResponse.kyc?.rejected || 0,
      }
      statsLoaded.value = true
    } catch {
      toast.error('Erreur lors du chargement des statistiques')
    }
  }

  const displayedEnterprises = computed(() => enterprises.value)

  const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value?.trim()) || Boolean(statusFilter.value)
  })

  const emptyStateTitle = computed(() => {
    if (searchQuery.value?.trim()) {
      return 'Aucun résultat'
    }
    if (statusFilter.value) {
      return 'Aucune entreprise pour ce statut'
    }
    return 'Aucune demande KYC'
  })

  const emptyStateDescription = computed(() => {
    if (searchQuery.value?.trim()) {
      return `Aucune entreprise ne correspond à "${searchQuery.value.trim()}".`
    }
    if (statusFilter.value) {
      return 'Essayez de modifier le statut KYC sélectionné.'
    }
    return 'Aucune demande KYC pour le moment.'
  })

  // Watch for search changes (debounced)
  watch(searchQuery, () => {
    if (searchTimeout) {
      clearTimeout(searchTimeout)
    }

    searchTimeout = setTimeout(() => {
      currentPage.value = 1
      fetchEnterprises()
    }, 500)
  })

  // Watch for status filter changes
  watch(statusFilter, () => {
    currentPage.value = 1
    fetchEnterprises()
  })

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_kyc', mode)
  })

  // Watch for page changes
  watch(currentPage, () => {
    fetchEnterprises()
  })

  // Refresh data
  function refreshData () {
    fetchEnterprises()
    calculateStats()
  }

  // Clear filters
  function clearFilters () {
    searchQuery.value = ''
    statusFilter.value = null
    currentPage.value = 1
    fetchEnterprises()
  }

  // Helper functions
  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'orange',
      approved: 'success',
      rejected: 'error',
      suspended: 'warning',
    }
    return colors[status] || 'grey'
  }

  function getStatusIcon (status: string): string {
    const icons: Record<string, string> = {
      pending: 'mdi-clock-outline',
      approved: 'mdi-check-circle',
      rejected: 'mdi-close-circle',
      suspended: 'mdi-pause-circle',
    }
    return icons[status] || 'mdi-help-circle'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      approved: 'Approuvée',
      rejected: 'Rejetée',
      suspended: 'Suspendue',
    }
    return labels[status] || status
  }

  function resolveKycStatus (enterprise: Enterprise): string {
    const approval = (enterprise as any).approval_status
    if (approval) return approval
    if (enterprise.status === 'active') return 'approved'
    if (enterprise.status === 'rejected') return 'rejected'
    return 'pending'
  }

  function formatDate (dateString: string | undefined): string {
    if (!dateString) return 'N/A'

    try {
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      })
    } catch {
      return 'N/A'
    }
  }

  // Initialize
  onMounted(() => {
    const refreshPending = localStorage.getItem('superadmin_kyc_refresh_pending')
    fetchEnterprises()
    calculateStats()
    if (refreshPending === '1') {
      localStorage.removeItem('superadmin_kyc_refresh_pending')
      refreshData()
    }
    if (typeof window !== 'undefined') {
      window.addEventListener('superadmin-kyc-updated', refreshData)
    }
  })

  onActivated(() => {
    const refreshPending = localStorage.getItem('superadmin_kyc_refresh_pending')
    if (refreshPending === '1') {
      localStorage.removeItem('superadmin_kyc_refresh_pending')
      refreshData()
    }
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('superadmin-kyc-updated', refreshData)
    }
  })
</script>

<style scoped>
.stat-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.table-row {
  transition: background-color 0.2s ease;
}

.table-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.04);
}

:deep(.v-text-field .v-field),
:deep(.v-select .v-field) {
  border-radius: 12px;
}

:deep(.v-btn) {
  border-radius: 12px;
}
</style>
