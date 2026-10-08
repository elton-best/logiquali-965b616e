<template>
  <v-card variant="outlined">
    <v-card-title class="text-subtitle-1">
      <v-icon class="mr-2" color="primary">mdi-radar</v-icon>
      Radar PESTEL
    </v-card-title>
    <v-divider />
    <v-card-text class="pa-4">
      <div ref="chartContainer" style="min-height: 350px;">
        <canvas ref="chartCanvas" />
      </div>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import {
    Chart,
    type ChartConfiguration,
    Filler,
    Legend,
    LineElement,
    PointElement,
    RadarController,
    RadialLinearScale,
    Tooltip,
  } from 'chart.js'
  import { nextTick, onMounted, ref, watch } from 'vue'

  // Register Chart.js components
  Chart.register(
    RadarController,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend,
  )

  interface Context {
    id?: number
    type: string
    political?: string
    economic?: string
    social?: string
    technological?: string
    environmental?: string
    legal?: string
  }

  interface Props {
    context: Context
  }

  const props = defineProps<Props>()

  const chartCanvas = ref<HTMLCanvasElement | null>(null)
  const chartContainer = ref<HTMLDivElement | null>(null)
  let chartInstance: Chart | null = null

  function parseList (text: string) {
    if (!text) return []
    return text.split('\n').filter(item => item.trim())
  }

  function calculateScore (factorText: string) {
    // Calculate score based on number of items (max 10)
    const items = parseList(factorText)
    return Math.min(items.length, 10)
  }

  function getChartData () {
    if (!props.context) {
      return {
        labels: ['Politique', 'Économique', 'Social', 'Technologique', 'Environnemental', 'Légal'],
        datasets: [{
          label: 'Score PESTEL',
          data: [0, 0, 0, 0, 0, 0],
          backgroundColor: 'rgba(54, 162, 235, 0.2)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 2,
          pointBackgroundColor: 'rgba(54, 162, 235, 1)',
          pointBorderColor: '#fff',
          pointHoverBackgroundColor: '#fff',
          pointHoverBorderColor: 'rgba(54, 162, 235, 1)',
        }],
      }
    }

    const data = [
      calculateScore(props.context.political || ''),
      calculateScore(props.context.economic || ''),
      calculateScore(props.context.social || ''),
      calculateScore(props.context.technological || ''),
      calculateScore(props.context.environmental || ''),
      calculateScore(props.context.legal || ''),
    ]

    // Color gradient based on average score
    const avgScore = data.reduce((a, b) => a + b, 0) / data.length
    let bgColor, borderColor

    if (avgScore >= 7) {
      bgColor = 'rgba(76, 175, 80, 0.2)'
      borderColor = 'rgba(76, 175, 80, 1)'
    } else if (avgScore >= 4) {
      bgColor = 'rgba(255, 152, 0, 0.2)'
      borderColor = 'rgba(255, 152, 0, 1)'
    } else {
      bgColor = 'rgba(244, 67, 54, 0.2)'
      borderColor = 'rgba(244, 67, 54, 1)'
    }

    return {
      labels: ['Politique', 'Économique', 'Social', 'Technologique', 'Environnemental', 'Légal'],
      datasets: [{
        label: 'Impact PESTEL',
        data,
        backgroundColor: bgColor,
        borderColor: borderColor,
        borderWidth: 2,
        pointBackgroundColor: borderColor,
        pointBorderColor: '#fff',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: borderColor,
        pointRadius: 5,
        pointHoverRadius: 7,
      }],
    }
  }

  function initChart () {
    if (!chartCanvas.value) return

    const config: ChartConfiguration = {
      type: 'radar',
      data: getChartData(),
      options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
          r: {
            beginAtZero: true,
            max: 10,
            min: 0,
            ticks: {
              stepSize: 2,
              font: {
                size: 11,
              },
            },
            pointLabels: {
              font: {
                size: 12,
                weight: 'bold',
              },
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
            angleLines: {
              color: 'rgba(0, 0, 0, 0.1)',
            },
          },
        },
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              font: {
                size: 12,
              },
              padding: 15,
            },
          },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleFont: {
              size: 13,
            },
            bodyFont: {
              size: 12,
            },
            padding: 12,
            callbacks: {
              label: context => {
                const label = context.label || ''
                const value = context.parsed.r || 0
                return `${label}: ${value}/10 (${parseList(getFactorText(context.dataIndex)).length} éléments)`
              },
            },
          },
        },
      },
    }

    chartInstance = new Chart(chartCanvas.value, config)
  }

  function getFactorText (index: number): string {
    const factors = [
      props.context.political,
      props.context.economic,
      props.context.social,
      props.context.technological,
      props.context.environmental,
      props.context.legal,
    ]
    return factors[index] || ''
  }

  function updateChart () {
    if (!chartInstance) return

    chartInstance.data = getChartData()
    chartInstance.update()
  }

  onMounted(async () => {
    await nextTick()
    initChart()
  })

  watch(() => props.context, () => {
    updateChart()
  }, { deep: true })
</script>

<style scoped>
canvas {
  max-height: 350px;
}
</style>
