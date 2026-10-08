<template>
  <div class="indicator-dashboard">
    <!-- KPI Summary Cards -->
    <div class="kpi-summary-grid">
      <BaseCard>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon-success">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <p class="kpi-label">Conformes</p>
          <p class="kpi-value">{{ statistics?.by_alert_level.green || 0 }}</p>
          <p class="kpi-percentage">{{ greenPercentage }}% du total</p>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon-warning">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <p class="kpi-label">Attention</p>
          <p class="kpi-value">{{ statistics?.by_alert_level.yellow || 0 }}</p>
          <p class="kpi-percentage">{{ yellowPercentage }}% du total</p>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon-danger">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <p class="kpi-label">Critiques</p>
          <p class="kpi-value">{{ statistics?.by_alert_level.red || 0 }}</p>
          <p class="kpi-percentage">{{ redPercentage }}% du total</p>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="kpi-card">
          <div class="kpi-icon kpi-icon-primary">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
            </svg>
          </div>
          <p class="kpi-label">Total Indicateurs</p>
          <p class="kpi-value">{{ statistics?.total || 0 }}</p>
          <p class="kpi-percentage">{{ statistics?.active_count || 0 }} actifs</p>
        </div>
      </BaseCard>
    </div>

    <!-- Charts Row 1 -->
    <div class="charts-grid">
      <!-- Alert Level Distribution (Donut) -->
      <IndicatorChart
        v-if="alertDistributionSeries.length > 0"
        chart-type="donut"
        :custom-options="alertDistributionOptions"
        :height="300"
        :series="alertDistributionSeries"
        title="Répartition par niveau d'alerte"
      />

      <!-- Category Distribution (Pie) -->
      <IndicatorChart
        v-if="categoryDistributionSeries.length > 0"
        chart-type="pie"
        :custom-options="categoryDistributionOptions"
        :height="300"
        :series="categoryDistributionSeries"
        title="Répartition par catégorie"
      />
    </div>

    <!-- Charts Row 2 -->
    <div class="chart-full">
      <!-- Trend Over Time (Line) -->
      <IndicatorChart
        v-if="trendSeries.length > 0"
        chart-type="line"
        :custom-options="trendOptions"
        description="Historique des 6 derniers mois"
        :height="350"
        :series="trendSeries"
        title="Évolution des indicateurs critiques"
      />
    </div>

    <!-- Trend Widgets Grid -->
    <div v-if="topIndicators.length > 0" class="trend-section">
      <h3 class="section-title">Indicateurs clés</h3>
      <div class="trend-widgets-grid">
        <IndicatorTrendWidget
          v-for="indicator in topIndicators"
          :key="indicator.id"
          :indicator="indicator"
          :trend-data="getTrendData(indicator.id)"
        />
      </div>
    </div>

    <!-- Performance Heatmap (Optional) -->
    <IndicatorChart
      v-if="heatmapSeries.length > 0"
      chart-type="heatmap"
      :custom-options="heatmapOptions"
      :height="350"
      :series="heatmapSeries"
      title="Carte de performance mensuelle"
    />
  </div>
</template>

<script setup lang="ts">
  import type { ApexOptions } from 'apexcharts'
  import type { Indicator, IndicatorStatistics, TrendData } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'
  import IndicatorChart from './IndicatorChart.vue'
  import IndicatorTrendWidget from './IndicatorTrendWidget.vue'

  interface Props {
    statistics?: IndicatorStatistics
    indicators?: Indicator[]
    trends?: Record<number, TrendData>
  }

  const props = defineProps<Props>()

  // ============================================
  // KPI Percentages
  // ============================================

  const totalAlerts = computed(() => {
    if (!props.statistics) return 0
    const { green, yellow, red } = props.statistics.by_alert_level
    return green + yellow + red
  })

  const greenPercentage = computed(() => {
    if (totalAlerts.value === 0) return 0
    return Math.round((props.statistics!.by_alert_level.green / totalAlerts.value) * 100)
  })

  const yellowPercentage = computed(() => {
    if (totalAlerts.value === 0) return 0
    return Math.round((props.statistics!.by_alert_level.yellow / totalAlerts.value) * 100)
  })

  const redPercentage = computed(() => {
    if (totalAlerts.value === 0) return 0
    return Math.round((props.statistics!.by_alert_level.red / totalAlerts.value) * 100)
  })

  // ============================================
  // Alert Distribution Chart
  // ============================================

  const alertDistributionSeries = computed(() => {
    if (!props.statistics) return []
    const { green, yellow, red } = props.statistics.by_alert_level
    return [green, yellow, red]
  })

  const alertDistributionOptions = computed<ApexOptions>(() => ({
    labels: ['Conforme', 'Attention', 'Critique'],
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    legend: {
      position: 'bottom',
    },
  }))

  // ============================================
  // Category Distribution Chart
  // ============================================

  const categoryDistributionSeries = computed(() => {
    if (!props.statistics) return []
    return Object.values(props.statistics.by_category)
  })

  const categoryDistributionOptions = computed<ApexOptions>(() => ({
    labels: ['Qualité', 'Environnement', 'Santé & Sécurité', 'Performance', 'Financier'],
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
  }))

  // ============================================
  // Trend Chart
  // ============================================

  const trendSeries = computed(() => {
    if (!props.indicators || !props.trends) return []

    // Get critical indicators with trend data
    const criticalIndicators = props.indicators
      .filter(i => i.alert_level === 'red' || i.alert_level === 'yellow')
      .slice(0, 5)

    return criticalIndicators.map(indicator => {
      const trend = props.trends![indicator.id]
      return {
        name: indicator.name,
        data: trend ? trend.values : [],
      }
    })
  })

  const trendOptions = computed<ApexOptions>(() => {
    const firstTrend = props.trends ? Object.values(props.trends)[0] : null

    return {
      xaxis: {
        categories: firstTrend ? firstTrend.periods : [],
        labels: {
          rotate: -45,
        },
      },
      stroke: {
        curve: 'smooth',
        width: 2,
      },
      markers: {
        size: 4,
      },
    }
  })

  // ============================================
  // Top Indicators
  // ============================================

  const topIndicators = computed(() => {
    if (!props.indicators) return []

    // Sort by priority: red first, then yellow, then green
    return [...props.indicators]
      .toSorted((a: Indicator, b: Indicator) => {
        const priority: Record<'red' | 'yellow' | 'green', number> = { red: 3, yellow: 2, green: 1 }
        const aLevel = (a.alert_level === 'red' || a.alert_level === 'yellow' || a.alert_level === 'green') ? a.alert_level : 'green'
        const bLevel = (b.alert_level === 'red' || b.alert_level === 'yellow' || b.alert_level === 'green') ? b.alert_level : 'green'
        return priority[bLevel] - priority[aLevel]
      })
      .slice(0, 8)
  })

  function getTrendData (indicatorId: number): TrendData | undefined {
    return props.trends?.[indicatorId]
  }

  // ============================================
  // Heatmap Chart (Performance Matrix)
  // ============================================

  const heatmapSeries = computed(() => {
    if (!props.indicators) return []

    // Create monthly performance heatmap
    const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun']
    const categories = ['Qualité', 'Environnement', 'Santé', 'Performance']

    return categories.map(category => ({
      name: category,
      data: months.map(() => Math.floor(Math.random() * 100)), // Replace with real data
    }))
  })

  const heatmapOptions = computed<ApexOptions>(() => ({
    chart: {
      type: 'heatmap',
    },
    dataLabels: {
      enabled: true,
    },
    colors: ['#3B82F6'],
    xaxis: {
      categories: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
    },
    plotOptions: {
      heatmap: {
        colorScale: {
          ranges: [
            { from: 0, to: 50, color: '#EF4444', name: 'Faible' },
            { from: 51, to: 75, color: '#F59E0B', name: 'Moyen' },
            { from: 76, to: 100, color: '#10B981', name: 'Bon' },
          ],
        },
      },
    },
  }))
</script>

<style scoped>
.indicator-dashboard {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-6);
}

.kpi-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--spacing-4);
}

.kpi-card {
  text-align: center;
  padding: var(--spacing-2);
}

.kpi-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: var(--border-radius-full);
  margin-bottom: var(--spacing-3);
}

.kpi-icon-success {
  background: var(--color-success-light);
  color: var(--color-success);
}

.kpi-icon-warning {
  background: var(--color-warning-light);
  color: var(--color-warning);
}

.kpi-icon-danger {
  background: var(--color-danger-light);
  color: var(--color-danger);
}

.kpi-icon-primary {
  background: var(--color-primary-light);
  color: var(--color-primary);
}

.kpi-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  margin-bottom: var(--spacing-1);
}

.kpi-value {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin: 0;
}

.kpi-percentage {
  font-size: var(--font-size-xs);
  color: var(--color-text-tertiary);
  margin-top: var(--spacing-1);
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: var(--spacing-6);
}

.chart-full {
  width: 100%;
}

.trend-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-4);
}

.section-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--color-text-primary);
  margin: 0;
}

.trend-widgets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--spacing-4);
}

@media (max-width: 1024px) {
  .kpi-summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .trend-widgets-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .kpi-summary-grid {
    grid-template-columns: 1fr;
  }

  .trend-widgets-grid {
    grid-template-columns: 1fr;
  }
}
</style>
