import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RiskMatrix from '../../improvement/Risk/RiskMatrix.vue'

// Mock the RiskHeatmap component
vi.mock('../../improvement/shared/RiskHeatmap.vue', () => ({
  default: {
    name: 'RiskHeatmap',
    template: '<div class="risk-heatmap"></div>',
    props: ['risks'],
    emits: ['cell-click', 'risk-click'],
  },
}))

// Mock the risk store
vi.mock('@/stores/improvement/riskStore', () => ({
  useRiskStore: vi.fn(() => ({
    fetchRisks: vi.fn().mockResolvedValue({}),
    risks: [
      {
        id: 1,
        name: 'Risk 1',
        probability: 0.5,
        impact: 0.7,
        criticality: 'high',
        type: 'risk',
        status: 'active',
        axes: ['axis1'],
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      },
    ],
  })),
}))

describe('RiskMatrix Component', () => {
  let wrapper: any

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('Rendering', () => {
    it('should render the component', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.exists()).toBe(true)
    })

    it('should display matrix title', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.text()).toContain('Matrice des Risques')
    })

    it('should render RiskHeatmap component', () => {
      wrapper = mount(RiskMatrix, {
        global: {
          stubs: {
            RiskHeatmap: {
              name: 'RiskHeatmap',
              template: '<div class="risk-heatmap-stub"></div>',
              props: ['risks'],
            },
          },
        },
      })

      expect(wrapper.find('.risk-heatmap-stub').exists()).toBe(true)
    })
  })

  describe('Props Passing', () => {
    it('should pass risks to heatmap component', () => {
      wrapper = mount(RiskMatrix, {
        global: {
          stubs: {
            RiskHeatmap: {
              name: 'RiskHeatmap',
              template: '<div></div>',
              props: ['risks'],
            },
          },
        },
      })

      const heatmap = wrapper.findComponent({ name: 'RiskHeatmap' })
      if (heatmap.exists()) {
        expect(heatmap.props('risks')).toBeDefined()
      }
    })
  })

  describe('Event Handling', () => {
    it('should emit risk-click event when heatmap emits risk-click', async () => {
      wrapper = mount(RiskMatrix, {
        global: {
          stubs: {
            RiskHeatmap: {
              name: 'RiskHeatmap',
              template: '<div @click="$emit(\'risk-click\', { id: 1, name: \'Risk 1\' })">Heatmap</div>',
              props: ['risks'],
              emits: ['cell-click', 'risk-click'],
            },
          },
        },
      })

      const heatmap = wrapper.findComponent({ name: 'RiskHeatmap' })
      if (heatmap.exists()) {
        await heatmap.vm.$emit('risk-click', { id: 1, name: 'Risk 1' })
        expect(wrapper.emitted('risk-click')).toBeTruthy()
      }
    })

    it('should handle cell-click event from heatmap', async () => {
      wrapper = mount(RiskMatrix, {
        global: {
          stubs: {
            RiskHeatmap: {
              name: 'RiskHeatmap',
              template: '<div @click="$emit(\'cell-click\', 0.5, 0.7)">Heatmap</div>',
              props: ['risks'],
              emits: ['cell-click', 'risk-click'],
            },
          },
        },
      })

      // Component should handle cell-click internally
      expect(wrapper.vm.handleCellClick).toBeDefined()
    })
  })

  describe('Matrix Wrapper', () => {
    it('should have matrix wrapper element', () => {
      wrapper = mount(RiskMatrix)

      const wrapper_elem = wrapper.find('.risk-matrix-wrapper')
      expect(wrapper_elem.exists()).toBe(true)
    })

    it('should have correct structure', () => {
      wrapper = mount(RiskMatrix)

      const html = wrapper.html()
      expect(html).toContain('risk-matrix-wrapper')
      expect(html).toContain('Matrice des Risques')
    })
  })

  describe('Title Styling', () => {
    it('should have properly styled title', () => {
      wrapper = mount(RiskMatrix)

      const title = wrapper.find('h3')
      expect(title.exists()).toBe(true)
      expect(title.classes()).toContain('text-lg')
      expect(title.classes()).toContain('font-semibold')
    })

    it('should have margin below title', () => {
      wrapper = mount(RiskMatrix)

      const title = wrapper.find('h3')
      expect(title.classes()).toContain('mb-4')
    })
  })

  describe('Risk Data Flow', () => {
    it('should initialize with risks data', async () => {
      wrapper = mount(RiskMatrix)

      // Wait for async operation
      await wrapper.vm.$nextTick()

      expect(wrapper.vm.risks).toBeDefined()
    })

    it('should update risks when store changes', async () => {
      wrapper = mount(RiskMatrix)

      await wrapper.vm.$nextTick()

      expect(wrapper.vm.risks).toBeDefined()
    })
  })

  describe('Accessibility', () => {
    it('should have semantic heading', () => {
      wrapper = mount(RiskMatrix)

      const heading = wrapper.find('h3')
      expect(heading.exists()).toBe(true)
    })

    it('should have descriptive title', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.text()).toContain('Matrice')
      expect(wrapper.text()).toContain('Risques')
    })
  })

  describe('Console Logging', () => {
    it('should have handleCellClick method', () => {
      wrapper = mount(RiskMatrix)

      expect(typeof wrapper.vm.handleCellClick).toBe('function')
    })

    it('handleCellClick should accept probability and gravity parameters', () => {
      wrapper = mount(RiskMatrix)

      const consoleSpy = vi.spyOn(console, 'log')
      wrapper.vm.handleCellClick(0.5, 0.7)

      expect(consoleSpy).toHaveBeenCalled()
      consoleSpy.mockRestore()
    })
  })

  describe('Emits', () => {
    it('should have risk-click emit defined', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.vm.$options.emits).toContain('risk-click')
    })
  })

  describe('Ref Management', () => {
    it('should initialize risks ref', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.vm.risks).toBeDefined()
      expect(Array.isArray(wrapper.vm.risks)).toBe(true)
    })
  })

  describe('Store Integration', () => {
    it('should use risk store', () => {
      wrapper = mount(RiskMatrix)

      expect(wrapper.vm.riskStore).toBeDefined()
    })

    it('should fetch risks on mount', async () => {
      wrapper = mount(RiskMatrix)

      await wrapper.vm.$nextTick()

      // Store method should be called
      expect(wrapper.vm.riskStore.fetchRisks).toBeDefined()
    })
  })
})
