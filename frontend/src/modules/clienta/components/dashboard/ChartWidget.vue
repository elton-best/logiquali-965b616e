<template>
  <v-card class="chart-card" elevation="2" rounded="xl">
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon :color="iconColor">{{ icon }}</v-icon>
        <span class="text-h6 font-weight-bold">{{ title }}</span>
      </div>
      <v-chip :color="chipColor" size="small" variant="tonal">
        {{ subtitle }}
      </v-chip>
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-6">
      <canvas ref="chartCanvas" />
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    type ChartConfiguration,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Title,
    Tooltip,
  } from 'chart.js'
  import { onMounted, ref, watch } from 'vue'

  Chart.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    BarController,
    LineController,
    DoughnutController,
    PieController,
  )

  const props = defineProps<{
    title: string
    subtitle?: string
    icon?: string
    iconColor?: string
    chipColor?: string
    chartType: 'line' | 'bar' | 'doughnut' | 'pie'
    data: {
      labels: string[]
      datasets: Array<{
        label: string
        data: number[]
        backgroundColor?: string | string[]
        borderColor?: string
        borderWidth?: number
      }>
    }
    options?: any
  }>()

  const emit = defineEmits<{
    (
      event: 'chart-click',
      payload: { datasetIndex: number, dataIndex: number, label: string },
    ): void
  }>()

  const chartCanvas = ref<HTMLCanvasElement | null>(null)
  let chartInstance: Chart | null = null

  function createChart () {
    if (!chartCanvas.value) return

    if (chartInstance) {
      chartInstance.destroy()
    }

    const config: ChartConfiguration = {
      type: props.chartType,
      data: props.data,
      options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: (_event, elements) => {
          const firstElement = elements[0]
          if (!firstElement) {
            return
          }

          const dataIndex = firstElement.index
          const datasetIndex = firstElement.datasetIndex
          const label = String(props.data.labels[dataIndex] ?? '')

          emit('chart-click', { datasetIndex, dataIndex, label })
        },
        plugins: {
          legend: {
            position: 'bottom',
          },
          tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            padding: 12,
            cornerRadius: 8,
          },
        },
        ...props.options,
      },
    }

    chartInstance = new Chart(chartCanvas.value, config)
  }

  watch(
    () => props.data,
    () => {
      createChart()
    },
    { deep: true },
  )

  onMounted(() => {
    createChart()
  })
</script>

<style scoped>
.chart-card {
  border: 1px solid rgba(148, 163, 184, 0.15);
}
</style>
