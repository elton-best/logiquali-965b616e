<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1 class="dashboard-title">Tableau de bord</h1>
      <p class="dashboard-subtitle">Vue d'ensemble de votre système de management intégré</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div v-for="(stat, index) in stats" :key="index" class="stat-card">
        <div class="stat-card-header">
          <div :class="['stat-icon', `icon-${stat.color}`]">
            <component :is="stat.icon" class="icon" />
          </div>
          <div
            :class="['stat-trend', getTrendClass(stat.trend)]"
          >
            <TrendingUp v-if="stat.trend === 'up'" class="trend-icon" />
            <TrendingDown v-if="stat.trend === 'down'" class="trend-icon" />
            <span>{{ stat.change }}</span>
          </div>
        </div>
        <p class="stat-label">{{ stat.label }}</p>
        <p class="stat-value">{{ stat.value }}</p>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
      <!-- Performance Chart -->
      <div class="chart-card">
        <h3 class="chart-title">Performance & Conformité</h3>
        <div class="chart-container">
          <LineChart :data="processData" :options="lineChartOptions" />
        </div>
      </div>

      <!-- Risk Distribution -->
      <div class="chart-card">
        <h3 class="chart-title">Distribution des risques</h3>
        <div class="chart-container">
          <PieChart :data="riskDistribution" :options="pieChartOptions" />
        </div>
      </div>

      <!-- Audit Progress -->
      <div class="chart-card">
        <h3 class="chart-title">Progression des audits</h3>
        <div class="chart-container">
          <BarChart :data="auditData" :options="barChartOptions" />
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="chart-card">
        <h3 class="chart-title">Activités récentes</h3>
        <div class="activities-list">
          <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
            <div
              :class="['activity-dot', `dot-${activity.status}`]"
            />
            <div class="activity-content">
              <p class="activity-title">{{ activity.title }}</p>
              <p class="activity-time">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  // import { LineChart, PieChart, BarChart } from 'vue-chart-3';
  import { Chart, registerables } from 'chart.js'
  import {
    AlertCircle,
    CheckCircle2,
    FileText,
    Target,
    TrendingDown,
    TrendingUp,
  } from 'lucide-vue-next'
  import { ref } from 'vue'

  Chart.register(...registerables)

  const stats = ref([
    {
      label: 'Processus actifs',
      value: '24',
      change: '+12%',
      trend: 'up',
      icon: Target,
      color: 'blue',
    },
    {
      label: 'Documents valides',
      value: '156',
      change: '+8%',
      trend: 'up',
      icon: FileText,
      color: 'green',
    },
    {
      label: 'Non-conformités ouvertes',
      value: '7',
      change: '-15%',
      trend: 'down',
      icon: AlertCircle,
      color: 'orange',
    },
    {
      label: 'Audits planifiés',
      value: '3',
      change: '0%',
      trend: 'neutral',
      icon: CheckCircle2,
      color: 'purple',
    },
  ])

  const processData = ref({
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [
      {
        label: 'Conformité',
        data: [85, 88, 90, 87, 92, 94],
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
      },
      {
        label: 'Performance',
        data: [78, 82, 85, 83, 88, 91],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.4,
      },
    ],
  })

  const lineChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  })

  const riskDistribution = ref({
    labels: ['Faible', 'Moyen', 'Élevé', 'Critique'],
    datasets: [{
      data: [12, 18, 8, 3],
      backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#991b1b'],
    }],
  })

  const pieChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  })

  const auditData = ref({
    labels: ['Qualité', 'Sécurité', 'Environnement', 'SI'],
    datasets: [
      {
        label: 'Complétés',
        data: [12, 8, 6, 10],
        backgroundColor: '#10b981',
      },
      {
        label: 'Planifiés',
        data: [15, 10, 8, 12],
        backgroundColor: '#94a3b8',
      },
    ],
  })

  const barChartOptions = ref({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'bottom',
      },
    },
  })

  const recentActivities = ref([
    { id: 1, type: 'audit', title: 'Audit ISO 9001 complété', time: 'Il y a 2h', status: 'success' },
    { id: 2, type: 'nc', title: 'Nouvelle non-conformité signalée', time: 'Il y a 4h', status: 'warning' },
    { id: 3, type: 'doc', title: 'Document PRO-001 mis à jour', time: 'Il y a 6h', status: 'info' },
    { id: 4, type: 'risk', title: 'Risque R-045 réévalué', time: 'Hier', status: 'info' },
    { id: 5, type: 'process', title: 'Processus P-012 validé', time: 'Hier', status: 'success' },
  ])

  function getTrendClass (trend: string) {
    if (trend === 'up') return 'trend-up'
    if (trend === 'down') return 'trend-down'
    return 'trend-neutral'
  }
</script>

<style scoped>
.dashboard-container {
  padding: var(--spacing-8);
}

.dashboard-header {
  margin-bottom: var(--spacing-8);
}

.dashboard-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.dashboard-subtitle {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-1);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--spacing-6);
  margin-bottom: var(--spacing-8);
}

.stat-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  padding: var(--spacing-6);
  transition: all var(--transition-base);
}

.stat-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

.stat-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-4);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-blue {
  background: var(--color-primary-100);
  color: var(--color-primary-600);
}

.icon-green {
  background: var(--color-success-100);
  color: var(--color-success-600);
}

.icon-orange {
  background: var(--color-warning-100);
  color: var(--color-warning-600);
}

.icon-purple {
  background: #ede9fe;
  color: #7c3aed;
}

.icon {
  width: 24px;
  height: 24px;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: var(--spacing-1);
  font-size: var(--font-size-sm);
}

.trend-up {
  color: var(--color-success-600);
}

.trend-down {
  color: var(--color-success-600);
}

.trend-neutral {
  color: var(--text-secondary);
}

.trend-icon {
  width: 16px;
  height: 16px;
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.stat-value {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  margin-top: var(--spacing-1);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: var(--spacing-6);
}

.chart-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  padding: var(--spacing-6);
}

.chart-title {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-4);
}

.chart-container {
  width: 100%;
  height: 250px;
}

.activities-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-3);
}

.activity-dot {
  width: 8px;
  height: 8px;
  border-radius: var(--radius-full);
  margin-top: 8px;
  flex-shrink: 0;
}

.dot-success {
  background: var(--color-success-500);
}

.dot-warning {
  background: var(--color-warning-500);
}

.dot-info {
  background: var(--color-primary-500);
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.activity-time {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-top: var(--spacing-1);
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: var(--spacing-4);
  }

  .stats-grid,
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
