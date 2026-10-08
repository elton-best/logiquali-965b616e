<template>
  <ClientALayout current-page="dashboard">
    <v-container class="dashboard-container" fluid>
      <DashboardHero
        :loading="loading"
        :user-name="userName"
        :weather="weatherInfo"
        @refresh="refreshData"
      />

      <DashboardFilters
        :allow-global="canSelectGlobalScope"
        class="mb-6"
        :filters="filters"
        :sites="availableSites"
        @export="exportDashboard"
        @update:filters="updateFilters"
      />

      <template v-if="loading && !apiStats">
        <v-row class="mb-6">
          <v-col
            v-for="i in 6"
            :key="i"
            cols="12"
            lg="4"
            sm="6"
          >
            <AppWidgetSkeleton />
          </v-col>
        </v-row>
      </template>

      <v-alert
        v-else-if="error"
        class="mb-6"
        rounded="lg"
        type="error"
        variant="tonal"
      >
        <v-alert-title>Erreur de chargement</v-alert-title>
        {{ error }}
        <template #append>
          <v-btn
            color="error"
            variant="text"
            @click="loadDashboardData"
          >Réessayer</v-btn>
        </template>
      </v-alert>

      <template v-else>
        <v-alert
          v-if="dashboardWarnings.length > 0"
          class="mb-4"
          type="warning"
          variant="tonal"
        >
          <div
            v-for="warning in dashboardWarnings"
            :key="warning"
            class="text-body-2"
          >
            {{ warning }}
          </div>
        </v-alert>

        <!-- Bouton Personnaliser -->
        <v-row class="mb-4">
          <v-col cols="12">
            <v-btn
              :color="editMode ? 'success' : 'primary'"
              :prepend-icon="editMode ? 'mdi-check' : 'mdi-pencil'"
              @click="toggleEditMode"
            >
              {{ editMode ? "Terminer" : "Personnaliser le dashboard" }}
            </v-btn>
          </v-col>
        </v-row>

        <DraggableGrid
          v-model:widgets="dashboardWidgets"
          :edit-mode="editMode"
          @save="saveLayout"
        >
          <template #default="{ widget }">
            <!-- Stats Cards -->
            <StatsCards
              v-if="widget.id === 'stats'"
              :stats="stats"
              @stat-click="handleStatClick"
            />

            <!-- Widgets Row -->
            <DashboardWidgetsRow
              v-else-if="widget.id === 'widgets'"
              :can-view-leadership-org-chart="canViewLeadershipOrgChart"
              :can-view-leadership-policy="canViewLeadershipPolicy"
              :can-view-leadership-roles="canViewLeadershipRoles"
              :employees-with-job-description="employeesWithJobDescription"
              :job-description-progress="jobDescriptionProgress"
              :leadership-quick-items-available="leadershipQuickItemsAvailable"
              :org-chart-color="orgChartColor"
              :org-chart-status="orgChartStatus"
              :policy-status-color="policyStatusColor"
              :policy-status-icon="policyStatusIcon"
              :policy-status-label="policyStatusLabel"
              :policy-status-text="policyStatusText"
              :priority-tasks="priorityTasks"
              :quick-actions="quickActions"
              :show-tasks="isModuleAccessible('leadership')"
              :total-employees="totalEmployees"
              @quick-action="handleQuickAction"
            />
          </template>
        </DraggableGrid>

        <v-row class="mb-6">
          <v-col cols="12">
            <v-card class="dashboard-panel" elevation="0" rounded="lg">
              <v-card-title class="d-flex align-center">
                <v-icon class="mr-2" color="primary">mdi-source-branch</v-icon>
                <span class="text-subtitle-1 font-weight-bold">Mes actions par processus</span>
              </v-card-title>
              <v-divider />
              <v-card-text>
                <v-list
                  v-if="collaboratorProcessLoad.length > 0"
                  class="py-0"
                  density="comfortable"
                >
                  <v-list-item
                    v-for="item in collaboratorProcessLoad"
                    :key="`${item.process_id ?? 'none'}-${item.process_label}`"
                    :class="{ 'cursor-pointer': Boolean(item.process_id) }"
                    @click="openProcessActions(item.process_id)"
                  >
                    <v-list-item-title class="text-body-2 font-weight-medium">
                      {{ item.process_label }}
                      <span
                        v-if="item.process_code"
                        class="text-caption text-medium-emphasis"
                      >({{ item.process_code }})</span>
                    </v-list-item-title>
                    <v-list-item-subtitle>
                      Ouvertes: {{ item.open_count }} · En retard: {{ item.overdue_count }}
                    </v-list-item-subtitle>
                    <template #append>
                      <v-chip color="primary" size="small" variant="tonal">
                        {{ item.total_count }}
                      </v-chip>
                    </template>
                  </v-list-item>
                </v-list>
                <div v-else class="text-caption text-medium-emphasis">
                  Aucune action collaborateur affectée par processus.
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <PeriodComparison
          v-if="filters.compareMode"
          class="mb-6"
          :metrics="comparisonMetrics"
        />

        <v-row class="mb-6">
          <v-col cols="12" lg="8">
            <div class="dashboard-panel h-100">
              <ChartWidget
                chart-type="line"
                chip-color="primary"
                :data="activityChartData"
                icon="mdi-chart-line"
                icon-color="primary"
                subtitle="30 derniers jours"
                title="Évolution des activités"
                @chart-click="handleActivityChartClick"
              />
            </div>
          </v-col>
          <v-col cols="12" lg="4">
            <div class="dashboard-panel h-100">
              <ChartWidget
                chart-type="doughnut"
                chip-color="success"
                :data="typeDistributionData"
                icon="mdi-chart-donut"
                icon-color="success"
                subtitle="Ce mois"
                title="Répartition par type"
                @chart-click="handleTypeDistributionChartClick"
              />
            </div>
          </v-col>
        </v-row>

        <v-row class="mb-6">
          <v-col cols="12">
            <ProcessDashboardWidget />
          </v-col>
        </v-row>

        <v-row class="mb-6">
          <v-col cols="12">
            <RecentActivitiesCard
              :recent-activities="recentActivities"
              @select="handleRecentActivityClick"
            />
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12">
            <div class="dashboard-panel h-100">
              <ChartWidget
                chart-type="bar"
                chip-color="info"
                :data="performanceChartData"
                icon="mdi-chart-bar"
                icon-color="info"
                subtitle="Comparaison sur 6 mois"
                title="Performance mensuelle"
                @chart-click="handlePerformanceChartClick"
              />
            </div>
          </v-col>
        </v-row>
      </template>

      <v-dialog v-model="exportDialogOpen" max-width="520">
        <v-card rounded="xl">
          <v-card-title class="text-h6">Exporter le dashboard</v-card-title>
          <v-card-text>
            <v-radio-group v-model="exportScope" label="Périmètre d'export">
              <v-radio
                v-if="canSelectGlobalScope"
                label="Global entreprise (tous les sites)"
                value="enterprise"
              />
              <v-radio label="Un site spécifique" value="site" />
            </v-radio-group>

            <v-select
              v-if="exportScope === 'site'"
              v-model="exportSiteId"
              density="comfortable"
              :items="availableSites"
              label="Site"
              variant="outlined"
            />
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn
              variant="text"
              @click="exportDialogOpen = false"
            >Annuler</v-btn>
            <v-btn
              color="primary"
              @click="confirmExportDashboard"
            >Exporter</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { CollaboratorActionSummary, DashboardStats } from '@/services/dashboardService'
  import { computed, onMounted, onUnmounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import { AppWidgetSkeleton } from '@/components/common'
  import ProcessDashboardWidget from '@/modules/clienta/components/processes/ProcessDashboardWidget.vue'
  import { useSubscribedModules } from '@/modules/clienta/composables/useSubscribedModules'
  import {
    DASHBOARD_QUICK_ACTIONS,
    DASHBOARD_STAT_ROUTES,
  } from '@/modules/clienta/constants/dashboardNavigation'
  import DashboardWidgetsRow from '@/modules/clienta/pages/dashboard/components/DashboardWidgetsRow.vue'
  import RecentActivitiesCard from '@/modules/clienta/pages/dashboard/components/RecentActivitiesCard.vue'
  import StatsCards from '@/modules/clienta/pages/dashboard/components/StatsCards.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { dashboardService } from '@/services/dashboardService'
  import { useAuthStore } from '@/stores/auth'
  import { useSiteContextStore } from '@/stores/siteContext'
  import { isEnterpriseAdminUser, routePathExists } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'
  import ClientALayout from '../components/ClientALayout.vue'
  import ChartWidget from '../components/dashboard/ChartWidget.vue'
  import DashboardFilters from '../components/dashboard/DashboardFilters.vue'
  import PeriodComparison from '../components/dashboard/PeriodComparison.vue'
  import DashboardHero from '../components/DashboardHero.vue'
  import DraggableGrid from '../components/DraggableGrid.vue'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const siteContextStore = useSiteContextStore()
  const { isModuleAccessible } = useSubscribedModules()

  const loading = ref(false)
  const error = ref<string | null>(null)
  const dashboardWarnings = ref<string[]>([])
  const apiStats = ref<DashboardStats | null>(null)
  const collaboratorSummary = ref<CollaboratorActionSummary | null>(null)
  const exportDialogOpen = ref(false)
  const exportScope = ref<'enterprise' | 'site'>('site')
  const exportSiteId = ref<string | number | null>(null)
  const editMode = ref(false)
  const dashboardWidgets = ref([
    { id: 'stats', order: 0 },
    { id: 'widgets', order: 1 },
  ])

  const filters = ref({
    period: 'month',
    site: null as string | number | null,
    compareMode: false,
  })

  const availableSites = computed(() => siteContextStore.availableSites)

  let refreshInterval: ReturnType<typeof setInterval> | null = null
  let dashboardRequestPromise: Promise<void> | null = null
  let layoutRequestPromise: Promise<void> | null = null

  const user = computed(() => {
    try {
      return JSON.parse(localStorage.getItem('user') || '{}')
    } catch {
      return {}
    }
  })

  const userName = computed(
    () => user.value.name || user.value.username || 'Utilisateur',
  )
  const weatherInfo = computed(() => '22°C Ensoleillé')
  const canSelectGlobalScope = computed(() => {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin') return true

    const roleNames = Array.isArray(currentUser?.role_names)
      ? currentUser.role_names
      : []
    const accessRole
      = typeof currentUser?.access_role === 'string' ? currentUser.access_role : ''
    const roles = Array.isArray(currentUser?.roles) ? currentUser.roles : []

    return (
      roleNames.includes('admin_entreprise')
      || accessRole === 'admin_entreprise'
      || roles.some((role: any) => {
        if (typeof role === 'string') return role === 'admin_entreprise'
        if (typeof role?.name === 'string')
          return role.name === 'admin_entreprise'
        if (typeof role?.attributes?.name === 'string')
          return role.attributes.name === 'admin_entreprise'
        return false
      })
    )
  })

  const stats = computed(() => {
    if (!apiStats.value) return []
    const data = apiStats.value
    const baseStats = [
      {
        id: 1,
        title: 'Sites actifs',
        value: String(data.totalSites || 0),
        variant: 'primary' as const,
        icon: 'Building',
        trend: 1,
      },
      {
        id: 2,
        title: 'Utilisateurs',
        value: String(data.totalUsers || 0),
        variant: 'success' as const,
        icon: 'Users',
        trend: 0,
      },
      {
        id: 3,
        title: 'Actions en retard',
        value: String(data.overdueActions || 0),
        variant: 'warning' as const,
        icon: 'Clock',
        trend: data.overdueActions > 0 ? -data.overdueActions : 0,
      },
      {
        id: 4,
        title: 'Non-conformités actives',
        value: String(data.activeNonConformities || 0),
        variant: 'error' as const,
        icon: 'AlertTriangle',
        trend: data.activeNonConformities > 0 ? -data.activeNonConformities : 0,
      },
      {
        id: 5,
        title: 'Audits à venir',
        value: String(data.upcomingAudits || 0),
        variant: 'audit' as const,
        icon: 'Calendar',
        trend: data.upcomingAudits || 0,
      },
      {
        id: 6,
        title: 'Processus actifs',
        value: String(data.totalProcesses || 0),
        variant: 'info' as const,
        icon: 'Info',
        trend: 0,
      },
    ]

    return baseStats.filter(stat => {
      const target = DASHBOARD_STAT_ROUTES[stat.id]
      if (!target) {
        return false
      }
      return canNavigate(target.route, target.permissions)
    })
  })

  const recentActivities = computed(() => {
    if (!apiStats.value?.recentActivities) return []

    const activityNavigation: Record<
      string,
      { route: string, permissions: string[] }
    > = {
      document: {
        route: '/company/documents',
        permissions: ['support.documents.read'],
      },
      nonconformity: {
        route: '/company/nonconformities',
        permissions: ['amelioration.non_conformites.read'],
      },
      audit: {
        route: '/company/audits',
        permissions: ['evaluation.audits.read'],
      },
      user: {
        route: '/company/users',
        permissions: ['users.read'],
      },
      action: {
        route: '/company/actions',
        permissions: ['amelioration.non_conformites.read'],
      },
    }

    return apiStats.value.recentActivities.slice(0, 5).map(activity => {
      const fallbackConfig = {
        icon: 'mdi-file-check',
        color: 'success',
        chipColor: 'primary',
      } as const
      const typeConfig: Record<
        string,
        { icon: string, color: string, chipColor: string }
      > = {
        document: {
          icon: 'mdi-file-check',
          color: 'success',
          chipColor: 'primary',
        },
        nonconformity: {
          icon: 'mdi-alert-circle',
          color: 'error',
          chipColor: 'error',
        },
        audit: { icon: 'mdi-calendar-plus', color: 'info', chipColor: 'info' },
        user: {
          icon: 'mdi-account-plus',
          color: 'success',
          chipColor: 'success',
        },
        action: {
          icon: 'mdi-check-circle',
          color: 'success',
          chipColor: 'warning',
        },
      }
      const config = typeConfig[activity.type] || fallbackConfig
      const navigationTarget = activityNavigation[activity.type]
      const canOpen = navigationTarget
        ? canNavigate(navigationTarget.route, navigationTarget.permissions)
        : false

      return {
        id: activity.id,
        title: activity.description || activity.name || 'Activité',
        time: getRelativeTime(activity.createdAt || activity.created_at || ''),
        category: getCategoryLabel(activity.type),
        icon: config.icon,
        color: config.color,
        chipColor: config.chipColor,
        route: navigationTarget?.route || null,
        canOpen,
      }
    }).filter(activity => activity.canOpen)
  })

  const priorityTasks = computed(() => {
    if (!apiStats.value) return []
    const tasks: Array<{
      id: number
      title: string
      subtitle: string
      count: number
      icon: string
      color: string
      permissions: string[]
    }> = []

    if ((collaboratorSummary.value?.stats.overdue_count || 0) > 0) {
      tasks.push({
        id: 101,
        title: 'Mes actions en retard',
        subtitle: 'Suivi collaborateur global',
        count: collaboratorSummary.value?.stats.overdue_count || 0,
        icon: 'mdi-account-clock',
        color: 'error',
        permissions: ['amelioration.non_conformites.read'],
      })
    }

    if ((collaboratorSummary.value?.stats.open_count || 0) > 0) {
      tasks.push({
        id: 102,
        title: 'Mes actions à mener',
        subtitle: 'Toutes sections confondues',
        count: collaboratorSummary.value?.stats.open_count || 0,
        icon: 'mdi-account-check-outline',
        color: 'primary',
        permissions: ['amelioration.non_conformites.read'],
      })
    }

    if (apiStats.value.overdueActions > 0) {
      tasks.push({
        id: 1,
        title: 'Actions en retard',
        subtitle: 'Actions correctives requises',
        count: apiStats.value.overdueActions,
        icon: 'mdi-clock-alert',
        color: 'warning',
        permissions: ['amelioration.non_conformites.read'],
      })
    }
    if (apiStats.value.activeNonConformities > 0) {
      tasks.push({
        id: 2,
        title: 'NC à traiter',
        subtitle: 'Non-conformités actives',
        count: apiStats.value.activeNonConformities,
        icon: 'mdi-alert-octagon',
        color: 'error',
        permissions: ['amelioration.non_conformites.read'],
      })
    }
    if (apiStats.value.upcomingAudits > 0) {
      tasks.push({
        id: 3,
        title: 'Audits à venir',
        subtitle: 'Préparation nécessaire',
        count: apiStats.value.upcomingAudits,
        icon: 'mdi-calendar-clock',
        color: 'info',
        permissions: ['evaluation.audits.read'],
      })
    }
    return tasks.filter(task => canAccess(task.permissions))
  })

  const collaboratorProcessLoad = computed(() => {
    return collaboratorSummary.value?.by_process || []
  })

  const comparisonMetrics = computed(() => [
    {
      id: 'sites',
      label: 'Sites actifs',
      icon: 'mdi-office-building',
      iconColor: 'primary',
      current: apiStats.value?.totalSites || 0,
      previous: (apiStats.value?.totalSites || 0) - 1,
      changePercent: 5.2,
      changeText: 'vs période précédente',
      changeIcon: 'mdi-trending-up',
      changeColor: 'success',
    },
    {
      id: 'users',
      label: 'Utilisateurs',
      icon: 'mdi-account-group',
      iconColor: 'success',
      current: apiStats.value?.totalUsers || 0,
      previous: (apiStats.value?.totalUsers || 0) - 2,
      changePercent: 8.3,
      changeText: 'vs période précédente',
      changeIcon: 'mdi-trending-up',
      changeColor: 'success',
    },
  ])

  const activityChartData = computed(() => {
    if (!apiStats.value?.charts?.activity) {
      return {
        labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
        datasets: [
          {
            label: 'Documents',
            data: [0, 0, 0, 0],
            borderColor: '#5b8dd9',
            backgroundColor: 'rgba(91, 141, 217, 0.1)',
            borderWidth: 2,
          },
          {
            label: 'Actions',
            data: [0, 0, 0, 0],
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            borderWidth: 2,
          },
        ],
      }
    }
    const data = apiStats.value.charts.activity
    return {
      labels: data.map(w => w.label),
      datasets: [
        {
          label: 'Documents',
          data: data.map(w => w.documents),
          borderColor: '#5b8dd9',
          backgroundColor: 'rgba(91, 141, 217, 0.1)',
          borderWidth: 2,
        },
        {
          label: 'Actions',
          data: data.map(w => w.actions),
          borderColor: '#22c55e',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          borderWidth: 2,
        },
      ],
    }
  })

  const typeDistributionData = computed(() => {
    if (!apiStats.value?.charts?.distribution) {
      return {
        labels: ['Documents', 'NC', 'Audits', 'Actions'],
        datasets: [
          {
            label: 'Distribution',
            data: [0, 0, 0, 0],
            backgroundColor: ['#5b8dd9', '#ef4444', '#3b82f6', '#22c55e'],
          },
        ],
      }
    }
    const data = apiStats.value.charts.distribution
    return {
      labels: ['Documents', 'NC', 'Audits', 'Actions'],
      datasets: [
        {
          label: 'Distribution',
          data: [data.documents, data.nc, data.audits, data.actions],
          backgroundColor: ['#5b8dd9', '#ef4444', '#3b82f6', '#22c55e'],
        },
      ],
    }
  })

  const performanceChartData = computed(() => {
    if (!apiStats.value?.charts?.performance) {
      return {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [
          {
            label: 'Objectifs atteints',
            data: [0, 0, 0, 0, 0, 0],
            backgroundColor: '#5b8dd9',
          },
          {
            label: 'Actions complétées',
            data: [0, 0, 0, 0, 0, 0],
            backgroundColor: '#22c55e',
          },
        ],
      }
    }
    const data = apiStats.value.charts.performance
    return {
      labels: data.map(m => m.label),
      datasets: [
        {
          label: 'Objectifs atteints',
          data: data.map(m => m.objectives),
          backgroundColor: '#5b8dd9',
        },
        {
          label: 'Actions complétées',
          data: data.map(m => m.actions),
          backgroundColor: '#22c55e',
        },
      ],
    }
  })

  function canAccess (requiredPermissions: string[]): boolean {
    const user = authStore.user as any
    if (user?.user_type === 'super_admin' || isEnterpriseAdminUser(user)) {
      return true
    }

    const navigationPermissions = getNavigationPermissionSet(user)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias =>
        navigationPermissions.has(alias),
      ),
    )
  }

  function routeExists (path: string): boolean {
    return routePathExists(router, path)
  }

  function canNavigate (path: string, requiredPermissions: string[]): boolean {
    return routeExists(path) && canAccess(requiredPermissions)
  }

  const quickActions = computed(() => {
    return DASHBOARD_QUICK_ACTIONS.filter(action =>
      canNavigate(action.route, action.requiredPermissions),
    )
  })

  const canViewLeadershipPolicy = computed(() =>
    canNavigate('/company/leadership/policy', ['leadership.politique.read']),
  )
  const canViewLeadershipOrgChart = computed(() =>
    canNavigate('/company/leadership/organization-chart', [
      'leadership.roles_responsabilites.organigramme.read',
    ]),
  )
  const canViewLeadershipRoles = computed(() =>
    canNavigate('/company/leadership/roles', [
      'leadership.roles_responsabilites.fiche_responsabilite.read',
    ]),
  )
  const leadershipQuickItemsAvailable = computed(
    () =>
      canViewLeadershipPolicy.value
      || canViewLeadershipOrgChart.value
      || canViewLeadershipRoles.value,
  )

  // Leadership computed properties
  const leadershipStats = computed<{
    policy_status?: string
    policy_last_update?: string
    organization_chart_updated?: boolean
    total_employees?: number
    employees_with_job_description?: number
  }>(() => apiStats.value?.leadership || {})

  const policyStatusColor = computed(() => {
    const status = leadershipStats.value.policy_status
    if (status === 'approved') return 'success'
    if (status === 'draft') return 'warning'
    if (status === 'expired') return 'error'
    return 'grey'
  })

  const policyStatusIcon = computed(() => {
    const status = leadershipStats.value.policy_status
    if (status === 'approved') return 'mdi-check-circle'
    if (status === 'draft') return 'mdi-pencil'
    if (status === 'expired') return 'mdi-alert-circle'
    return 'mdi-file-document-outline'
  })

  const policyStatusLabel = computed(() => {
    const status = leadershipStats.value.policy_status || 'not_created'
    const labels: Record<string, string> = {
      approved: 'Approuvée',
      draft: 'Brouillon',
      expired: 'Expirée',
      not_created: 'À créer',
    }
    return labels[status] || 'Non définie'
  })

  const policyStatusText = computed(() => {
    const lastUpdate = leadershipStats.value.policy_last_update
    if (!lastUpdate) return 'Aucune politique définie'
    return `Mise à jour ${lastUpdate}`
  })

  const orgChartColor = computed(() =>
    leadershipStats.value.organization_chart_updated ? 'success' : 'warning',
  )

  const orgChartStatus = computed(() =>
    leadershipStats.value.organization_chart_updated
      ? 'À jour (< 6 mois)'
      : 'Nécessite mise à jour',
  )

  const totalEmployees = computed(
    () => leadershipStats.value.total_employees || 0,
  )
  const employeesWithJobDescription = computed(
    () => leadershipStats.value.employees_with_job_description || 0,
  )

  const jobDescriptionProgress = computed(() => {
    if (totalEmployees.value === 0) return 0
    return Math.round(
      (employeesWithJobDescription.value / totalEmployees.value) * 100,
    )
  })

  function getRelativeTime (dateString: string): string {
    if (!dateString) return 'Récemment'
    const date = new Date(dateString)
    const now = new Date()
    const diffMs = now.getTime() - date.getTime()
    const diffMins = Math.floor(diffMs / 60_000)
    const diffHours = Math.floor(diffMs / 3_600_000)
    const diffDays = Math.floor(diffMs / 86_400_000)
    if (diffMins < 1) return 'À l\'instant'
    if (diffMins < 60) return `Il y a ${diffMins} min`
    if (diffHours < 24) return `Il y a ${diffHours}h`
    if (diffDays === 1) return 'Hier'
    if (diffDays < 7) return `Il y a ${diffDays} jours`
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  }

  function getCategoryLabel (type: string): string {
    const labels: Record<string, string> = {
      document: 'Documents',
      nonconformity: 'Non-conformités',
      audit: 'Audits',
      user: 'Collaborateurs',
      action: 'Actions',
    }
    return labels[type] || 'Activité'
  }

  async function loadDashboardData (isBackground = false) {
    if (dashboardRequestPromise) {
      return dashboardRequestPromise
    }

    dashboardRequestPromise = (async () => {
      try {
        if (!isBackground) {
          loading.value = true
        }
        error.value = null
        dashboardWarnings.value = []

        const params: any = {}
        const selectedSite = filters.value.site
        const useEnterpriseScope
          = canSelectGlobalScope.value
            && siteContextStore.activeScope === 'enterprise'

        params.scope = useEnterpriseScope ? 'enterprise' : 'site'
        if (!useEnterpriseScope && selectedSite) {
          params.site_id = selectedSite
        }

        const [statsResult, collaboratorResult] = await Promise.allSettled([
          dashboardService.getStats(params),
          dashboardService.getCollaboratorActions({
            scope: params.scope,
            site_id: params.site_id,
            per_page: 10,
          }),
        ])

        if (statsResult.status === 'fulfilled') {
          apiStats.value = statsResult.value
        } else {
          throw statsResult.reason
        }

        if (collaboratorResult.status === 'fulfilled') {
          collaboratorSummary.value = collaboratorResult.value
        } else {
          console.warn('[Dashboard] collaborator-actions unavailable:', collaboratorResult.reason)
          dashboardWarnings.value.push('Le bloc "Mes actions collaborateur" est momentanément indisponible.')
          collaboratorSummary.value = {
            stats: {
              total_assigned: 0,
              open_count: 0,
              in_progress_count: 0,
              overdue_count: 0,
              due_soon_count: 0,
              completed_count: 0,
            },
            actions: [],
            by_process: [],
            pagination: {
              current_page: 1,
              last_page: 1,
              per_page: 10,
              total: 0,
            },
          }
        }
      } catch (error_: any) {
        console.error('Erreur:', error_)
        error.value
          = error_.response?.data?.message
            || error_.message
            || 'Erreur de chargement'
        toast.error(error.value || 'Erreur de chargement')
      } finally {
        loading.value = false
        dashboardRequestPromise = null
      }
    })()

    return dashboardRequestPromise
  }

  async function refreshData () {
    await loadDashboardData()
    toast.success('Données actualisées')
  }

  function updateFilters (newFilters: typeof filters.value) {
    const previousSite = filters.value.site
    const nextFilters = { ...newFilters }
    if (
      !canSelectGlobalScope.value
      && (nextFilters.site === 'all' || nextFilters.site === null)
    ) {
      nextFilters.site = availableSites.value[0]?.value ?? nextFilters.site
    }
    filters.value = nextFilters

    const siteChanged = String(previousSite) !== String(nextFilters.site)
    if (siteChanged) {
      if (
        canSelectGlobalScope.value
        && (nextFilters.site === 'all' || nextFilters.site === null)
      ) {
        siteContextStore.setEnterpriseScope()
      } else if (nextFilters.site) {
        siteContextStore.setActiveSite(nextFilters.site)
      }
      return
    }

    loadDashboardData()
  }

  function exportDashboard () {
    if (
      canSelectGlobalScope.value
      && (filters.value.site === 'all' || filters.value.site === null)
    ) {
      exportScope.value = 'enterprise'
      exportSiteId.value = null
    } else {
      exportScope.value = 'site'
      exportSiteId.value
        = filters.value.site || availableSites.value[0]?.value || null
    }
    exportDialogOpen.value = true
  }

  function confirmExportDashboard () {
    if (exportScope.value === 'site' && !exportSiteId.value) {
      toast.error('Sélectionne un site pour exporter')
      return
    }

    const scopeLabel
      = exportScope.value === 'enterprise'
        ? 'global entreprise'
        : `site ${String(exportSiteId.value)}`

    exportDialogOpen.value = false
    toast.info(`Export ${scopeLabel} en cours...`)
    setTimeout(() => toast.success(`Dashboard exporté (${scopeLabel})`), 1000)
  }

  function handleQuickAction (actionId: string) {
    const action = quickActions.value.find(item => item.id === actionId)
    if (!action) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    if (!canNavigate(action.route, action.requiredPermissions)) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    router.push(action.route)
  }

  function handleStatClick (statId: number) {
    const target = DASHBOARD_STAT_ROUTES[statId]
    if (!target) {
      return
    }
    if (!canNavigate(target.route, target.permissions)) {
      toast.info('Accès limité: permission insuffisante')
      return
    }
    router.push(target.route)
  }

  function handleRecentActivityClick (activity: {
    route: string | null
    canOpen: boolean
  }) {
    if (!activity.route) {
      return
    }

    if (!activity.canOpen) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    router.push(activity.route)
  }

  function openProcessActions (processId: number | null) {
    if (!processId) {
      return
    }

    router.push({
      path: '/company/actions',
      query: {
        process_id: String(processId),
      },
    })
  }

  function handleTypeDistributionChartClick (payload: { label: string }) {
    const labelKey = payload.label.trim().toLowerCase()

    const distributionTargets: Record<
      string,
      { route: string, permissions: string[] }
    > = {
      document: { route: '/company/documents', permissions: ['support.documents.read'] },
      documents: {
        route: '/company/documents',
        permissions: ['support.documents.read'],
      },
      nc: {
        route: '/company/nonconformities',
        permissions: ['amelioration.non_conformites.read'],
      },
      audit: { route: '/company/audits', permissions: ['evaluation.audits.read'] },
      audits: { route: '/company/audits', permissions: ['evaluation.audits.read'] },
      action: {
        route: '/company/actions',
        permissions: ['amelioration.non_conformites.read'],
      },
      actions: {
        route: '/company/actions',
        permissions: ['amelioration.non_conformites.read'],
      },
    }

    const target = distributionTargets[labelKey]
    if (!target) {
      return
    }

    if (!canNavigate(target.route, target.permissions)) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    router.push(target.route)
  }

  function handleActivityChartClick (payload: {
    datasetIndex: number
    dataIndex: number
    label: string
  }) {
    const targetsByDatasetIndex: Record<
      number,
      { route: string, permissions: string[] }
    > = {
      0: {
        route: '/company/documents',
        permissions: ['support.documents.read'],
      },
      1: {
        route: '/company/actions',
        permissions: ['amelioration.non_conformites.read'],
      },
    }

    const target = targetsByDatasetIndex[payload.datasetIndex]
    if (!target) {
      return
    }

    if (!canNavigate(target.route, target.permissions)) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    router.push(target.route)
  }

  function handlePerformanceChartClick (payload: {
    datasetIndex: number
    dataIndex: number
    label: string
  }) {
    const targetsByDatasetIndex: Record<
      number,
      { route: string, permissions: string[] }
    > = {
      0: {
        route: '/company/planning/objectives',
        permissions: ['planification.objectifs.read'],
      },
      1: {
        route: '/company/actions',
        permissions: ['amelioration.non_conformites.read'],
      },
    }

    const target = targetsByDatasetIndex[payload.datasetIndex]
    if (!target) {
      return
    }

    if (!canNavigate(target.route, target.permissions)) {
      toast.info('Accès limité: permission insuffisante')
      return
    }

    router.push(target.route)
  }

  function toggleEditMode () {
    editMode.value = !editMode.value
    if (!editMode.value) {
      saveLayout()
    }
  }

  async function saveLayout () {
    try {
      await api.post('/dashboard/layout', {
        layout: dashboardWidgets.value,
      })
      toast.success('Layout sauvegardé')
    } catch (error) {
      console.error('Erreur sauvegarde layout:', error)
      toast.error('Erreur lors de la sauvegarde')
    }
  }

  async function loadLayout () {
    if (layoutRequestPromise) {
      return layoutRequestPromise
    }

    layoutRequestPromise = (async () => {
      try {
        const response = await api.get('/dashboard/layout')
        const layout = response.data?.data
        if (Array.isArray(layout) && layout.length > 0) {
          dashboardWidgets.value = layout
        }
      } catch (error) {
        console.error('Erreur chargement layout:', error)
      } finally {
        layoutRequestPromise = null
      }
    })()

    return layoutRequestPromise
  }

  function syncFiltersWithSiteContext () {
    if (
      canSelectGlobalScope.value
      && siteContextStore.activeScope === 'enterprise'
    ) {
      filters.value.site = 'all'
      return
    }

    if (siteContextStore.activeSiteId) {
      filters.value.site = siteContextStore.activeSiteId
      return
    }

    if (availableSites.value.length > 0) {
      filters.value.site = availableSites.value[0]?.value ?? null
    }
  }

  function handleSiteContextChanged () {
    const previous = filters.value.site
    syncFiltersWithSiteContext()
    if (String(previous) !== String(filters.value.site)) {
      loadDashboardData()
    }
  }

  onMounted(async () => {
    await siteContextStore.loadAvailableSites()
    syncFiltersWithSiteContext()
    await loadDashboardData()
    await loadLayout()
    refreshInterval = setInterval(() => loadDashboardData(true), 120_000)
    window.addEventListener('site-context-changed', handleSiteContextChanged)
  })

  onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval)
    window.removeEventListener('site-context-changed', handleSiteContextChanged)
  })
</script>

<style scoped>
.dashboard-container {
  background: #f8fafc;
  min-height: 100vh;
  max-width: 1600px;
  margin: 0 auto;
  padding: 24px;
  scroll-behavior: smooth;
}

.dashboard-panel {
  border-radius: 12px;
}

:deep(.v-card) {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  transition: all 0.3s ease;
}

:deep(.v-card:hover) {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

:deep(.v-btn) {
  transition: all 0.2s ease;
}

:deep(.v-btn:active) {
  transform: scale(0.95);
}

:deep(.v-chip) {
  transition: all 0.2s ease;
}

@media (max-width: 960px) {
  .dashboard-container {
    padding: 18px;
  }

  .dashboard-panel {
    border-radius: 14px;
  }
}

@media (max-width: 600px) {
  .dashboard-container {
    padding: 12px;
  }

  :deep(.v-btn) {
    width: 100%;
  }
}

:deep(.v-chip:hover) {
  transform: scale(1.05);
}
</style>
