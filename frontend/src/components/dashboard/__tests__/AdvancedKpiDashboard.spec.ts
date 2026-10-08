import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import AdvancedKpiDashboard from '../AdvancedKpiDashboard.vue'

// Mock Chart.js complètement
vi.mock('chart.js', () => ({
  Chart: {
    register: vi.fn(),
  },
  CategoryScale: {},
  LinearScale: {},
  PointElement: {},
  LineElement: {},
  BarElement: {},
  ArcElement: {},
  RadialLinearScale: {},
  Title: {},
  Tooltip: {},
  Legend: {},
  Filler: {},
}))

// Mock vue-chartjs
vi.mock('vue-chartjs', () => ({
  Line: { template: '<canvas class="line-chart"></canvas>' },
  Bar: { template: '<canvas class="bar-chart"></canvas>' },
  Doughnut: { template: '<canvas class="doughnut-chart"></canvas>' },
  Radar: { template: '<canvas class="radar-chart"></canvas>' },
}))

// Mock ApexCharts
vi.mock('vue3-apexcharts', () => ({
  default: { template: '<div class="apexchart"></div>' },
}))

// Mock child components
vi.mock('../KpiStatCard.vue', () => ({
  default: { template: '<div class="kpi-stat-card"></div>' },
}))

describe('AdvancedKpiDashboard.vue', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(AdvancedKpiDashboard, {
      global: {
        stubs: {
          Line: true,
          Bar: true,
          Doughnut: true,
          Radar: true,
          apexchart: true,
          VSelect: true,
          VBtn: true,
          VDataTable: true,
          VChip: true,
          VIcon: true,
          KpiStatCard: true,
        },
      },
    })
  })

  it('renders correctly', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays dashboard title', () => {
    expect(wrapper.text()).toContain('Tableau de Bord KPI')
  })

  it('initializes with 4 KPI cards', () => {
    const vm = wrapper.vm as any
    expect(vm.kpiCards.length).toBe(4)
  })

  it('KPI cards have required properties', () => {
    const vm = wrapper.vm as any

    for (const card of vm.kpiCards) {
      expect(card).toHaveProperty('id')
      expect(card).toHaveProperty('title')
      expect(card).toHaveProperty('value')
    }
  })

  it('has objectives data', () => {
    const vm = wrapper.vm as any
    expect(vm.objectives.length).toBe(4)
  })

  it('has process performance data', () => {
    const vm = wrapper.vm as any
    expect(vm.processPerformance.length).toBe(6)
  })

  it('getStatusColor returns correct colors', () => {
    const vm = wrapper.vm as any

    expect(vm.getStatusColor('Conforme')).toBe('green')
    expect(vm.getStatusColor('Surveillance')).toBe('orange')
    expect(vm.getStatusColor('Non-Conforme')).toBe('red')
  })
})
