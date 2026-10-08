<template>
  <div class="advanced-kpi-dashboard bg-gray-50 p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-gray-800">Tableau de Bord KPI</h1>
      <div class="flex gap-2">
        <v-select
          v-model="selectedPeriod"
          density="compact"
          :items="periods"
          label="Période"
          style="width: 150px"
          variant="outlined"
        />
        <v-btn
          color="primary"
          prepend-icon="mdi-refresh"
          @click="refreshData"
        >
          Actualiser
        </v-btn>
        <v-btn
          color="success"
          prepend-icon="mdi-download"
          variant="outlined"
          @click="exportPDF"
        >
          Export PDF
        </v-btn>
      </div>
    </div>

    <!-- KPI Cards Grid -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <KpiStatCard
        v-for="kpi in kpiCards"
        :key="kpi.id"
        :color="kpi.color"
        :icon="kpi.icon"
        :title="kpi.title"
        :trend="kpi.trend"
        :trend-value="kpi.trendValue"
        :unit="kpi.unit"
        :value="kpi.value"
      />
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-2 gap-6">
      <!-- Evolution KPI Line Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Évolution KPI Mensuels</h3>
        <Line :data="lineChartData" :height="200" :options="lineChartOptions" />
      </div>

      <!-- NC Distribution Pie Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Répartition Non-Conformités</h3>
        <Doughnut :data="doughnutChartData" :height="200" :options="doughnutChartOptions" />
      </div>

      <!-- Risk Heatmap with ApexCharts -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Heatmap Risques par Processus</h3>
        <apexchart
          height="250"
          :options="heatmapOptions"
          :series="heatmapSeries"
          type="heatmap"
        />
      </div>

      <!-- Objectives Progress Gauges -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Progression Objectifs</h3>
        <div class="grid grid-cols-2 gap-4">
          <div v-for="objective in objectives" :key="objective.id">
            <apexchart
              height="150"
              :options="getGaugeOptions(objective)"
              :series="[objective.progress]"
              type="radialBar"
            />
          </div>
        </div>
      </div>

      <!-- Actions Status Bar Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Actions par Statut</h3>
        <Bar :data="barChartData" :height="200" :options="barChartOptions" />
      </div>

      <!-- Audit Compliance Radar Chart -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Conformité Audits ISO</h3>
        <Radar :data="radarChartData" :height="200" :options="radarChartOptions" />
      </div>
    </div>

    <!-- Process Performance Table -->
    <div class="bg-white rounded-lg shadow p-6 mt-6">
      <h3 class="text-lg font-semibold mb-4">Performance Processus</h3>
      <v-data-table
        class="elevation-0"
        :headers="processHeaders"
        :items="processPerformance"
        :items-per-page="10"
      >
        <template #item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            size="small"
          >
            {{ item.status }}
          </v-chip>
        </template>
        <template #item.trend="{ item }">
          <div class="flex items-center gap-1">
            <v-icon
              :color="item.trend > 0 ? 'green' : 'red'"
              size="20"
            >
              {{ item.trend > 0 ? 'mdi-trending-up' : 'mdi-trending-down' }}
            </v-icon>
            <span :class="item.trend > 0 ? 'text-green-600' : 'text-red-600'">
              {{ Math.abs(item.trend) }}%
            </span>
          </div>
        </template>
      </v-data-table>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { ApexOptions } from 'apexcharts'
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
    RadialLinearScale,
    Title,
    Tooltip,
  } from 'chart.js'
  import { computed, ref } from 'vue'
  import VueApexCharts from 'vue3-apexcharts'
  import { Bar, Doughnut, Line, Radar } from 'vue-chartjs'
  import KpiStatCard from './KpiStatCard.vue'

  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    RadialLinearScale,
    Title,
    Tooltip,
    Legend,
    Filler,
  )

  const apexchart = VueApexCharts

  const selectedPeriod = ref('12mois')
  const periods = ['1mois', '3mois', '6mois', '12mois', 'annee']

  interface KpiCard {
    id: number
    title: string
    value: string
    unit: string
    trend: 'up' | 'down' | 'stable'
    trendValue: string
    icon: string
    color: string
  }

  // KPI Cards Data
  const kpiCards = ref<KpiCard[]>([
    {
      id: 1,
      title: 'Taux de Conformité',
      value: '96.2',
      unit: '%',
      trend: 'up',
      trendValue: '+2.3%',
      icon: 'mdi-check-circle',
      color: 'green',
    },
    {
      id: 2,
      title: 'NC Ouvertes',
      value: '8',
      unit: '',
      trend: 'down',
      trendValue: '-4',
      icon: 'mdi-alert-circle',
      color: 'orange',
    },
    {
      id: 3,
      title: 'Actions en Cours',
      value: '24',
      unit: '',
      trend: 'up',
      trendValue: '+6',
      icon: 'mdi-clipboard-check',
      color: 'blue',
    },
    {
      id: 4,
      title: 'Risques Critiques',
      value: '3',
      unit: '',
      trend: 'stable',
      trendValue: '0',
      icon: 'mdi-shield-alert',
      color: 'red',
    },
  ])

  // Line Chart - Evolution KPI
  const lineChartData = computed(() => ({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
    datasets: [
      {
        label: 'Taux Conformité (%)',
        data: [92, 93, 94, 95, 94, 96, 97, 96, 97, 98, 97, 96],
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        fill: true,
        tension: 0.4,
      },
      {
        label: 'Objectif (%)',
        data: [95, 95, 95, 95, 95, 95, 95, 95, 95, 95, 95, 95],
        borderColor: 'rgb(59, 130, 246)',
        borderDash: [5, 5],
        fill: false,
      },
    ],
  }))

  const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom' as const,
      },
    },
    scales: {
      y: {
        beginAtZero: false,
        min: 85,
        max: 100,
      },
    },
  }

  // Doughnut Chart - NC Distribution
  const doughnutChartData = computed(() => ({
    labels: ['Produits', 'Processus', 'Documentation', 'Ressources', 'Environnement'],
    datasets: [
      {
        data: [12, 19, 8, 5, 6],
        backgroundColor: [
          'rgba(239, 68, 68, 0.8)',
          'rgba(249, 115, 22, 0.8)',
          'rgba(234, 179, 8, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(59, 130, 246, 0.8)',
        ],
      },
    ],
  }))

  const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right' as const,
      },
    },
  }

  // Heatmap - Risk by Process (ApexCharts)
  const heatmapSeries = ref([
    {
      name: 'Production',
      data: [
        { x: 'Planification', y: 15 },
        { x: 'Exécution', y: 25 },
        { x: 'Contrôle', y: 12 },
        { x: 'Livraison', y: 8 },
      ],
    },
    {
      name: 'Qualité',
      data: [
        { x: 'Planification', y: 8 },
        { x: 'Exécution', y: 12 },
        { x: 'Contrôle', y: 20 },
        { x: 'Livraison', y: 5 },
      ],
    },
    {
      name: 'Achats',
      data: [
        { x: 'Planification', y: 18 },
        { x: 'Exécution', y: 22 },
        { x: 'Contrôle', y: 10 },
        { x: 'Livraison', y: 15 },
      ],
    },
    {
      name: 'RH',
      data: [
        { x: 'Planification', y: 6 },
        { x: 'Exécution', y: 9 },
        { x: 'Contrôle', y: 7 },
        { x: 'Livraison', y: 4 },
      ],
    },
  ])

  const heatmapOptions: ApexOptions = {
    chart: {
      type: 'heatmap',
      toolbar: { show: false },
    },
    dataLabels: {
      enabled: true,
    },
    colors: ['#22c55e'],
    xaxis: {
      type: 'category',
    },
    plotOptions: {
      heatmap: {
        colorScale: {
          ranges: [
            { from: 0, to: 5, color: '#22c55e', name: 'Faible' },
            { from: 6, to: 10, color: '#eab308', name: 'Moyen' },
            { from: 11, to: 20, color: '#f97316', name: 'Élevé' },
            { from: 21, to: 30, color: '#ef4444', name: 'Critique' },
          ],
        },
      },
    },
  }

  // Objectives Progress Gauges
  const objectives = ref([
    { id: 1, name: 'ISO 9001', progress: 85 },
    { id: 2, name: 'ISO 14001', progress: 72 },
    { id: 3, name: 'ISO 45001', progress: 68 },
    { id: 4, name: 'Client Satisfaction', progress: 92 },
  ])

  function getGaugeOptions (objective: any): ApexOptions {
    return {
      chart: {
        type: 'radialBar',
      },
      plotOptions: {
        radialBar: {
          hollow: {
            size: '60%',
          },
          dataLabels: {
            name: {
              fontSize: '14px',
              offsetY: -10,
            },
            value: {
              fontSize: '20px',
              fontWeight: 'bold',
              offsetY: 5,
              formatter: (val: number) => `${val}%`,
            },
          },
        },
      },
      labels: [objective.name],
      colors: [objective.progress >= 80 ? '#22c55e' : (objective.progress >= 60 ? '#f97316' : '#ef4444')],
    }
  }

  // Bar Chart - Actions par Statut
  const barChartData = computed(() => ({
    labels: ['En Attente', 'En Cours', 'Terminées', 'Retard', 'Annulées'],
    datasets: [
      {
        label: 'Nombre d\'actions',
        data: [8, 24, 156, 5, 3],
        backgroundColor: [
          'rgba(156, 163, 175, 0.8)',
          'rgba(59, 130, 246, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(107, 114, 128, 0.8)',
        ],
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

  // Radar Chart - Audit Compliance
  const radarChartData = computed(() => ({
    labels: ['ISO 9001', 'ISO 14001', 'ISO 45001', 'Documentation', 'Formation', 'Processus'],
    datasets: [
      {
        label: 'Score Actuel',
        data: [85, 72, 68, 90, 78, 82],
        borderColor: 'rgb(59, 130, 246)',
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
      },
      {
        label: 'Objectif',
        data: [90, 85, 85, 95, 90, 90],
        borderColor: 'rgb(34, 197, 94)',
        backgroundColor: 'rgba(34, 197, 94, 0.2)',
      },
    ],
  }))

  const radarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      r: {
        min: 0,
        max: 100,
        ticks: {
          stepSize: 20,
        },
      },
    },
  }

  // Process Performance Table
  const processHeaders = [
    { title: 'Processus', key: 'name', sortable: true },
    { title: 'Pilote', key: 'owner', sortable: true },
    { title: 'Performance', key: 'performance', sortable: true },
    { title: 'Objectif', key: 'target', sortable: true },
    { title: 'Tendance', key: 'trend', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
  ]

  const processPerformance = ref([
    { name: 'Production', owner: 'J. Dupont', performance: '96%', target: '95%', trend: 2, status: 'Conforme' },
    { name: 'Qualité', owner: 'M. Martin', performance: '94%', target: '95%', trend: -1, status: 'Surveillance' },
    { name: 'Achats', owner: 'L. Bernard', performance: '88%', target: '90%', trend: -3, status: 'Non-Conforme' },
    { name: 'RH', owner: 'S. Petit', performance: '97%', target: '95%', trend: 5, status: 'Conforme' },
    { name: 'Maintenance', owner: 'P. Robert', performance: '92%', target: '90%', trend: 3, status: 'Conforme' },
    { name: 'Logistique', owner: 'A. Richard', performance: '89%', target: '90%', trend: -2, status: 'Surveillance' },
  ])

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      'Conforme': 'green',
      'Surveillance': 'orange',
      'Non-Conforme': 'red',
    }
    return colors[status] || 'gray'
  }

  function refreshData () {
    console.log('Refreshing data...')
  // TODO: Implement API call to refresh dashboard data
  }

  function exportPDF () {
    console.log('Exporting to PDF...')
  // TODO: Implement PDF export functionality
  }
</script>

<style scoped>
.advanced-kpi-dashboard {
  min-height: 100vh;
}
</style>
