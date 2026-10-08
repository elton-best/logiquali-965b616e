import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ChartWidget from '../ChartWidget.vue'

describe('ChartWidget Component', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Props and Rendering', () => {
    it('should render with title', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Sales Chart',
        },
      })

      expect(wrapper.text()).toContain('Sales Chart')
    })

    it('should render title as h3 heading', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Analytics',
        },
      })

      const h3 = wrapper.find('h3')
      expect(h3.exists()).toBe(true)
      expect(h3.text()).toContain('Analytics')
    })

    it('should not render subtitle by default', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const subtitle = wrapper.find('p')
      expect(subtitle.exists()).toBe(false)
    })

    it('should render subtitle when provided', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'Last 30 days',
        },
      })

      expect(wrapper.text()).toContain('Last 30 days')
    })

    it('should render subtitle as paragraph', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'Subtitle text',
        },
      })

      const p = wrapper.find('p')
      expect(p.exists()).toBe(true)
      expect(p.text()).toContain('Subtitle text')
    })
  })

  describe('Loading State', () => {
    it('should not show loading spinner by default', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const spinner = wrapper.find('[class*="animate-spin"]')
      expect(spinner.exists()).toBe(false)
    })

    it('should show loading spinner when loading is true', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          loading: true,
        },
      })

      const spinner = wrapper.find('[class*="animate-spin"]')
      expect(spinner.exists()).toBe(true)
    })

    it('should hide slot content when loading', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          loading: true,
        },
        slots: {
          default: '<div>Chart content</div>',
        },
      })

      expect(wrapper.text()).not.toContain('Chart content')
    })

    it('should show slot content when not loading', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          loading: false,
        },
        slots: {
          default: '<div class="chart-content">Chart content</div>',
        },
      })

      expect(wrapper.find('.chart-content').exists()).toBe(true)
      expect(wrapper.text()).toContain('Chart content')
    })

    it('should show loading message container with correct height', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          loading: true,
        },
      })

      const loadingContainer = wrapper.find('[class*="h-64"]')
      expect(loadingContainer.exists()).toBe(true)
    })
  })

  describe('Toolbar Actions', () => {
    it('should render export button', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBeGreaterThanOrEqual(1)
    })

    it('should render fullscreen button', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBeGreaterThanOrEqual(2)
    })

    it('should have two action buttons', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons).toHaveLength(2)
    })

    it('should emit export event when export button clicked', async () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      await buttons[0].trigger('click')

      expect(wrapper.emitted('export')).toBeTruthy()
      expect(wrapper.emitted('export')).toHaveLength(1)
    })

    it('should emit fullscreen event when fullscreen button clicked', async () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      await buttons[1].trigger('click')

      expect(wrapper.emitted('fullscreen')).toBeTruthy()
      expect(wrapper.emitted('fullscreen')).toHaveLength(1)
    })

    it('should have export button with title attribute', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons[0].attributes('title')).toContain('Exporter')
    })

    it('should have fullscreen button with title attribute', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      expect(buttons[1].attributes('title')).toContain('écran')
    })
  })

  describe('Icons', () => {
    it('should render download icon in export button', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const html = wrapper.html()
      expect(html.toLowerCase()).toMatch(/download|export|save/i)
    })

    it('should render maximize icon in fullscreen button', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const html = wrapper.html()
      expect(html.toLowerCase()).toMatch(/maximize|fullscreen|expand/i)
    })
  })

  describe('Styling and Layout', () => {
    it('should have card styling', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const card = wrapper.find('.card')
      expect(card.exists()).toBe(true)
    })

    it('should have padding', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const card = wrapper.find('.card')
      expect(card.classes()).toContain('p-6')
    })

    it('should have proper header spacing', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const header = wrapper.find('[class*="flex"][class*="justify-between"]')
      expect(header.exists()).toBe(true)
    })

    it('should have button styling', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      for (const button of buttons) {
        expect(button.classes()).toContain('p-2')
      }
    })

    it('should have hover effects on buttons', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      for (const button of buttons) {
        expect(button.classes()).toContain('hover:bg-neutral-100')
      }
    })
  })

  describe('Slot Content', () => {
    it('should render slot content', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
        slots: {
          default: '<canvas id="myChart"></canvas>',
        },
      })

      expect(wrapper.find('#myChart').exists()).toBe(true)
    })

    it('should render multiple slot elements', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
        slots: {
          default: `
            <div class="chart-area">
              <canvas id="chart"></canvas>
              <legend>Legend</legend>
            </div>
          `,
        },
      })

      expect(wrapper.find('.chart-area').exists()).toBe(true)
      expect(wrapper.find('canvas').exists()).toBe(true)
    })

    it('should position slot content within card', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
        slots: {
          default: '<div id="content"></div>',
        },
      })

      // Verify slot content is rendered
      const content = wrapper.find('#content')
      const card = wrapper.find('.card')
      expect(content.exists()).toBe(true)
      expect(card.exists()).toBe(true)
    })
  })

  describe('Typography', () => {
    it('should have proper title font size', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const h3 = wrapper.find('h3')
      expect(h3.classes()).toContain('text-lg')
    })

    it('should have proper title font weight', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const h3 = wrapper.find('h3')
      expect(h3.classes()).toContain('font-semibold')
    })

    it('should have proper subtitle font size', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'Subtitle',
        },
      })

      const p = wrapper.find('p')
      expect(p.classes()).toContain('text-sm')
    })

    it('should have proper spacing between title and subtitle', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'Subtitle',
        },
      })

      const p = wrapper.find('p')
      expect(p.classes()).toContain('mt-1')
    })
  })

  describe('Responsive Design', () => {
    it('should render correctly with long title', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'This is a very long chart title that might wrap on smaller screens',
        },
      })

      expect(wrapper.find('h3').exists()).toBe(true)
      expect(wrapper.text()).toContain('very long')
    })

    it('should render correctly with long subtitle', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'This is a very long subtitle describing the chart',
        },
      })

      expect(wrapper.find('p').exists()).toBe(true)
      expect(wrapper.text()).toContain('very long')
    })
  })

  describe('Dark Mode Support', () => {
    it('should have dark mode text colors for title', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const h3 = wrapper.find('h3')
      expect(h3.classes()).toContain('dark:text-neutral-50')
    })

    it('should have dark mode text colors for subtitle', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
          subtitle: 'Subtitle',
        },
      })

      const p = wrapper.find('p')
      expect(p.classes()).toContain('dark:text-neutral-400')
    })

    it('should have dark mode hover colors for buttons', () => {
      wrapper = mount(ChartWidget, {
        props: {
          title: 'Chart',
        },
      })

      const buttons = wrapper.findAll('button')
      for (const button of buttons) {
        expect(button.classes()).toContain('dark:hover:bg-neutral-800')
      }
    })
  })
})
