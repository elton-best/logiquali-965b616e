<template>
  <div class="satisfaction-dashboard bg-gray-50 p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Satisfaction Parties Intéressées</h1>
        <p class="text-gray-600 mt-1">Évaluation Personnel - Clients - Prestataires (Module 12)</p>
      </div>
      <div class="flex gap-2">
        <v-btn
          color="primary"
          prepend-icon="mdi-file-document-plus"
          @click="createSurvey"
        >
          Nouvelle Enquête
        </v-btn>
        <v-btn
          color="success"
          prepend-icon="mdi-download"
          variant="outlined"
          @click="exportReport"
        >
          Rapport RGPD
        </v-btn>
      </div>
    </div>

    <!-- Category Tabs -->
    <v-tabs v-model="activeTab" class="mb-6" color="primary">
      <v-tab value="clients">
        <v-icon start>mdi-account-group</v-icon>
        Clients
      </v-tab>
      <v-tab value="employees">
        <v-icon start>mdi-account-tie</v-icon>
        Personnel
      </v-tab>
      <v-tab value="providers">
        <v-icon start>mdi-truck</v-icon>
        Prestataires
      </v-tab>
    </v-tabs>

    <!-- Clients Tab -->
    <v-window v-model="activeTab">
      <v-window-item value="clients">
        <!-- Client NPS & Stats -->
        <div class="grid grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Net Promoter Score</p>
                <p class="text-4xl font-bold text-blue-600">{{ clientStats.nps }}</p>
                <p class="text-xs text-green-600 mt-1">+5 vs trim. préc.</p>
              </div>
              <v-icon color="blue" size="48">mdi-chart-box</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Satisfaction Globale</p>
                <p class="text-3xl font-bold text-green-600">{{ clientStats.satisfaction }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ clientStats.totalResponses }} réponses</p>
              </div>
              <v-icon color="green" size="48">mdi-emoticon-happy</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Taux Réponse</p>
                <p class="text-3xl font-bold text-purple-600">{{ clientStats.responseRate }}%</p>
                <p class="text-xs text-gray-500 mt-1">{{ clientStats.totalSent }} envoyées</p>
              </div>
              <v-icon color="purple" size="48">mdi-chart-line</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Taux Fidélisation</p>
                <p class="text-3xl font-bold text-indigo-600">{{ clientStats.retention }}%</p>
                <p class="text-xs text-green-600 mt-1">+3% vs N-1</p>
              </div>
              <v-icon color="indigo" size="48">mdi-account-heart</v-icon>
            </div>
          </div>
        </div>

        <!-- Client Charts -->
        <div class="grid grid-cols-2 gap-6 mb-6">
          <!-- NPS Distribution -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Répartition NPS</h3>
            <div class="flex items-center gap-4 mb-4">
              <div class="flex-1">
                <div class="flex justify-between mb-1">
                  <span class="text-sm text-green-600 font-medium">Promoteurs (9-10)</span>
                  <span class="text-sm font-semibold">{{ npsDistribution.promoters }}%</span>
                </div>
                <v-progress-linear color="green" height="10" :model-value="npsDistribution.promoters" rounded />
              </div>
            </div>
            <div class="flex items-center gap-4 mb-4">
              <div class="flex-1">
                <div class="flex justify-between mb-1">
                  <span class="text-sm text-yellow-600 font-medium">Passifs (7-8)</span>
                  <span class="text-sm font-semibold">{{ npsDistribution.passives }}%</span>
                </div>
                <v-progress-linear color="amber" height="10" :model-value="npsDistribution.passives" rounded />
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="flex-1">
                <div class="flex justify-between mb-1">
                  <span class="text-sm text-red-600 font-medium">Détracteurs (0-6)</span>
                  <span class="text-sm font-semibold">{{ npsDistribution.detractors }}%</span>
                </div>
                <v-progress-linear color="red" height="10" :model-value="npsDistribution.detractors" rounded />
              </div>
            </div>
          </div>

          <!-- Satisfaction Evolution -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Évolution Satisfaction</h3>
            <Line :data="clientSatisfactionTrend" :height="200" :options="lineChartOptions" />
          </div>
        </div>
      </v-window-item>

      <!-- Employees Tab -->
      <v-window-item value="employees">
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Évaluations Réalisées</p>
                <p class="text-3xl font-bold text-blue-600">{{ employeeStats.completed }}/{{ employeeStats.total }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ employeeStats.completionRate }}% terminées</p>
              </div>
              <v-icon color="blue" size="48">mdi-clipboard-check</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Note Moyenne</p>
                <p class="text-3xl font-bold text-green-600">{{ employeeStats.averageRating }}/5</p>
                <p class="text-xs text-green-600 mt-1">+0.3 vs N-1</p>
              </div>
              <v-icon color="green" size="48">mdi-star</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Plans Développement</p>
                <p class="text-3xl font-bold text-purple-600">{{ employeeStats.developmentPlans }}</p>
                <p class="text-xs text-gray-500 mt-1">En cours</p>
              </div>
              <v-icon color="purple" size="48">mdi-school</v-icon>
            </div>
          </div>
        </div>

        <!-- Employee Charts -->
        <div class="grid grid-cols-2 gap-6">
          <!-- Rating Distribution -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Distribution Notes</h3>
            <Bar :data="ratingDistribution" :height="300" :options="barChartOptions" />
          </div>

          <!-- Competency Levels -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Compétences Moyennes</h3>
            <div class="space-y-3">
              <div v-for="skill in competencyData" :key="skill.name">
                <div class="flex justify-between mb-1">
                  <span class="text-sm font-medium">{{ skill.name }}</span>
                  <span class="text-sm font-semibold">{{ skill.value }}/5</span>
                </div>
                <v-progress-linear
                  :color="getSkillColor(skill.value)"
                  height="8"
                  :model-value="(skill.value / 5) * 100"
                  rounded
                />
              </div>
            </div>
          </div>
        </div>
      </v-window-item>

      <!-- Providers Tab -->
      <v-window-item value="providers">
        <div class="grid grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Prestataires Actifs</p>
                <p class="text-3xl font-bold text-blue-600">{{ providerStats.total }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ providerStats.evaluated }} évalués</p>
              </div>
              <v-icon color="blue" size="48">mdi-briefcase</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Excellents</p>
                <p class="text-3xl font-bold text-green-600">{{ providerStats.excellent }}</p>
                <p class="text-xs text-green-600 mt-1">≥ 4/5</p>
              </div>
              <v-icon color="green" size="48">mdi-check-decagram</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Acceptables</p>
                <p class="text-3xl font-bold text-orange-600">{{ providerStats.acceptable }}</p>
                <p class="text-xs text-orange-600 mt-1">3-4/5</p>
              </div>
              <v-icon color="orange" size="48">mdi-alert-circle</v-icon>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">Non Conformes</p>
                <p class="text-3xl font-bold text-red-600">{{ providerStats.nonCompliant }}</p>
                <p class="text-xs text-red-600 mt-1">&lt; 3/5</p>
              </div>
              <v-icon color="red" size="48">mdi-close-circle</v-icon>
            </div>
          </div>
        </div>

        <!-- Provider Charts -->
        <div class="grid grid-cols-2 gap-6">
          <!-- Classification Pie -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Classification Prestataires</h3>
            <Doughnut :data="providerClassification" :height="250" :options="doughnutChartOptions" />
          </div>

          <!-- Criteria Comparison -->
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Critères Évaluation (Moyenne)</h3>
            <Bar :data="providerCriteriaChart" :height="250" :options="barChartOptions" />
          </div>
        </div>
      </v-window-item>
    </v-window>

    <!-- RGPD Notice -->
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6">
      <div class="flex">
        <v-icon color="amber">mdi-shield-lock</v-icon>
        <div class="ml-3">
          <p class="text-sm text-yellow-800">
            <strong>Conformité RGPD:</strong> Les données personnelles sont automatiquement anonymisées après les délais de conservation (1 an clients, 3 ans personnel). Les exports respectent le droit à l'oubli.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import {
    ArcElement,
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
  import { computed, ref } from 'vue'
  import { Bar, Doughnut, Line } from 'vue-chartjs'
  import { useRouter } from 'vue-router'

  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
  )

  const router = useRouter()

  // Data
  const activeTab = ref('clients')

  const clientStats = ref({
    nps: 42,
    satisfaction: 87,
    totalResponses: 156,
    responseRate: 68,
    totalSent: 229,
    retention: 92,
  })

  const npsDistribution = ref({
    promoters: 52,
    passives: 38,
    detractors: 10,
  })

  const employeeStats = ref({
    completed: 78,
    total: 85,
    completionRate: 92,
    averageRating: 4.2,
    developmentPlans: 23,
  })

  const providerStats = ref({
    total: 34,
    evaluated: 34,
    excellent: 18,
    acceptable: 12,
    nonCompliant: 4,
  })

  const competencyData = ref([
    { name: 'Compétences Techniques', value: 4.2 },
    { name: 'Compétences Comportementales', value: 4 },
    { name: 'Leadership', value: 3.8 },
    { name: 'Innovation', value: 3.5 },
    { name: 'Communication', value: 4.3 },
    { name: 'Collaboration', value: 4.1 },
  ])

  // Chart Data
  const clientSatisfactionTrend = computed(() => ({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
    datasets: [{
      label: 'Satisfaction %',
      data: [82, 84, 83, 85, 86, 87, 88, 87, 86, 88, 89, 87],
      borderColor: 'rgb(16, 185, 129)',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      fill: true,
      tension: 0.4,
    }],
  }))

  const ratingDistribution = computed(() => ({
    labels: ['1 étoile', '2 étoiles', '3 étoiles', '4 étoiles', '5 étoiles'],
    datasets: [{
      label: 'Nombre d\'évaluations',
      data: [2, 5, 18, 32, 21],
      backgroundColor: [
        'rgba(239, 68, 68, 0.7)',
        'rgba(251, 146, 60, 0.7)',
        'rgba(250, 204, 21, 0.7)',
        'rgba(132, 204, 22, 0.7)',
        'rgba(34, 197, 94, 0.7)',
      ],
    }],
  }))

  const providerClassification = computed(() => ({
    labels: ['Excellent (≥4)', 'Acceptable (3-4)', 'Non Conforme (<3)'],
    datasets: [{
      data: [providerStats.value.excellent, providerStats.value.acceptable, providerStats.value.nonCompliant],
      backgroundColor: [
        'rgba(34, 197, 94, 0.7)',
        'rgba(251, 146, 60, 0.7)',
        'rgba(239, 68, 68, 0.7)',
      ],
      borderColor: ['rgb(34, 197, 94)', 'rgb(251, 146, 60)', 'rgb(239, 68, 68)'],
      borderWidth: 2,
    }],
  }))

  const providerCriteriaChart = computed(() => ({
    labels: ['Qualité', 'Délais', 'Communication', 'NC', 'Conformité'],
    datasets: [{
      label: 'Note moyenne /5',
      data: [4.2, 3.8, 4.5, 4, 4.3],
      backgroundColor: 'rgba(59, 130, 246, 0.7)',
      borderColor: 'rgb(59, 130, 246)',
      borderWidth: 2,
    }],
  }))

  // Chart Options
  const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100 } },
  }

  const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } },
  }

  const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom' } },
  }

  // Methods
  function getSkillColor (value) {
    if (value >= 4.5) return 'green'
    if (value >= 3.5) return 'blue'
    if (value >= 2.5) return 'orange'
    return 'red'
  }

  function createSurvey () {
    router.push('/satisfaction/surveys/create')
  }

  function exportReport () {
    console.log('Export RGPD report')
  }
</script>

<style scoped>
.satisfaction-dashboard {
  min-height: calc(100vh - 64px);
}
</style>
