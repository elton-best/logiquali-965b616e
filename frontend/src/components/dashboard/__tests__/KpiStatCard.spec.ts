import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import KpiStatCard from '../KpiStatCard.vue'

// Mock Vuetify components
vi.mock('vuetify/components', () => ({
  VIcon: { template: '<i><slot /></i>' },
}))

describe('KpiStatCard.vue', () => {
  let wrapper: any

  const defaultProps = {
    title: 'Test KPI',
    value: '95.5',
    unit: '%',
    trend: 'up' as const,
    trendValue: '+2.5%',
    icon: 'mdi-test',
    color: 'green',
  }

  beforeEach(() => {
    wrapper = mount(KpiStatCard, {
      props: defaultProps,
      global: {
        stubs: {
          VIcon: true,
        },
      },
    })
  })

  it('renders correctly', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays title', () => {
    expect(wrapper.html()).toContain('Test KPI')
  })

  it('displays value and unit', () => {
    expect(wrapper.html()).toContain('95.5')
    expect(wrapper.html()).toContain('%')
  })

  it('displays trend value when trend is not stable', () => {
    expect(wrapper.html()).toContain('+2.5%')
  })

  it('does not display trend when trend is stable', async () => {
    await wrapper.setProps({ trend: 'stable' })

    const trendContainer = wrapper.find('.stat-trend')
    expect(trendContainer.exists()).toBe(false)
  })

  it('applies green border color class for green color', () => {
    expect(wrapper.vm.borderColorClass).toBe('border-success')
  })

  it('applies correct icon background class', () => {
    expect(wrapper.vm.iconBackgroundClass).toBe('bg-success')
  })

  it('supports different color options', async () => {
    const colorTests = [
      { color: 'green', border: 'border-success', bg: 'bg-success' },
      { color: 'orange', border: 'border-warning', bg: 'bg-warning' },
      { color: 'blue', border: 'border-primary', bg: 'bg-primary' },
      { color: 'red', border: 'border-danger', bg: 'bg-danger' },
      { color: 'purple', border: 'border-purple', bg: 'bg-purple' },
      { color: 'teal', border: 'border-teal', bg: 'bg-teal' },
    ]

    for (const test of colorTests) {
      await wrapper.setProps({ color: test.color })
      expect(wrapper.vm.borderColorClass).toBe(test.border)
      expect(wrapper.vm.iconBackgroundClass).toBe(test.bg)
    }
  })

  it('defaults to gray for unknown color', async () => {
    await wrapper.setProps({ color: 'unknown' })

    expect(wrapper.vm.borderColorClass).toBe('border-primary')
    expect(wrapper.vm.iconBackgroundClass).toBe('bg-primary')
  })

  it('displays icon with correct color', () => {
    const icon = wrapper.findComponent({ name: 'VIcon' })
    expect(icon.exists()).toBe(true)
  })

  it('can handle numeric value', async () => {
    await wrapper.setProps({ value: 123 })

    expect(wrapper.html()).toContain('123')
  })

  it('can handle string value', async () => {
    await wrapper.setProps({ value: 'N/A' })

    expect(wrapper.html()).toContain('N/A')
  })

  it('unit prop is optional', async () => {
    await wrapper.setProps({ unit: undefined })

    // Should still render without error
    expect(wrapper.exists()).toBe(true)
  })

  it('shows up trend icon for positive trend', async () => {
    await wrapper.setProps({ trend: 'up' })

    // Icon is mocked as VIcon-stub, just verify trend container exists
    const trendContainer = wrapper.find('.stat-trend')
    expect(trendContainer.exists()).toBe(true)
  })

  it('shows down trend icon for negative trend', async () => {
    await wrapper.setProps({ trend: 'down' })

    // Icon is mocked as VIcon-stub, just verify trend container exists
    const trendContainer = wrapper.find('.stat-trend')
    expect(trendContainer.exists()).toBe(true)
  })

  it('applies green text color for up trend', async () => {
    await wrapper.setProps({ trend: 'up' })

    expect(wrapper.find('.trend-value').classes()).toContain('trend-up')
  })

  it('applies red text color for down trend', async () => {
    await wrapper.setProps({ trend: 'down' })

    expect(wrapper.find('.trend-value').classes()).toContain('trend-down')
  })
})
