import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
// import TurtleCell from '../TurtleCell.vue'
import TurtleDiagramEditor from '../TurtleDiagramEditor.vue'

// Mock Vuetify components
vi.mock('vuetify/components', () => ({
  VBtn: { template: '<button><slot /></button>' },
  VIcon: { template: '<i><slot /></i>' },
  VExpansionPanels: { template: '<div><slot /></div>' },
  VExpansionPanel: { template: '<div><slot /></div>' },
  VExpansionPanelTitle: { template: '<div><slot /></div>' },
  VExpansionPanelText: { template: '<div><slot /></div>' },
  VChip: { template: '<span><slot /></span>' },
  VSnackbar: { template: '<div><slot /></div>' },
}))

describe('TurtleDiagramEditor.vue', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = mount(TurtleDiagramEditor, {
      props: {
        processId: 1,
        process: { title: 'Test Process' },
      },
      global: {
        stubs: {
          TurtleCell: true,
          VBtn: true,
          VIcon: true,
          VExpansionPanels: true,
          VExpansionPanel: true,
          VExpansionPanelTitle: true,
          VExpansionPanelText: true,
          VChip: true,
          VSnackbar: true,
        },
      },
    })
  })

  it('renders correctly', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('displays process title', () => {
    expect(wrapper.html()).toContain('Test Process')
  })

  it('initializes diagram data with arrays', () => {
    const vm = wrapper.vm as any
    expect(vm.diagram).toBeDefined()
    expect(vm.diagram.who).toBeInstanceOf(Array)
    expect(vm.diagram.risks).toBeInstanceOf(Array)
  })

  it('saveDiagram emits saved event', async () => {
    const vm = wrapper.vm as any
    await vm.saveDiagram()
    expect(wrapper.emitted('saved')).toBeTruthy()
  })
})
