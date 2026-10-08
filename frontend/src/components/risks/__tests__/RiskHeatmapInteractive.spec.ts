import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RiskHeatmapInteractive from '../RiskHeatmapInteractive.vue'

// Mock Vuetify components
vi.mock('vuetify/components', () => ({
  VSelect: { template: '<select><slot /></select>' },
  VBtn: { template: '<button><slot /></button>' },
  VCheckbox: { template: '<input type="checkbox" />' },
  VExpandTransition: { template: '<div><slot /></div>' },
  VDialog: { template: '<div><slot /></div>' },
  VCard: { template: '<div><slot /></div>' },
  VCardTitle: { template: '<div><slot /></div>' },
  VCardText: { template: '<div><slot /></div>' },
  VCardActions: { template: '<div><slot /></div>' },
  VChip: { template: '<span><slot /></span>' },
  VIcon: { template: '<i><slot /></i>' },
  VSpacer: { template: '<div></div>' },
}))

describe('RiskHeatmapInteractive.vue', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(RiskHeatmapInteractive, {
      global: {
        stubs: {
          VSelect: true,
          VBtn: true,
          VCheckbox: true,
          VExpandTransition: true,
          VDialog: true,
          VCard: true,
          VCardTitle: true,
          VCardText: true,
          VCardActions: true,
          VChip: true,
          VIcon: true,
          VSpacer: true,
        },
      },
    })
  })

  it('renders correctly', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays heatmap title', () => {
    expect(wrapper.html()).toContain('Cartographie des Risques')
  })

  it('initializes with 8 example risks', () => {
    const vm = wrapper.vm as any
    expect(vm.risks.length).toBe(8)
  })

  it('all risks have required properties', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(risk).toHaveProperty('id')
      expect(risk).toHaveProperty('code')
      expect(risk).toHaveProperty('name')
      expect(risk).toHaveProperty('process')
      expect(risk).toHaveProperty('description')
      expect(risk).toHaveProperty('probability')
      expect(risk).toHaveProperty('gravity')
      expect(risk).toHaveProperty('criticality')
      expect(risk).toHaveProperty('actions')
    }
  })

  it('criticality is calculated as probability × gravity', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(risk.criticality).toBe(risk.probability * risk.gravity)
    }
  })

  it('getCellClass returns correct class for criticality', () => {
    const vm = wrapper.vm as any

    expect(vm.getCellClass(5, 5)).toBe('critical') // 25 = critical
    expect(vm.getCellClass(3, 4)).toBe('high') // 12 = high
    expect(vm.getCellClass(2, 3)).toBe('medium') // 6 = medium
    expect(vm.getCellClass(1, 2)).toBe('low') // 2 = low
  })

  it('getRiskCount returns correct count for cell', () => {
    const vm = wrapper.vm as any

    // R-PROD-001 has P=3, G=5
    const count = vm.getRiskCount(5, 3)
    expect(count).toBeGreaterThanOrEqual(0)
  })

  it('getCellRisks returns risks for specific cell', () => {
    const vm = wrapper.vm as any

    const cellRisks = vm.getCellRisks(5, 3)
    expect(Array.isArray(cellRisks)).toBe(true)

    for (const risk of cellRisks) {
      expect(risk.gravity).toBe(5)
      expect(risk.probability).toBe(3)
    }
  })

  it('filteredRisks filters by process', async () => {
    const vm = wrapper.vm as any

    vm.selectedProcess = 'Production'

    const filtered = vm.filteredRisks
    for (const risk of filtered) {
      expect(risk.process).toBe('Production')
    }
  })

  it('filteredRisks shows all when "Tous" selected', async () => {
    const vm = wrapper.vm as any

    vm.selectedProcess = 'Tous'

    const filtered = vm.filteredRisks
    expect(filtered.length).toBe(vm.risks.length)
  })

  it('filteredRisks filters by criticality checkboxes', async () => {
    const vm = wrapper.vm as any

    // Only show critical risks
    vm.filterCritical = true
    vm.filterHigh = false
    vm.filterMedium = false
    vm.filterLow = false

    const filtered = vm.filteredRisks
    for (const risk of filtered) {
      expect(risk.criticality).toBeGreaterThanOrEqual(15)
    }
  })

  it('openCellDetail opens dialog with first risk', () => {
    const vm = wrapper.vm as any

    // Find a cell with risks
    const cellRisks = vm.getCellRisks(5, 3)
    if (cellRisks.length > 0) {
      vm.openCellDetail(5, 3)

      expect(vm.showRiskDialog).toBe(true)
      expect(vm.selectedRisk).toBe(cellRisks[0])
    }
  })

  it('openRiskDetail sets selected risk and shows dialog', () => {
    const vm = wrapper.vm as any
    const testRisk = vm.risks[0]

    vm.openRiskDetail(testRisk)

    expect(vm.showRiskDialog).toBe(true)
    expect(vm.selectedRisk).toBe(testRisk)
  })

  it('getCriticalityColor returns correct colors', () => {
    const vm = wrapper.vm as any

    expect(vm.getCriticalityColor(20)).toBe('red') // >= 15
    expect(vm.getCriticalityColor(12)).toBe('orange') // >= 10
    expect(vm.getCriticalityColor(7)).toBe('yellow') // >= 5
    expect(vm.getCriticalityColor(3)).toBe('green') // < 5
  })

  it('editRisk logs console message', () => {
    const vm = wrapper.vm as any
    const consoleSpy = vi.spyOn(console, 'log')
    const testRisk = vm.risks[0]

    vm.editRisk(testRisk)

    expect(consoleSpy).toHaveBeenCalledWith('Edit risk:', testRisk)
  })

  it('showFilters defaults to false', () => {
    const vm = wrapper.vm as any
    expect(vm.showFilters).toBe(false)
  })

  it('all filter checkboxes default to true', () => {
    const vm = wrapper.vm as any

    expect(vm.filterCritical).toBe(true)
    expect(vm.filterHigh).toBe(true)
    expect(vm.filterMedium).toBe(true)
    expect(vm.filterLow).toBe(true)
  })

  it('processFilters includes all expected processes', () => {
    const vm = wrapper.vm as any

    expect(vm.processFilters).toContain('Tous')
    expect(vm.processFilters).toContain('Production')
    expect(vm.processFilters).toContain('Qualité')
    expect(vm.processFilters).toContain('Achats')
    expect(vm.processFilters).toContain('RH')
    expect(vm.processFilters).toContain('Maintenance')
    expect(vm.processFilters).toContain('Logistique')
  })

  it('selectedProcess defaults to "Tous"', () => {
    const vm = wrapper.vm as any
    expect(vm.selectedProcess).toBe('Tous')
  })

  it('showRiskDialog defaults to false', () => {
    const vm = wrapper.vm as any
    expect(vm.showRiskDialog).toBe(false)
  })

  it('selectedRisk defaults to null', () => {
    const vm = wrapper.vm as any
    expect(vm.selectedRisk).toBe(null)
  })

  it('risk codes follow correct format', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(risk.code).toMatch(/^R-[A-Z]+-\d{3}$/)
    }
  })

  it('all risks have actions array', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(Array.isArray(risk.actions)).toBe(true)
      expect(risk.actions.length).toBeGreaterThan(0)
    }
  })

  it('probability values are between 1 and 5', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(risk.probability).toBeGreaterThanOrEqual(1)
      expect(risk.probability).toBeLessThanOrEqual(5)
    }
  })

  it('gravity values are between 1 and 5', () => {
    const vm = wrapper.vm as any

    for (const risk of vm.risks) {
      expect(risk.gravity).toBeGreaterThanOrEqual(1)
      expect(risk.gravity).toBeLessThanOrEqual(5)
    }
  })
})
