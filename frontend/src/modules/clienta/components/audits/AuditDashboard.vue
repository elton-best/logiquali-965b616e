<template>
  <v-container class="audit-dashboard pa-6" fluid>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 font-weight-bold mb-2">
          <v-icon class="mr-2" size="32">mdi-chart-line</v-icon>
          Dashboard Audits QHSE
        </h1>
        <p class="text-body-2 text-grey">
          Vue d'ensemble des audits internes - ISO 9001:2015 §9.2
        </p>
      </v-col>
    </v-row>

    <!-- KPI Cards -->
    <v-row class="mt-4">
      <v-col cols="12" md="3" sm="6">
        <v-card class="kpi-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-caption text-grey">Audits planifiés</div>
                <div class="text-h4 font-weight-bold mt-2">
                  {{ stats?.total || 0 }}
                </div>
              </div>
              <v-avatar color="primary" size="56">
                <v-icon color="white" size="32">mdi-calendar-check</v-icon>
              </v-avatar>
            </div>
            <v-progress-linear
              class="mt-4"
              color="primary"
              height="4"
              :model-value="completionRate"
              rounded
            />
            <div class="text-caption mt-1">
              {{ stats?.completed || 0 }} réalisés ({{ completionRate }}%)
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card class="kpi-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-caption text-grey">Taux conformité</div>
                <div class="text-h4 font-weight-bold mt-2" :class="conformityColor">
                  {{ stats?.avg_conformity_rate || 0 }}%
                </div>
              </div>
              <v-avatar :color="conformityColor.replace('text-', '')" size="56">
                <v-icon color="white" size="32">mdi-check-circle</v-icon>
              </v-avatar>
            </div>
            <div class="text-caption mt-4">
              Objectif: ≥ 85%
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card class="kpi-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-caption text-grey">Non-conformités</div>
                <div class="text-h4 font-weight-bold mt-2 text-error">
                  {{ (stats?.nc_major_count || 0) + (stats?.nc_minor_count || 0) }}
                </div>
              </div>
              <v-avatar color="error" size="56">
                <v-icon color="white" size="32">mdi-alert-circle</v-icon>
              </v-avatar>
            </div>
            <div class="text-caption mt-4">
              <span class="text-error">{{ stats?.nc_major_count || 0 }}</span> majeures,
              <span class="text-warning">{{ stats?.nc_minor_count || 0 }}</span> mineures
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card class="kpi-card" elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-caption text-grey">En cours</div>
                <div class="text-h4 font-weight-bold mt-2 text-info">
                  {{ stats?.in_progress || 0 }}
                </div>
              </div>
              <v-avatar color="info" size="56">
                <v-icon color="white" size="32">mdi-play-circle</v-icon>
              </v-avatar>
            </div>
            <div class="text-caption mt-4">
              {{ stats?.planned || 0 }} à venir
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Charts Row -->
    <v-row class="mt-4">
      <!-- Conformity Trend -->
      <v-col cols="12" md="8">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-chart-line</v-icon>
            Évolution Taux de Conformité
          </v-card-title>
          <v-card-text>
            <apexchart
              v-if="conformityTrendData.length > 0"
              height="300"
              :options="conformityChartOptions"
              :series="conformityChartSeries"
              type="line"
            />
            <div v-else class="text-center pa-8 text-grey">
              Aucune donnée disponible
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Audits by Type -->
      <v-col cols="12" md="4">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-chart-donut</v-icon>
            Répartition par Type
          </v-card-title>
          <v-card-text>
            <apexchart
              v-if="auditsByType.length > 0"
              height="300"
              :options="typeChartOptions"
              :series="auditsByType"
              type="donut"
            />
            <div v-else class="text-center pa-8 text-grey">
              Aucune donnée disponible
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Audits by Status & Findings -->
    <v-row class="mt-4">
      <v-col cols="12" md="6">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-chart-bar</v-icon>
            Audits par Statut
          </v-card-title>
          <v-card-text>
            <apexchart
              v-if="auditsByStatus.length > 0"
              height="300"
              :options="statusChartOptions"
              :series="statusChartSeries"
              type="bar"
            />
            <div v-else class="text-center pa-8 text-grey">
              Aucune donnée disponible
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center">
            <v-icon class="mr-2">mdi-alert-box</v-icon>
            Constats par Sévérité
          </v-card-title>
          <v-card-text>
            <apexchart
              height="300"
              :options="findingsChartOptions"
              :series="findingsChartSeries"
              type="bar"
            />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Recent Audits -->
    <v-row class="mt-4">
      <v-col cols="12">
        <v-card elevation="2">
          <v-card-title class="d-flex align-center justify-space-between">
            <div>
              <v-icon class="mr-2">mdi-clock-outline</v-icon>
              Audits Récents
            </div>
            <v-btn
              color="primary"
              variant="text"
              @click="$router.push('/audits')"
            >
              Voir tout
            </v-btn>
          </v-card-title>
          <v-card-text>
            <v-data-table
              class="elevation-0"
              :headers="headers"
              :items="recentAudits"
              :loading="loading"
            >
              <template #item.status="{ item }">
                <v-chip :color="getStatusColor(item.status)" size="small">
                  {{ item.status }}
                </v-chip>
              </template>
              <template #item.conformity_rate="{ item }">
                <v-progress-circular
                  v-if="item.conformity_rate"
                  :color="item.conformity_rate >= 85 ? 'success' : 'warning'"
                  :model-value="item.conformity_rate"
                  size="40"
                >
                  <span class="text-caption">{{ item.conformity_rate }}%</span>
                </v-progress-circular>
                <span v-else class="text-grey">N/A</span>
              </template>
              <template #item.actions="{ item }">
                <v-btn
                  icon
                  size="small"
                  @click="viewAudit(item.id)"
                >
                  <v-icon>mdi-eye</v-icon>
                </v-btn>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  const router = useRouter()
  const store = useAuditStore()

  // State
  const loading = ref(false)
  const stats = ref<any>(null)
  const recentAudits = ref<any[]>([])

  const headers = [
    { title: 'Référence', key: 'ref' },
    { title: 'Titre', key: 'title' },
    { title: 'Type', key: 'type' },
    { title: 'Date', key: 'planned_date' },
    { title: 'Statut', key: 'status' },
    { title: 'Conformité', key: 'conformity_rate' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  // Computed
  const completionRate = computed(() => {
    if (!stats.value?.total) return 0
    return Math.round((stats.value.completed / stats.value.total) * 100)
  })

  const conformityColor = computed(() => {
    const rate = stats.value?.avg_conformity_rate || 0
    if (rate >= 90) return 'text-success'
    if (rate >= 75) return 'text-info'
    if (rate >= 60) return 'text-warning'
    return 'text-error'
  })

  // Chart Data
  const conformityTrendData = computed(() => {
    return [] // À remplir avec vraies données
  })

  const conformityChartOptions = {
    chart: {
      type: 'line',
      toolbar: { show: false },
    },
    stroke: {
      curve: 'smooth',
      width: 3,
    },
    xaxis: {
      categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
    },
    yaxis: {
      min: 0,
      max: 100,
      title: { text: 'Taux de conformité (%)' },
    },
    colors: ['#4CAF50'],
  }

  const conformityChartSeries = [
    {
      name: 'Conformité',
      data: [82, 85, 78, 90, 88, 92],
    },
  ]

  const auditsByType = computed(() => {
    return [25, 15, 10, 8, 5] // internal, process, system, product, supplier
  })

  const typeChartOptions = {
    labels: ['Interne', 'Processus', 'Système', 'Produit', 'Fournisseur'],
    colors: ['#2196F3', '#4CAF50', '#FF9800', '#F44336', '#9C27B0'],
    legend: { position: 'bottom' },
  }

  const auditsByStatus = computed(() => {
    return [
      { x: 'Planifié', y: stats.value?.planned || 0 },
      { x: 'En cours', y: stats.value?.in_progress || 0 },
      { x: 'Terminé', y: stats.value?.completed || 0 },
    ]
  })

  const statusChartOptions = {
    chart: { type: 'bar' },
    colors: ['#FFC107', '#2196F3', '#4CAF50'],
    plotOptions: {
      bar: { horizontal: false },
    },
  }

  const statusChartSeries = computed(() => [{
    name: 'Audits',
    data: auditsByStatus.value.map(s => s.y),
  }])

  const findingsChartSeries = [{
    name: 'NC Majeures',
    data: [stats.value?.nc_major_count || 0],
  }, {
    name: 'NC Mineures',
    data: [stats.value?.nc_minor_count || 0],
  }, {
    name: 'Observations',
    data: [stats.value?.observations_count || 0],
  }]

  const findingsChartOptions = {
    chart: { type: 'bar', stacked: true },
    colors: ['#F44336', '#FF9800', '#FFC107'],
    plotOptions: {
      bar: { horizontal: true },
    },
  }

  // Methods
  async function loadData () {
    loading.value = true
    try {
      await store.fetchStatistics()
      stats.value = store.statistics

      await store.fetchAudits({ per_page: 5 })
      recentAudits.value = store.audits.slice(0, 5)
    } catch (error) {
      console.error(error)
    } finally {
      loading.value = false
    }
  }

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      planned: 'warning',
      in_progress: 'info',
      completed: 'success',
      cancelled: 'error',
    }
    return colors[status] || 'grey'
  }

  function viewAudit (id: number) {
    router.push(`/quality/audits/${id}`)
  }

  onMounted(() => {
    loadData()
  })
</script>

<style scoped>
.audit-dashboard {
  max-width: 1600px;
  margin: 0 auto;
}

.kpi-card {
  transition: transform 0.2s;
}

.kpi-card:hover {
  transform: translateY(-4px);
}
</style>
