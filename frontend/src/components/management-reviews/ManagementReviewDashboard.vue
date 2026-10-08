<template>
  <div class="management-review-dashboard bg-gray-50 p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Revues de Direction</h1>
        <p class="text-gray-600 mt-1">Pilotage SMI - ISO 9001:2015 clause 9.3</p>
      </div>
      <div class="flex gap-2">
        <v-select
          v-model="selectedYear"
          density="compact"
          :items="years"
          label="Année"
          style="width: 120px"
          variant="outlined"
        />
        <v-btn
          color="primary"
          prepend-icon="mdi-calendar-plus"
          @click="createReview"
        >
          Planifier Revue
        </v-btn>
        <v-btn
          color="success"
          prepend-icon="mdi-download"
          variant="outlined"
          @click="exportAnnualReport"
        >
          Rapport Annuel
        </v-btn>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Revues Réalisées</p>
            <p class="text-3xl font-bold text-blue-600">{{ stats.completed }}/4</p>
            <p class="text-xs text-gray-500 mt-1">Objectif trimestriel</p>
          </div>
          <v-icon color="blue" size="48">mdi-calendar-check</v-icon>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Actions Décidées</p>
            <p class="text-3xl font-bold text-indigo-600">{{ stats.actionsTotal }}</p>
            <p class="text-xs text-green-600 mt-1">{{ stats.actionsClosed }} clôturées</p>
          </div>
          <v-icon color="indigo" size="48">mdi-checkbox-marked-circle</v-icon>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Taux Efficacité SMI</p>
            <p class="text-3xl font-bold text-green-600">{{ stats.efficiencyRate }}%</p>
            <p :class="['text-xs mt-1', stats.efficiencyTrend >= 0 ? 'text-green-600' : 'text-red-600']">
              {{ stats.efficiencyTrend >= 0 ? '+' : '' }}{{ stats.efficiencyTrend }}% vs N-1
            </p>
          </div>
          <v-icon color="green" size="48">mdi-trending-up</v-icon>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Objectifs Atteints</p>
            <p class="text-3xl font-bold text-purple-600">{{ stats.objectivesAchieved }}/{{ stats.objectivesTotal }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ stats.objectivesPercent }}%</p>
          </div>
          <v-icon color="purple" size="48">mdi-target</v-icon>
        </div>
      </div>
    </div>

    <!-- Timeline des Revues -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Calendrier Revues {{ selectedYear }}</h3>
        <v-chip :color="getNextReviewColor()" size="small">
          <v-icon start>mdi-clock-outline</v-icon>
          Prochaine: {{ nextReviewDate }}
        </v-chip>
      </div>

      <div class="relative mt-6">
        <!-- Timeline horizontal -->
        <div class="flex justify-between items-start">
          <div
            v-for="(review, index) in quarterlyReviews"
            :key="index"
            class="flex-1 relative"
          >
            <!-- Timeline point -->
            <div class="flex flex-col items-center">
              <div
                :class="[
                  'w-12 h-12 rounded-full flex items-center justify-center mb-2 border-4',
                  getReviewStatusClass(review.status)
                ]"
              >
                <v-icon :color="getReviewIconColor(review.status)">
                  {{ getReviewIcon(review.status) }}
                </v-icon>
              </div>

              <!-- Line connecting points -->
              <div
                v-if="index < 3"
                :class="[
                  'absolute top-6 left-1/2 h-1',
                  review.status === 'completed' ? 'bg-green-500' : 'bg-gray-300'
                ]"
                style="width: calc(100% + 20px)"
              />
            </div>

            <!-- Review info -->
            <div class="mt-4 text-center">
              <p class="font-semibold text-gray-800">{{ review.quarter }}</p>
              <p class="text-sm text-gray-600">{{ review.date }}</p>
              <v-chip
                class="mt-1"
                :color="review.status === 'completed' ? 'success' : review.status === 'planned' ? 'primary' : 'warning'"
                size="x-small"
              >
                {{ getStatusLabel(review.status) }}
              </v-chip>

              <div v-if="review.status === 'completed'" class="mt-2">
                <v-btn
                  color="primary"
                  size="x-small"
                  variant="text"
                  @click="viewReview(review.id)"
                >
                  Voir rapport
                </v-btn>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-2 gap-6 mb-6">
      <!-- Synthèse Données Entrée -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Synthèse Données d'Entrée (ISO 9.3.2)</h3>
        <Bar :data="inputDataChart" :height="200" :options="barChartOptions" />
      </div>

      <!-- Évolution Décisions -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Évolution Décisions/Actions</h3>
        <Line :data="decisionsChart" :height="200" :options="lineChartOptions" />
      </div>
    </div>

    <!-- Actions & Décisions Recent -->
    <div class="grid grid-cols-2 gap-6">
      <!-- Latest Actions -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Actions en Cours</h3>
          <v-chip color="warning" size="small">{{ ongoingActions.length }}</v-chip>
        </div>

        <div class="space-y-3">
          <div
            v-for="action in ongoingActions"
            :key="action.id"
            class="border-l-4 pl-4 py-2"
            :class="action.isOverdue ? 'border-red-500 bg-red-50' : 'border-blue-500 bg-blue-50'"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <p class="font-medium text-gray-800">{{ action.title }}</p>
                <p class="text-xs text-gray-600 mt-1">
                  Responsable: {{ action.responsible }} | Échéance: {{ action.dueDate }}
                </p>
              </div>
              <v-chip
                :color="action.isOverdue ? 'error' : 'primary'"
                size="x-small"
              >
                {{ action.progress }}%
              </v-chip>
            </div>
          </div>
        </div>

        <v-btn block class="mt-4" color="primary" variant="text">
          Voir toutes les actions
        </v-btn>
      </div>

      <!-- Latest Decisions -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Décisions Récentes</h3>
          <v-chip color="success" size="small">{{ recentDecisions.length }}</v-chip>
        </div>

        <div class="space-y-3">
          <div
            v-for="decision in recentDecisions"
            :key="decision.id"
            class="border-l-4 border-green-500 bg-green-50 pl-4 py-2"
          >
            <div class="flex items-start gap-2">
              <v-icon color="green" size="small">mdi-gavel</v-icon>
              <div class="flex-1">
                <p class="font-medium text-gray-800 text-sm">{{ decision.title }}</p>
                <p class="text-xs text-gray-600 mt-1">
                  {{ decision.reviewDate }} - {{ decision.reviewer }}
                </p>
              </div>
              <v-chip
                :color="decision.priority === 'high' ? 'error' : decision.priority === 'medium' ? 'warning' : 'success'"
                size="x-small"
              >
                {{ decision.priority === 'high' ? 'Haute' : decision.priority === 'medium' ? 'Moyenne' : 'Normale' }}
              </v-chip>
            </div>
          </div>
        </div>

        <v-btn block class="mt-4" color="primary" variant="text">
          Historique complet
        </v-btn>
      </div>
    </div>
  </div>
</template>

<script setup>
  import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
  } from 'chart.js'
  import { computed, onMounted, ref } from 'vue'
  import { Bar, Line } from 'vue-chartjs'
  import { useRouter } from 'vue-router'

  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
  )

  const router = useRouter()

  // Data
  const selectedYear = ref(new Date().getFullYear())
  const years = ref([2024, 2025, 2026])

  const stats = ref({
    completed: 3,
    actionsTotal: 24,
    actionsClosed: 18,
    efficiencyRate: 87,
    efficiencyTrend: 5,
    objectivesAchieved: 12,
    objectivesTotal: 15,
    objectivesPercent: 80,
  })

  const quarterlyReviews = ref([
    { id: 1, quarter: 'T1', date: '31 Mars', status: 'completed' },
    { id: 2, quarter: 'T2', date: '30 Juin', status: 'completed' },
    { id: 3, quarter: 'T3', date: '30 Sept', status: 'completed' },
    { id: 4, quarter: 'T4', date: '31 Déc', status: 'planned' },
  ])

  const nextReviewDate = computed(() => {
    const pending = quarterlyReviews.value.find(r => r.status !== 'completed')
    return pending ? `${pending.quarter} - ${pending.date}` : 'Aucune'
  })

  const ongoingActions = ref([
    {
      id: 1,
      title: 'Mise en place système gestion énergétique',
      responsible: 'Martin Dupont',
      dueDate: '15/02/2026',
      progress: 65,
      isOverdue: false,
    },
    {
      id: 2,
      title: 'Formation personnel nouvelles procédures',
      responsible: 'Sophie Bernard',
      dueDate: '28/01/2026',
      progress: 40,
      isOverdue: true,
    },
    {
      id: 3,
      title: 'Audit interne processus achats',
      responsible: 'Jean Martin',
      dueDate: '20/02/2026',
      progress: 85,
      isOverdue: false,
    },
  ])

  const recentDecisions = ref([
    {
      id: 1,
      title: 'Approbation budget formation 2026',
      reviewDate: '30 Sept 2025',
      reviewer: 'Direction',
      priority: 'high',
    },
    {
      id: 2,
      title: 'Extension périmètre certification ISO 14001',
      reviewDate: '30 Sept 2025',
      reviewer: 'DG',
      priority: 'medium',
    },
    {
      id: 3,
      title: 'Renouvellement contrat prestataire logistique',
      reviewDate: '30 Juin 2025',
      reviewer: 'Direction',
      priority: 'normal',
    },
  ])

  // Chart Data
  const inputDataChart = computed(() => ({
    labels: ['Objectifs', 'Satisfaction', 'NC', 'Audits', 'Réclamations', 'Performance'],
    datasets: [{
      label: 'Données Traitées',
      data: [15, 42, 8, 5, 12, 28],
      backgroundColor: [
        'rgba(59, 130, 246, 0.7)',
        'rgba(16, 185, 129, 0.7)',
        'rgba(239, 68, 68, 0.7)',
        'rgba(245, 158, 11, 0.7)',
        'rgba(139, 92, 246, 0.7)',
        'rgba(236, 72, 153, 0.7)',
      ],
      borderColor: [
        'rgb(59, 130, 246)',
        'rgb(16, 185, 129)',
        'rgb(239, 68, 68)',
        'rgb(245, 158, 11)',
        'rgb(139, 92, 246)',
        'rgb(236, 72, 153)',
      ],
      borderWidth: 2,
    }],
  }))

  const decisionsChart = computed(() => ({
    labels: ['T1', 'T2', 'T3', 'T4'],
    datasets: [
      {
        label: 'Décisions Prises',
        data: [8, 6, 10, 0],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: true,
        tension: 0.4,
      },
      {
        label: 'Actions Générées',
        data: [12, 8, 14, 0],
        borderColor: 'rgb(16, 185, 129)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        fill: true,
        tension: 0.4,
      },
    ],
  }))

  const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
      },
    },
  }

  const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
    scales: {
      y: {
        beginAtZero: true,
      },
    },
  }

  // Methods
  function getReviewStatusClass (status) {
    return {
      'bg-green-500 border-green-500': status === 'completed',
      'bg-blue-500 border-blue-500': status === 'planned',
      'bg-gray-300 border-gray-300': status === 'pending',
    }
  }

  function getReviewIconColor (status) {
    return status === 'completed' ? 'white' : (status === 'planned' ? 'white' : 'gray')
  }

  function getReviewIcon (status) {
    return status === 'completed' ? 'mdi-check-bold' : (status === 'planned' ? 'mdi-calendar' : 'mdi-calendar-clock')
  }

  function getStatusLabel (status) {
    const labels = {
      completed: 'Réalisée',
      planned: 'Planifiée',
      pending: 'À venir',
    }
    return labels[status] || status
  }

  function getNextReviewColor () {
    const now = new Date()
    const month = now.getMonth()

    // Alerter si dans moins de 30 jours
    if ((month === 2) || (month === 5) || (month === 8) || (month === 11)) {
      return 'warning'
    }
    return 'info'
  }

  function createReview () {
    router.push('/management-reviews/create')
  }

  function viewReview (id) {
    router.push(`/management-reviews/${id}`)
  }

  function exportAnnualReport () {
    // TODO: API call to generate annual review report
    console.log('Export annual report')
  }

  function refreshData () {
    // TODO: Fetch fresh data from API
    console.log('Refresh dashboard data')
  }

  onMounted(() => {
    // Load initial data
    refreshData()
  })
</script>

<style scoped>
.management-review-dashboard {
  min-height: calc(100vh - 64px);
}
</style>
