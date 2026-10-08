<template>
  <SuperAdminLayout current-page="dashboard">
    <v-container class="pa-6" fluid>
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold text-primary">Tableau de bord Super Admin</h1>
          <p class="text-subtitle-1 text-grey-darken-1 mt-1">
            Vue d'ensemble de la plateforme BestQHSE
          </p>
        </div>
        <v-tooltip location="top" text="Actualiser">
          <template #activator="{ props: tooltipProps }">
            <v-btn
              v-bind="tooltipProps"
              color="primary"
              icon="mdi-refresh"
              :loading="loading"
              variant="outlined"
              @click="loadDashboardData"
            />
          </template>
        </v-tooltip>
      </div>

      <h2 class="text-h6 font-weight-bold mb-3">Vue d'ensemble</h2>
      <v-row v-if="loading" class="mb-6">
        <v-col v-for="i in 6" :key="i" cols="12" md="4">
          <v-skeleton-loader type="card" />
        </v-col>
      </v-row>

      <v-row v-else class="mb-6" dense>
        <v-col cols="12" md="6">
          <DashboardBentoCard
            :badge="stats.pending_kyc > 0 ? 'Action requise' : ''"
            badge-color="warning"
            clickable
            :gradient="['rgba(255, 152, 0, 0.1)', 'rgba(255, 152, 0, 0.05)']"
            icon="mdi-file-document-alert-outline"
            icon-color="warning"
            label="Validations KYC en attente"
            subtitle="Action requise"
            :value="stats.pending_kyc"
            value-color="rgb(var(--v-theme-warning))"
            variant="large"
            @click="$router.push('/superadmin/kyc')"
          >
            <template #action>
              <v-btn
                v-if="stats.pending_kyc > 0"
                block
                color="warning"
                prepend-icon="mdi-arrow-right"
                variant="tonal"
              >
                Traiter maintenant
              </v-btn>
            </template>
          </DashboardBentoCard>
        </v-col>
        <v-col cols="12" md="6">
          <DashboardBentoCard
            :animate-value="false"
            clickable
            :gradient="['rgba(156, 39, 176, 0.1)', 'rgba(156, 39, 176, 0.05)']"
            icon="mdi-cash-multiple"
            icon-color="purple"
            label="Chiffre d'affaires"
            subtitle="Revenus cumulés"
            :trend="revenueTrendLabel"
            :trend-direction="revenueTrendDirection"
            :value="formatCurrency(stats.total_revenue)"
            value-color="rgb(var(--v-theme-purple))"
            variant="large"
            @click="$router.push('/superadmin/subscriptions')"
          >
            <template #visual>
              <div class="revenue-chart">
                <svg height="80" preserveAspectRatio="none" viewBox="0 0 300 80" width="100%">
                  <path
                    :d="revenueSparkline"
                    fill="none"
                    stroke="rgba(156, 39, 176, 0.3)"
                    stroke-width="2"
                  />
                  <path
                    :d="revenueSparklineFill"
                    fill="url(#gradient-revenue)"
                    opacity="0.2"
                  />
                  <defs>
                    <linearGradient
                      id="gradient-revenue"
                      x1="0%"
                      x2="0%"
                      y1="0%"
                      y2="100%"
                    >
                      <stop offset="0%" stop-color="rgba(156, 39, 176, 0.5)" />
                      <stop offset="100%" stop-color="rgba(156, 39, 176, 0)" />
                    </linearGradient>
                  </defs>
                </svg>
              </div>
            </template>
          </DashboardBentoCard>
        </v-col>
      </v-row>

      <h2 class="text-h6 font-weight-bold mb-3">Indicateurs clés</h2>
      <v-row class="mb-6" dense>
        <v-col cols="12" md="3">
          <DashboardBentoCard
            clickable
            :gradient="['rgba(25, 118, 210, 0.1)', 'rgba(25, 118, 210, 0.05)']"
            icon="mdi-office-building-outline"
            icon-color="primary"
            label="Entreprises"
            :subtitle="`${stats.active_enterprises} actives · ${stats.suspended_enterprises} suspendues`"
            :trend="`+${stats.pending_enterprises}`"
            trend-direction="up"
            :value="stats.total_enterprises"
            value-color="rgb(var(--v-theme-primary))"
            variant="medium"
            @click="$router.push('/superadmin/companies')"
          />
        </v-col>
        <v-col cols="12" md="3">
          <DashboardBentoCard
            clickable
            :gradient="['rgba(76, 175, 80, 0.1)', 'rgba(76, 175, 80, 0.05)']"
            icon="mdi-credit-card-check-outline"
            icon-color="success"
            label="Abonnements actifs"
            :subtitle="`Taux: ${conversionRate}%`"
            :value="stats.active_subscriptions"
            value-color="rgb(var(--v-theme-success))"
            variant="medium"
            @click="$router.push('/superadmin/subscriptions')"
          />
        </v-col>
        <v-col cols="12" md="3">
          <DashboardBentoCard
            clickable
            :gradient="['rgba(3, 169, 244, 0.1)', 'rgba(3, 169, 244, 0.05)']"
            icon="mdi-account-multiple-plus"
            icon-color="info"
            label="Nouvelles inscriptions"
            subtitle="Cette semaine"
            :value="stats.weekly.new_registrations"
            value-color="rgb(var(--v-theme-info))"
            variant="medium"
            @click="$router.push('/superadmin/kyc')"
          />
        </v-col>
        <v-col cols="12" md="3">
          <DashboardBentoCard
            clickable
            :gradient="['rgba(76, 175, 80, 0.1)', 'rgba(76, 175, 80, 0.05)']"
            icon="mdi-check-decagram"
            icon-color="success"
            label="Validations KYC"
            subtitle="Cette semaine"
            :value="stats.weekly.kyc_validations"
            value-color="rgb(var(--v-theme-success))"
            variant="medium"
            @click="$router.push('/superadmin/kyc')"
          />
        </v-col>
      </v-row>

      <!-- Recent Pending Enterprises Table -->
      <v-card class="mt-6" elevation="2">
        <v-card-title class="pa-6 d-flex justify-space-between align-center">
          <div class="d-flex align-center">
            <v-icon class="mr-3" color="warning" size="24">
              mdi-file-document-alert-outline
            </v-icon>
            <span class="text-h6 font-weight-bold">Entreprises en attente de validation</span>
          </div>
          <v-btn
            append-icon="mdi-arrow-right"
            color="primary"
            to="/superadmin/kyc"
            variant="text"
          >
            Tout voir
          </v-btn>
        </v-card-title>
        <v-divider />

        <div v-if="loading" class="pa-6">
          <v-skeleton-loader type="table-row@3" />
        </div>

        <div v-else-if="pendingEnterprises.length === 0" class="pa-6">
          <EmptyState
            description="Toutes les validations KYC sont à jour."
            icon="mdi-check-circle-outline"
            title="Aucune entreprise en attente"
          />
        </div>

        <v-table v-else hover>
          <thead>
            <tr>
              <th class="text-left font-weight-bold">Entreprise</th>
              <th class="text-left font-weight-bold">RCCM</th>
              <th class="text-left font-weight-bold">Date</th>
              <th class="text-left font-weight-bold">Statut</th>
              <th class="text-center font-weight-bold">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="enterprise in pendingEnterprises"
              :key="enterprise.id"
              class="table-row"
            >
              <td>
                <div class="d-flex align-center py-3">
                  <v-avatar class="mr-3" color="primary" size="40" variant="tonal">
                    <v-icon size="20">mdi-office-building</v-icon>
                  </v-avatar>
                  <span class="font-weight-medium">{{ enterprise.name || 'N/A' }}</span>
                </div>
              </td>
              <td class="text-medium-emphasis">
                {{ enterprise.registration_number || 'N/A' }}
              </td>
              <td>
                <span class="text-caption text-disabled">{{ formatDate(enterprise.created_at) }}</span>
              </td>
              <td>
                <v-chip
                  color="warning"
                  size="x-small"
                  variant="tonal"
                >
                  En attente
                </v-chip>
              </td>
              <td class="text-center">
                <v-tooltip location="top" text="Voir KYC">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-bind="tooltipProps"
                      color="primary"
                      icon="mdi-eye-outline"
                      size="small"
                      :to="`/superadmin/kyc/${enterprise.id}`"
                      variant="tonal"
                    />
                  </template>
                </v-tooltip>
              </td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
    </v-container>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import type { Enterprise } from '@/types/api'
  import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from '@/composables/useToast'
  import DashboardBentoCard from '@/modules/superadmin/components/DashboardBentoCard.vue'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  interface DashboardStats {
    total_enterprises: number
    pending_kyc: number
    active_subscriptions: number
    total_revenue: number
    revenue_series?: number[]
    revenue_trend_pct?: number
    pending_enterprises: number
    active_enterprises: number
    suspended_enterprises: number
    rejected_enterprises: number
    weekly: {
      new_registrations: number
      kyc_validations: number
    }
  }

  const loading = ref(true)
  const route = useRoute()
  const router = useRouter()
  const stats = ref<DashboardStats>({
    total_enterprises: 0,
    pending_kyc: 0,
    active_subscriptions: 0,
    total_revenue: 0,
    pending_enterprises: 0,
    active_enterprises: 0,
    suspended_enterprises: 0,
    rejected_enterprises: 0,
    weekly: {
      new_registrations: 0,
      kyc_validations: 0,
    },
  })
  const pendingEnterprises = ref<Enterprise[]>([])
  const toast = useToast()

  const conversionRate = computed(() => {
    if (stats.value.total_enterprises === 0) return 0
    return ((stats.value.active_subscriptions / stats.value.total_enterprises) * 100).toFixed(0)
  })

  const revenueTrendLabel = computed(() => {
    const trend = stats.value.revenue_trend_pct ?? 0
    const sign = trend > 0 ? '+' : ''
    return `${sign}${trend}%`
  })

  const revenueTrendDirection = computed(() => {
    const trend = stats.value.revenue_trend_pct ?? 0
    if (trend > 0) return 'up'
    if (trend < 0) return 'down'
    return 'neutral'
  })

  // Generate sparkline path for revenue chart
  const revenueSparkline = computed(() => {
    const points = stats.value.revenue_series && stats.value.revenue_series.length > 0
      ? stats.value.revenue_series
      : Array.from({ length: 10 }, () => 0)
    const width = 300
    const height = 80
    const step = width / (points.length - 1)
    const max = Math.max(...points, 1)

    return points.map((point, index) => {
      const x = index * step
      const y = height - ((point / max) * height)
      return `${index === 0 ? 'M' : 'L'} ${x} ${y}`
    }).join(' ')
  })

  const revenueSparklineFill = computed(() => {
    return `${revenueSparkline.value} L 300 80 L 0 80 Z`
  })

  async function loadDashboardData () {
    loading.value = true
    try {
      const dashboardStats = await superAdminService.getDashboardStats()

      stats.value = {
        total_enterprises: dashboardStats.companies?.total || 0,
        pending_kyc: dashboardStats.kyc?.pending || 0,
        active_subscriptions: dashboardStats.subscriptions?.active || 0,
        total_revenue: dashboardStats.revenue?.total || 0,
        revenue_series: dashboardStats.revenue?.series || [],
        revenue_trend_pct: dashboardStats.revenue?.trend_pct || 0,
        pending_enterprises: dashboardStats.kyc?.pending || 0,
        active_enterprises: dashboardStats.companies?.active || 0,
        suspended_enterprises: dashboardStats.companies?.suspended || 0,
        rejected_enterprises: dashboardStats.kyc?.rejected || 0,
        weekly: dashboardStats.weekly || { new_registrations: 0, kyc_validations: 0 },
      }

      const response = await superAdminService.getEnterprises({
        status: 'pending',
        per_page: 5,
        page: 1,
      })

      pendingEnterprises.value = response.data || []
    } catch {
      toast.error('Erreur lors du chargement du tableau de bord')
    } finally {
      loading.value = false
    }
  }

  function formatCurrency (value: number): string {
    return new Intl.NumberFormat('fr-FR', {
      style: 'decimal',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value) + ' FCFA'
  }

  function formatDate (dateString: string | undefined): string {
    if (!dateString) return 'N/A'

    const date = new Date(dateString)
    if (Number.isNaN(date.getTime())) return 'N/A'

    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(date)
  }

  onMounted(() => {
    loadDashboardData()
    if (typeof window !== 'undefined') {
      window.addEventListener('superadmin-kyc-updated', loadDashboardData)
    }
  })

  watch(() => route.query.refresh, async value => {
    if (!value) return
    await loadDashboardData()
    router.replace({ path: route.path, query: { ...route.query, refresh: undefined } })
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('superadmin-kyc-updated', loadDashboardData)
    }
  })
</script>

<style scoped lang="scss">
.revenue-chart {
  padding: 12px 16px 0;
}

.table-row {
  transition: background-color 0.2s ease;

  &:hover {
    background-color: rgba(var(--v-theme-primary), 0.04);
  }
}
</style>
