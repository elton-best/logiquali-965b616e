import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import GlobalSearchBar from '../GlobalSearchBar.vue'

// Mock fetch
global.fetch = vi.fn()

const mockRouter = createRouter({
  history: createMemoryHistory(),
  routes: [
    { path: '/documents/:id', name: 'document' },
    { path: '/processes/:id', name: 'process' },
    { path: '/risks/:id', name: 'risk' },
  ],
})

describe('GlobalSearchBar.vue', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    localStorage.setItem('token', 'test-token')
  })

  const createWrapper = (props = {}) => {
    return mount(GlobalSearchBar, {
      props,
      global: {
        plugins: [mockRouter],
        stubs: {
          VTextField: {
            template: '<input v-bind="$attrs" @input="$emit(\'input\', $event)" @keydown.enter="$emit(\'keydown.enter\')" />',
          },
          VBtn: { template: '<button><slot /></button>' },
          VCard: { template: '<div><slot /></div>' },
          VExpandTransition: { template: '<div><slot /></div>' },
          VRow: { template: '<div><slot /></div>' },
          VCol: { template: '<div><slot /></div>' },
          VSelect: { template: '<select v-model="$attrs.modelValue"><slot /></select>' },
          VMenu: { template: '<div><slot /></div>' },
          VList: { template: '<div><slot /></div>' },
          VListSubheader: { template: '<div><slot /></div>' },
          VListItem: { template: '<div @click="$emit(\'click\')"><slot /></div>' },
          VListItemTitle: { template: '<div><slot /></div>' },
          VListItemSubtitle: { template: '<div><slot /></div>' },
          VIcon: { template: '<i />' },
          VChip: { template: '<span><slot /></span>' },
          VSpacer: { template: '<div />' },
        },
      },
    })
  }

  it('renders successfully', () => {
    wrapper = createWrapper()
    expect(wrapper.exists()).toBe(true)
  })

  it('accepts custom placeholder prop', () => {
    wrapper = createWrapper({
      placeholder: 'Custom search...',
    })
    expect(wrapper.props('placeholder')).toBe('Custom search...')
  })

  it('does not search if query is less than 2 characters', async () => {
    wrapper = createWrapper()

    wrapper.vm.searchQuery = 'a'
    await wrapper.vm.performSearch()

    expect(global.fetch).not.toHaveBeenCalled()
    expect(wrapper.vm.results).toEqual([])
  })

  it('clears search results when clearSearch called', async () => {
    wrapper = createWrapper()

    wrapper.vm.searchQuery = 'test'
    wrapper.vm.results = [{ id: 1, title: 'Test' }]
    wrapper.vm.showResults = true

    await wrapper.vm.clearSearch()

    expect(wrapper.vm.searchQuery).toBe('')
    expect(wrapper.vm.results).toEqual([])
    expect(wrapper.vm.showResults).toBe(false)
  })

  it('returns correct icon for each type', () => {
    wrapper = createWrapper()

    expect(wrapper.vm.getTypeIcon('document')).toBe('mdi-file-document')
    expect(wrapper.vm.getTypeIcon('process')).toBe('mdi-cog')
    expect(wrapper.vm.getTypeIcon('risk')).toBe('mdi-alert-circle')
    expect(wrapper.vm.getTypeIcon('non_conformity')).toBe('mdi-alert-octagon')
  })

  it('returns correct color for each type', () => {
    wrapper = createWrapper()

    expect(wrapper.vm.getTypeColor('document')).toBe('blue')
    expect(wrapper.vm.getTypeColor('process')).toBe('purple')
    expect(wrapper.vm.getTypeColor('risk')).toBe('orange')
    expect(wrapper.vm.getTypeColor('non_conformity')).toBe('red')
  })

  it('returns correct label for each type', () => {
    wrapper = createWrapper()

    expect(wrapper.vm.getTypeLabel('document')).toBe('Document')
    expect(wrapper.vm.getTypeLabel('process')).toBe('Processus')
    expect(wrapper.vm.getTypeLabel('risk')).toBe('Risque')
    expect(wrapper.vm.getTypeLabel('non_conformity')).toBe('NC')
  })

  it('toggles filters panel visibility', async () => {
    wrapper = createWrapper({ showFilters: true })

    expect(wrapper.vm.filtersVisible).toBe(false)

    await wrapper.vm.toggleFilters()
    expect(wrapper.vm.filtersVisible).toBe(true)

    await wrapper.vm.toggleFilters()
    expect(wrapper.vm.filtersVisible).toBe(false)
  })
})
