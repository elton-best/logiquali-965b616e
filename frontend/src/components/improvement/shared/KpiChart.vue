<template>
  <div class="kpi-chart">
    <div v-if="title" class="chart-header">
      <h3 class="chart-title">{{ title }}</h3>
      <div class="chart-actions">
        <button class="action-btn" title="Télécharger" @click="downloadChart">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
          </svg>
        </button>
      </div>
    </div>

    <div class="chart-container" :style="{ height: height }">
      <canvas ref="chartCanvas" />
    </div>

    <div v-if="showLegend && chartInstance" class="chart-legend">
      <div
        v-for="(item, index) in legendItems"
        :key="index"
        class="legend-item"
      >
        <span class="legend-color" :style="{ backgroundColor: item.color }" />
        <span class="legend-label">{{ item.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Chart, registerables } from 'chart.js'
  import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

  Chart.register(...registerables)

  interface ChartDataset {
    label: string
    data: number[]
    backgroundColor?: string | string[]
    borderColor?: string | string[]
    borderWidth?: number
  }

  interface Props {
    type: 'line' | 'bar' | 'pie' | 'doughnut' | 'radar'
    data: {
      labels: string[]
      datasets: ChartDataset[]
    }
    options?: any
    title?: string
    height?: string
    showLegend?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    height: '300px',
    showLegend: true,
  })

  const chartCanvas = ref<HTMLCanvasElement | null>(null)
  const chartInstance = ref<Chart | null>(null)

  const defaultColors = [
    '#3b82f6', // blue
    '#10b981', // green
    '#f59e0b', // orange
    '#ef4444', // red
    '#8b5cf6', // purple
    '#ec4899', // pink
    '#06b6d4', // cyan
    '#84cc16', // lime
  ]

  const legendItems = computed(() => {
    if (!chartInstance.value) return []

    const meta = chartInstance.value.getDatasetMeta(0)
    if (!meta) return []

    const firstDataset = props.data.datasets[0]
    const background = firstDataset?.backgroundColor

    return props.data.labels.map((label, index) => ({
      label,
      color: Array.isArray(background)
        ? (background[index] ?? defaultColors[index % defaultColors.length])
        : defaultColors[index % defaultColors.length],
    }))
  })

  function createChart () {
    if (!chartCanvas.value) return

    if (chartInstance.value) {
      chartInstance.value.destroy()
    }

    const ctx = chartCanvas.value.getContext('2d')
    if (!ctx) return

    // Apply default colors if not provided
    const datasetsWithColors = props.data.datasets.map((dataset, idx) => {
      if (!dataset.backgroundColor) {
        dataset.backgroundColor = props.type === 'pie' || props.type === 'doughnut'
          ? props.data.labels.map((_, i) => defaultColors[i % defaultColors.length] as string)
          : defaultColors[idx % defaultColors.length]
      }
      if (!dataset.borderColor && props.type === 'line') {
        dataset.borderColor = defaultColors[idx % defaultColors.length]
      }
      return dataset
    })

    const defaultOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#666',
          borderWidth: 1,
          padding: 12,
          displayColors: true,
        },
      },
    }

    const mergedOptions = props.options
      ? { ...defaultOptions, ...props.options }
      : defaultOptions

    chartInstance.value = new Chart(ctx, {
      type: props.type,
      data: {
        labels: props.data.labels,
        datasets: datasetsWithColors,
      },
      options: mergedOptions,
    })
  }

  function downloadChart () {
    if (!chartCanvas.value) return

    const url = chartCanvas.value.toDataURL('image/png')
    const link = document.createElement('a')
    link.download = `chart-${Date.now()}.png`
    link.href = url
    link.click()
  }

  onMounted(() => {
    createChart()
  })

  watch(() => props.data, () => {
    createChart()
  }, { deep: true })

  watch(() => props.type, () => {
    createChart()
  })

  onBeforeUnmount(() => {
    if (chartInstance.value) {
      chartInstance.value.destroy()
    }
  })
</script>

<style scoped>
.kpi-chart {
  @apply bg-white rounded-lg border border-gray-200 p-4;
}

.chart-header {
  @apply flex items-center justify-between mb-4;
}

.chart-title {
  @apply text-lg font-semibold text-gray-900;
}

.chart-actions {
  @apply flex gap-2;
}

.action-btn {
  @apply p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors;
}

.chart-container {
  @apply relative;
}

.chart-legend {
  @apply flex flex-wrap gap-4 mt-4 pt-4 border-t border-gray-200;
}

.legend-item {
  @apply flex items-center gap-2;
}

.legend-color {
  @apply w-3 h-3 rounded-full;
}

.legend-label {
  @apply text-sm text-gray-700;
}
</style>
