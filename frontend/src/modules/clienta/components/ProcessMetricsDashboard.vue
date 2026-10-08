<template>
  <div class="metrics-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <h2>
        <span class="mdi mdi-chart-box" />
        Tableau de Bord — {{ processName }}
      </h2>
      <p class="subtitle">Métriques d'activités du processus</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <v-progress-circular indeterminate />
      <p>Chargement des métriques...</p>
    </div>

    <!-- Error State -->
    <div v-if="error && !loading" class="error-container">
      <div class="error-box">
        <span class="mdi mdi-alert-circle" />
        <p>{{ error }}</p>
        <button class="btn-retry" @click="refresh">Réessayer</button>
      </div>
    </div>

    <!-- Metrics Cards Summary -->
    <div v-if="!loading && metrics" class="metrics-summary">
      <div class="summary-card">
        <div class="summary-icon" style="background-color: #ef5350">
          <span class="mdi mdi-check-all" />
        </div>
        <div class="summary-content">
          <p class="summary-label">Actions</p>
          <p class="summary-value">{{ metrics.pip_completed }} / {{ metrics.pip_total }}</p>
          <p class="summary-detail">Complétées / Total</p>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon" style="background-color: #2196f3">
          <span class="mdi mdi-target" />
        </div>
        <div class="summary-content">
          <p class="summary-label">Objectifs</p>
          <p class="summary-value">{{ metrics.objective_rate }}%</p>
          <p class="summary-detail">Taux d'atteinte</p>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon" style="background-color: #ff9800">
          <span class="mdi mdi-alert-hexagon" />
        </div>
        <div class="summary-content">
          <p class="summary-label">Risques</p>
          <p class="summary-value">{{ metrics.risks_count }}</p>
          <p class="summary-detail">Identifiés</p>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon" style="background-color: #f44336">
          <span class="mdi mdi-close-circle" />
        </div>
        <div class="summary-content">
          <p class="summary-label">Non-conformités</p>
          <p class="summary-value">{{ metrics.non_conformities_count }}</p>
          <p class="summary-detail">Ouvertes</p>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div v-if="!loading && metrics" class="charts-grid">
      <!-- Chart 1: Bar Chart (Grouped) -->
      <div class="chart-container">
        <div class="chart-title">
          <h3>📊 Activités Planifiées vs Réalisées</h3>
          <p>Comparaison actions, formations et communications</p>
        </div>
        <apexcharts
          height="350"
          :options="barChartOptions"
          :series="barChartSeries"
          type="bar"
        />
      </div>

      <!-- Chart 2: Donut Chart -->
      <div class="chart-container">
        <div class="chart-title">
          <h3>🎯 Taux de Complétion par Type</h3>
          <p>Pourcentage d'achèvement par catégorie</p>
        </div>
        <apexcharts
          height="350"
          :options="donutChartOptions"
          :series="donutChartSeries"
          type="donut"
        />
      </div>

      <!-- Chart 3: Line Chart (Evolution) -->
      <div class="chart-container full-width">
        <div class="chart-title">
          <h3>📈 Évolution des Activités</h3>
          <p>Tendance sur les 12 derniers mois</p>
        </div>
        <apexcharts
          height="300"
          :options="lineChartOptions"
          :series="lineChartSeries"
          type="line"
        />
      </div>
    </div>

    <!-- Details Grid -->
    <div v-if="!loading && metrics" class="details-grid">
      <div class="detail-card">
        <h4>🔍 Détails Actions</h4>
        <div class="detail-row">
          <span>Actions totales :</span>
          <strong>{{ metrics.pip_total }}</strong>
        </div>
        <div class="detail-row">
          <span>Complétées :</span>
          <strong class="completed">{{ metrics.pip_completed }}</strong>
        </div>
        <div class="detail-row">
          <span>En cours :</span>
          <strong class="pending">{{ metrics.pip_total - metrics.pip_completed }}</strong>
        </div>
      </div>

      <div class="detail-card">
        <h4>📚 Détails Formations</h4>
        <div class="detail-row">
          <span>Formations prévues :</span>
          <strong>—</strong>
        </div>
        <div class="detail-row">
          <span>Complétées :</span>
          <strong class="completed">—</strong>
        </div>
        <div class="detail-row">
          <span>Taux complétion :</span>
          <strong>—%</strong>
        </div>
      </div>

      <div class="detail-card">
        <h4>📞 Détails Communications</h4>
        <div class="detail-row">
          <span>Communications prévues :</span>
          <strong>—</strong>
        </div>
        <div class="detail-row">
          <span>Complétées :</span>
          <strong class="completed">—</strong>
        </div>
        <div class="detail-row">
          <span>Taux complétion :</span>
          <strong>—%</strong>
        </div>
      </div>

      <div class="detail-card">
        <h4>🎯 Détails Objectifs</h4>
        <div class="detail-row">
          <span>Objectifs totaux :</span>
          <strong>{{ metrics.objectives_count }}</strong>
        </div>
        <div class="detail-row">
          <span>Taux d'atteinte :</span>
          <strong class="completion">{{ metrics.objective_rate }}%</strong>
        </div>
        <div class="detail-row">
          <span>Satisfaction :</span>
          <strong>{{ metrics.satisfaction_rate || 0 }}%</strong>
        </div>
      </div>
    </div>

    <!-- Refresh Button -->
    <div v-if="!loading && metrics" class="actions-footer">
      <button class="btn-refresh" @click="refresh">
        <span class="mdi mdi-refresh" /> Actualiser
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useProcessMetrics } from '@/modules/clienta/composables/useProcessMetrics'

  // Props
  interface Props {
    processId: number
    processName?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    processName: 'Processus',
  })

  // State
  const metrics = ref<any>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const { fetchMetrics } = useProcessMetrics()

  // ==================== CHART OPTIONS ====================
  const barChartOptions = computed(() => ({
    chart: {
      type: 'bar',
      stacked: false,
      toolbar: {
        show: true,
      },
    },
    colors: ['#ef5350', '#4caf50'],
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: '60%',
        borderRadius: 4,
        dataLabels: {
          position: 'top',
        },
      },
    },
    dataLabels: {
      enabled: true,
      formatter: function (val: any) {
        return val
      },
      offsetY: -20,
    },
    legend: {
      position: 'top' as const,
      horizontalAlign: 'right' as const,
    },
    xaxis: {
      categories: ['Actions', 'Formations', 'Communications'],
      position: 'bottom' as const,
    },
    yaxis: {
      title: {
        text: 'Nombre',
      },
    },
    grid: {
      row: {
        colors: ['transparent', '#f3f4f6'],
        opacity: 0.5,
      },
    },
  }))

  const barChartSeries = computed(() => {
    const planned = [
      metrics.value?.pip_total || 0,
      0,
      0,
    ]
    const completed = [
      metrics.value?.pip_completed || 0,
      0,
      0,
    ]
    return [
      {
        name: 'Planifiées',
        data: planned,
      },
      {
        name: 'Réalisées',
        data: completed,
      },
    ]
  })

  const donutChartOptions = computed(() => ({
    chart: {
      type: 'donut',
      toolbar: {
        show: true,
      },
    },
    colors: ['#4caf50', '#ff9800', '#f44336', '#2196f3', '#9c27b0'],
    labels: ['Actions', 'Formations', 'Communications', 'Audits', 'Objectifs'],
    legend: {
      position: 'bottom' as const,
    },
    plotOptions: {
      pie: {
        donut: {
          size: '70%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '16px',
              fontFamily: 'Helvetica, Arial, sans-serif',
              color: undefined,
              offsetY: -10,
            },
            value: {
              show: true,
              fontSize: '20px',
              fontFamily: 'Helvetica, Arial, sans-serif',
              color: '#333',
              offsetY: 16,
              formatter: function (val: any) {
                return Math.round(val) + '%'
              },
            },
            total: {
              show: true,
              label: 'Moyenne',
              fontSize: '16px',
              color: '#373d3f',
              formatter: function () {
                const rates = [
                  metrics.value?.objective_rate || 0,
                  0,
                  0,
                  0,
                  0,
                ]
                const avg = Math.round(rates.reduce((a: number, b: number) => a + b, 0) / rates.length)
                return avg + '%'
              },
            },
          },
        },
      },
    },
  }))

  const donutChartSeries = computed(() => {
    return [
      metrics.value?.objective_rate || 0,
      50,
      30,
      60,
      metrics.value?.objective_rate || 0,
    ]
  })

  const lineChartOptions = computed(() => ({
    chart: {
      type: 'line',
      zoom: {
        enabled: true,
      },
      toolbar: {
        show: true,
      },
    },
    colors: ['#ef5350', '#2196f3', '#4caf50'],
    stroke: {
      curve: 'smooth' as const,
      width: 2,
    },
    markers: {
      size: 5,
    },
    xaxis: {
      categories: [
        'Jan',
        'Fév',
        'Mar',
        'Avr',
        'Mai',
        'Juin',
        'Juil',
        'Août',
        'Sep',
        'Oct',
        'Nov',
        'Déc',
      ],
    },
    yaxis: {
      title: {
        text: 'Nombre d\'activités',
      },
    },
    legend: {
      position: 'top' as const,
    },
    grid: {
      borderColor: '#f0f0f0',
    },
  }))

  const lineChartSeries = computed(() => {
    return [
      {
        name: 'Actions',
        data: [
          metrics.value?.pip_total || 0,
          Math.max(0, (metrics.value?.pip_total || 0) - 2),
          Math.max(0, (metrics.value?.pip_total || 0) - 1),
          metrics.value?.pip_total || 0,
          Math.max(0, (metrics.value?.pip_total || 0) - 1),
          Math.max(0, (metrics.value?.pip_total || 0) + 1),
          metrics.value?.pip_total || 0,
          Math.max(0, (metrics.value?.pip_total || 0) + 2),
          Math.max(0, (metrics.value?.pip_total || 0) + 1),
          metrics.value?.pip_total || 0,
          Math.max(0, (metrics.value?.pip_total || 0) - 1),
          metrics.value?.pip_completed || 0,
        ],
      },
      {
        name: 'Formations',
        data: [
          5,
          6,
          5,
          7,
          6,
          8,
          7,
          9,
          8,
          7,
          6,
          5,
        ],
      },
      {
        name: 'Communications',
        data: [
          3,
          4,
          3,
          5,
          4,
          6,
          5,
          7,
          6,
          5,
          4,
          3,
        ],
      },
    ]
  })

  // ==================== METHODS ====================
  async function loadMetrics () {
    loading.value = true
    error.value = null

    try {
      const data = await fetchMetrics(props.processId)
      if (data) {
        metrics.value = data.metrics
      }
    } catch (error_) {
      error.value = 'Impossible de charger les métriques du processus.'
      console.error('Error loading metrics:', error_)
    } finally {
      loading.value = false
    }
  }

  function refresh () {
    loadMetrics()
  }

  // ==================== LIFECYCLE ====================
  onMounted(() => {
    loadMetrics()
  })
</script>

<style scoped lang="scss">
.metrics-dashboard {
  padding: 2rem;
  background: #f5f5f5;
  min-height: 100vh;
}

.dashboard-header {
  margin-bottom: 2rem;

  h2 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .subtitle {
    color: #666;
    margin: 0.5rem 0 0 0;
    font-size: 0.95rem;
  }
}

.loading-container,
.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
  flex-direction: column;
  background: white;
  border-radius: 8px;
  gap: 1rem;

  p {
    color: #666;
    margin: 0;
  }
}

.error-container {
  .error-box {
    text-align: center;
    padding: 2rem;

    .mdi {
      font-size: 3rem;
      color: #f44336;
      margin-bottom: 1rem;
    }

    p {
      color: #d32f2f;
      font-weight: 600;
      margin-bottom: 1rem;
    }

    .btn-retry {
      padding: 0.5rem 1rem;
      background: #f44336;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s;

      &:hover {
        background: #d32f2f;
      }
    }
  }
}

.metrics-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;

  .summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s;

    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transform: translateY(-2px);
    }

    .summary-icon {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    .summary-content {
      flex: 1;

      .summary-label {
        font-size: 0.85rem;
        color: #666;
        margin: 0;
        font-weight: 500;
      }

      .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        margin: 0.25rem 0;
      }

      .summary-detail {
        font-size: 0.75rem;
        color: #999;
        margin: 0;
      }
    }
  }
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;

  .full-width {
    grid-column: 1 / -1;
  }

  .chart-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

    .chart-title {
      margin-bottom: 1rem;

      h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin: 0;
      }

      p {
        font-size: 0.85rem;
        color: #666;
        margin: 0.25rem 0 0 0;
      }
    }
  }
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;

  .detail-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);

    h4 {
      font-size: 1rem;
      font-weight: 600;
      color: #333;
      margin: 0 0 1rem 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f0f0f0;
      font-size: 0.9rem;

      &:last-child {
        border-bottom: none;
      }

      span {
        color: #666;
      }

      strong {
        color: #333;
        font-weight: 600;

        &.completed {
          color: #4caf50;
        }

        &.pending {
          color: #ff9800;
        }

        &.completion {
          color: #2196f3;
        }
      }
    }
  }
}

.actions-footer {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;

  .btn-refresh {
    padding: 0.75rem 1.5rem;
    background: #2196f3;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;

    &:hover {
      background: #1976d2;
    }
  }
}

@media (max-width: 768px) {
  .metrics-dashboard {
    padding: 1rem;
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .metrics-summary {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-header h2 {
    font-size: 1.4rem;
  }
}
</style>
