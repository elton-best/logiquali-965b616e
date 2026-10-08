<template>
  <BaseCard>
    <div v-if="title" class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
      <p v-if="description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ description }}</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center h-64">
      <div class="text-center">
        <div class="inline-block w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-2" />
        <p class="text-sm text-gray-600">Chargement...</p>
      </div>
    </div>

    <div v-else-if="error" class="flex items-center justify-center h-64 text-red-600">
      <div class="text-center">
        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
        </svg>
        <p>{{ error }}</p>
      </div>
    </div>

    <apexchart
      v-else
      :height="height"
      :options="chartOptions"
      :series="series"
      :type="chartType"
    />
  </BaseCard>
</template>

<script setup lang="ts">
  import type { ApexOptions } from 'apexcharts'
  import type { ChartType } from '@/types/indicator'
  import { computed } from 'vue'
  import BaseCard from '@/components/common/BaseCard.vue'

  interface Props {
    chartType: ChartType
    series: any[]
    title?: string
    description?: string
    height?: number | string
    loading?: boolean
    error?: string | null
    customOptions?: Partial<ApexOptions>
  }

  const props = withDefaults(defineProps<Props>(), {
    height: 350,
    loading: false,
    error: null,
  })

  const chartOptions = computed<ApexOptions>(() => {
    const baseOptions: ApexOptions = {
      chart: {
        fontFamily: 'Inter, sans-serif',
        toolbar: {
          show: true,
          tools: {
            download: true,
            selection: false,
            zoom: false,
            zoomin: false,
            zoomout: false,
            pan: false,
            reset: false,
          },
        },
        locales: [{
          name: 'fr',
          options: {
            months: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
            shortMonths: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            days: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
            shortDays: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
          },
        }],
        defaultLocale: 'fr',
      },
      colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
      dataLabels: {
        enabled: false,
      },
      stroke: {
        curve: 'smooth',
        width: 2,
      },
      grid: {
        borderColor: '#E5E7EB',
      },
      xaxis: {
        labels: {
          style: {
            colors: '#6B7280',
          },
        },
      },
      yaxis: {
        labels: {
          style: {
            colors: '#6B7280',
          },
        },
      },
      legend: {
        position: 'bottom',
        horizontalAlign: 'center',
        labels: {
          colors: '#6B7280',
        },
      },
      tooltip: {
        theme: 'light',
      },
    }

    // Chart-specific options
    if (props.chartType === 'line' || props.chartType === 'area') {
      Object.assign(baseOptions, {
        stroke: {
          curve: 'smooth',
          width: props.chartType === 'area' ? 1 : 2,
        },
        fill: props.chartType === 'area'
          ? {
            type: 'gradient',
            gradient: {
              opacityFrom: 0.6,
              opacityTo: 0.1,
            },
          }
          : undefined,
      })
    }

    if (props.chartType === 'bar') {
      Object.assign(baseOptions, {
        plotOptions: {
          bar: {
            borderRadius: 4,
            horizontal: false,
            columnWidth: '60%',
          },
        },
      })
    }

    if (props.chartType === 'pie' || props.chartType === 'donut') {
      Object.assign(baseOptions, {
        plotOptions: {
          pie: {
            donut: {
              size: props.chartType === 'donut' ? '70%' : '0%',
            },
          },
        },
        dataLabels: {
          enabled: true,
        },
      })
    }

    if (props.chartType === 'radar') {
      Object.assign(baseOptions, {
        plotOptions: {
          radar: {
            size: 140,
            polygons: {
              strokeColors: '#E5E7EB',
              fill: {
                colors: ['#F3F4F6', '#FFFFFF'],
              },
            },
          },
        },
      })
    }

    if (props.chartType === 'gauge') {
      Object.assign(baseOptions, {
        plotOptions: {
          radialBar: {
            startAngle: -135,
            endAngle: 135,
            hollow: {
              size: '70%',
            },
            dataLabels: {
              name: {
                fontSize: '16px',
                offsetY: -10,
              },
              value: {
                fontSize: '24px',
                offsetY: 5,
                formatter: (val: number) => `${val}%`,
              },
            },
          },
        },
      })
    }

    // Merge with custom options
    return {
      ...baseOptions,
      ...props.customOptions,
    }
  })
</script>
