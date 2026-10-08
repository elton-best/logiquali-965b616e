<template>
  <div class="indicators-dashboard-page">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">Tableau de bord Indicateurs</h1>
        <p class="page-subtitle">
          Vue d'ensemble des performances QHSE (ISO 9001:2015 §9.1.1)
        </p>
      </div>
      <div class="header-actions">
        <button class="btn-secondary" @click="router.push('/improvement/kpi')">
          Liste KPIs
        </button>
        <button class="btn-primary" @click="exportDashboard">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
          Exporter
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-container">
      <div class="spinner" />
    </div>

    <!-- Dashboard Content -->
    <div v-else>
      <!-- Complete Dashboard Component -->
      <IndicatorDashboard
        :indicators="indicators"
        :statistics="dashboardStatistics"
        :trends="trendsMap"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { TrendData } from '@/types/indicator'
  import { storeToRefs } from 'pinia'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import IndicatorDashboard from '@/components/indicators/IndicatorDashboard.vue'
  import { useIndicatorStore } from '@/stores/indicatorStore'

  const router = useRouter()
  const indicatorStore = useIndicatorStore()

  const { indicators, loading, statistics } = storeToRefs(indicatorStore)
  const dashboardStatistics = computed(() => statistics.value ?? undefined)

  const trendsMap = ref<Record<number, TrendData>>({})

  function isoDate (date: Date): string {
    return date.toISOString().split('T')[0] ?? ''
  }

  async function loadDashboard () {
    // Load active indicators
    await indicatorStore.fetchIndicators({ active_only: true })
    await indicatorStore.fetchStatistics()

    // Load trends for top indicators
    const topIndicators = indicators.value.slice(0, 10)
    for (const indicator of topIndicators) {
      try {
        const now = new Date()
        const sixMonthsAgo = new Date()
        sixMonthsAgo.setMonth(now.getMonth() - 6)

        const trend = await indicatorStore.fetchTrend(
          indicator.id,
          isoDate(sixMonthsAgo),
          isoDate(now),
        )
        if (trend) {
          trendsMap.value[indicator.id] = trend
        }
      } catch (error) {
        console.error(`Failed to load trend for indicator ${indicator.id}:`, error)
      }
    }
  }

  async function exportDashboard () {
    try {
      await indicatorStore.exportToExcel({ active_only: true })
    } catch (error) {
      console.error('Export failed:', error)
    }
  }

  onMounted(() => {
    loadDashboard()
  })
</script>

<style scoped>
.indicators-dashboard-page {
  padding: var(--spacing-6);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-6);
}

.page-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  color: var(--color-text-primary);
  margin: 0;
}

.page-subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-secondary);
  margin-top: var(--spacing-1);
}

.header-actions {
  display: flex;
  gap: var(--spacing-3);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-3) var(--spacing-4);
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-primary:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.btn-secondary {
  padding: var(--spacing-3) var(--spacing-4);
  background: white;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.btn-secondary:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.btn-secondary:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 384px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-4);
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
    justify-content: center;
  }
}
</style>
