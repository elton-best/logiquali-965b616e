import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import TurtleCell from '../TurtleCell.vue'

vi.mock('vuetify/components', () => ({
  VIcon: { template: '<i><slot /></i>' },
  VBtn: { template: '<button><slot /></button>' },
}))

describe('TurtleCell.vue', () => {
  let wrapper: any

  const defaultProps = {
    title: 'Test Title',
    subtitle: 'Test Subtitle',
    icon: 'mdi-test',
    color: 'blue',
    items: ['Item 1', 'Item 2'],
  }

  beforeEach(() => {
    wrapper = mount(TurtleCell, {
      props: defaultProps,
      global: {
        stubs: {
          VIcon: true,
          VBtn: true,
        },
      },
    })
  })

  it('renders correctly', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays title and subtitle', () => {
    expect(wrapper.html()).toContain('Test Title')
    expect(wrapper.html()).toContain('Test Subtitle')
  })

  it('applies correct background color', () => {
    expect(wrapper.vm.cellBackgroundColor).toBe('bg-blue-50')
  })

  it('emits add event', async () => {
    await wrapper.vm.handleAdd()
    expect(wrapper.emitted('add')).toBeTruthy()
  })
})
