<template>
  <div class="kpi-dashboard">
    <h3 class="text-lg font-semibold mb-4">Tableau de Bord KPI</h3>

    <div class="grid grid-cols-2 gap-4 mb-6">
      <ImprovementStatCard
        v-for="kpi in kpis"
        :key="kpi.id"
        :title="kpi.name"
        :unit="kpi.unit"
        :value="kpi.current_value"
      />
    </div>

    <KpiChart :data="chartData" title="Évolution des KPI" type="line" />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import ImprovementStatCard from '../shared/ImprovementStatCard.vue'
  import KpiChart from '../shared/KpiChart.vue'

  const kpis = ref([
    { id: 1, name: 'Taux conformité', current_value: '95', unit: '%' },
    { id: 2, name: 'NC ouvertes', current_value: '12', unit: '' },
  ])

  const chartData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr'],
    datasets: [{ label: 'KPI', data: [90, 92, 94, 95] }],
  }
</script>
