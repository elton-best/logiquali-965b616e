<template>
  <div class="improvement-dashboard">
    <h1 class="dashboard-title">Tableau de Bord Amélioration Continue</h1>

    <div class="stats-grid">
      <ImprovementStatCard icon="alert" title="NC Ouvertes" :value="stats.nc_open" />
      <ImprovementStatCard icon="calendar" title="Audits Planifiés" :value="stats.audits_planned" />
      <ImprovementStatCard icon="tasks" title="Actions En Cours" :value="stats.actions_active" />
      <ImprovementStatCard icon="target" title="Objectifs Atteints" :value="`${stats.objectives_achieved}%`" />
    </div>

    <div class="charts-grid">
      <KpiChart :data="ncChartData" title="Non-Conformités par Mois" type="bar" />
      <KpiChart :data="axesChartData" title="Répartition par Axe" type="doughnut" />
    </div>

    <div class="bottom-grid">
      <div class="card">
        <h3 class="card-title">Alertes</h3>
        <div class="alerts-list">
          <AlertBadge :count="5" text="5 actions en retard" type="danger" />
          <AlertBadge :count="3" text="3 audits à planifier" type="warning" />
        </div>
      </div>

      <div class="card">
        <h3 class="card-title">Actions Prioritaires</h3>
        <ActionTimeline :actions="priorityActions" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import ActionTimeline from '@/components/improvement/shared/ActionTimeline.vue'
  import AlertBadge from '@/components/improvement/shared/AlertBadge.vue'
  import ImprovementStatCard from '@/components/improvement/shared/ImprovementStatCard.vue'
  import KpiChart from '@/components/improvement/shared/KpiChart.vue'
  import { useDashboardStore } from '@/stores/improvement/dashboardStore'

  const dashboardStore = useDashboardStore()
  const stats = ref({ nc_open: 0, audits_planned: 0, actions_active: 0, objectives_achieved: 0 })
  const priorityActions = ref([])

  const ncChartData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{ label: 'NC', data: [12, 15, 10, 8, 14, 11] }],
  }

  const axesChartData = {
    labels: ['Qualité', 'HSE', 'Environnement'],
    datasets: [{ label: 'Axes', data: [45, 35, 20] }],
  }

  onMounted(async () => {
    await dashboardStore.fetchGlobal()
    stats.value = dashboardStore.statistics || stats.value
  })
</script>

<style scoped>
.improvement-dashboard {
  padding: var(--spacing-6);
}

.dashboard-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-6);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--spacing-4);
  margin-bottom: var(--spacing-6);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: var(--spacing-6);
  margin-bottom: var(--spacing-6);
}

.bottom-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: var(--spacing-6);
}

.card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  padding: var(--spacing-6);
  box-shadow: var(--shadow-base);
  transition: all var(--transition-base);
}

.card:hover {
  box-shadow: var(--shadow-md);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-4);
}

.alerts-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
}

@media (max-width: 768px) {
  .improvement-dashboard {
    padding: var(--spacing-4);
  }

  .stats-grid,
  .charts-grid,
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>
