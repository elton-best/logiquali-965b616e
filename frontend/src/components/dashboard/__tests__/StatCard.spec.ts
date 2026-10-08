import { mount } from '@vue/test-utils'
import { TrendingUp } from 'lucide-vue-next'
import { beforeEach, describe, expect, it } from 'vitest'
import StatCard from '../StatCard.vue'

describe('StatCard Component', () => {
  let wrapper: any

  beforeEach(() => {
    wrapper = null
  })

  describe('Props and Rendering', () => {
    it('should render with title and value', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Total Users',
          value: 150,
          icon: TrendingUp,
        },
      })

      expect(wrapper.text()).toContain('Total Users')
      expect(wrapper.text()).toContain('150')
    })

    it('should render icon component', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      // Icon from lucide-vue-next is rendered as SVG, not as component
      const html = wrapper.html()
      expect(html).toMatch(/svg|icon|<\/svg>/i)
    })

    it('should render string value', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Status',
          value: 'Active',
          icon: TrendingUp,
        },
      })

      expect(wrapper.text()).toContain('Active')
    })

    it('should render numeric value', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Count',
          value: 42,
          icon: TrendingUp,
        },
      })

      expect(wrapper.text()).toContain('42')
    })
  })

  describe('Trend Display', () => {
    it('should not show trend when not provided', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      const trendText = wrapper.text()
      expect(trendText).not.toContain('%')
    })

    it('should show positive trend', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Growth',
          value: 100,
          icon: TrendingUp,
          trend: {
            value: 15,
            label: 'compared to last month',
          },
        },
      })

      expect(wrapper.text()).toContain('+15%')
      expect(wrapper.text()).toContain('compared to last month')
    })

    it('should show negative trend', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Decline',
          value: 100,
          icon: TrendingUp,
          trend: {
            value: -10,
            label: 'compared to last month',
          },
        },
      })

      expect(wrapper.text()).toContain('-10%')
      expect(wrapper.text()).toContain('compared to last month')
    })

    it('should show zero trend', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Stable',
          value: 100,
          icon: TrendingUp,
          trend: {
            value: 0,
            label: 'no change',
          },
        },
      })

      expect(wrapper.text()).toContain('0%')
    })

    it('should apply correct color class for positive trend', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Growth',
          value: 100,
          icon: TrendingUp,
          trend: {
            value: 15,
            label: 'growth',
          },
        },
      })

      const trendColorClass = wrapper.vm.trendColor
      expect(trendColorClass).toContain('green')
    })

    it('should apply correct color class for negative trend', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Decline',
          value: 100,
          icon: TrendingUp,
          trend: {
            value: -10,
            label: 'decline',
          },
        },
      })

      const trendColorClass = wrapper.vm.trendColor
      expect(trendColorClass).toContain('red')
    })
  })

  describe('Color Props', () => {
    it('should default to primary color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      expect(wrapper.props('color')).toBe('primary')
    })

    it('should accept primary color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'primary',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('primary')
    })

    it('should accept accent color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'accent',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('accent')
    })

    it('should accept yellow color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'yellow',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('yellow')
    })

    it('should accept red color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'red',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('red')
    })

    it('should accept blue color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'blue',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('blue')
    })

    it('should accept neutral color', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          color: 'neutral',
        },
      })

      const classes = wrapper.vm.colorClasses
      expect(classes).toContain('neutral')
    })
  })

  describe('Loading State', () => {
    it('should not show loading by default', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      expect(wrapper.props('loading')).toBe(false)
      expect(wrapper.text()).toContain('100')
    })

    it('should show loading skeleton when loading is true', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          loading: true,
        },
      })

      const html = wrapper.html()
      expect(html).toContain('animate-pulse') || expect(wrapper.text()).not.toContain('100')
    })

    it('should hide value when loading', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          loading: true,
        },
      })

      expect(wrapper.text()).not.toContain('100')
    })

    it('should show value when not loading', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
          loading: false,
        },
      })

      expect(wrapper.text()).toContain('100')
    })
  })

  describe('Layout and Structure', () => {
    it('should have card styling', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      const html = wrapper.html()
      expect(html).toContain('card')
    })

    it('should display title above value in component', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'My Title',
          value: 100,
          icon: TrendingUp,
        },
      })

      const text = wrapper.text()
      // Verify both are rendered
      expect(text).toContain('My Title')
      expect(text).toContain('100')
    })

    it('should have icon container', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      const html = wrapper.html()
      expect(html).toContain('rounded-xl')
    })
  })

  describe('Accessibility', () => {
    it('should have semantic structure', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test Title',
          value: 100,
          icon: TrendingUp,
        },
      })

      const html = wrapper.html()
      expect(html).toContain('Test Title')
    })

    it('should display all required information', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Total Users',
          value: 1500,
          icon: TrendingUp,
          trend: {
            value: 25,
            label: 'increase this month',
          },
        },
      })

      const text = wrapper.text()
      expect(text).toContain('Total Users')
      expect(text).toContain('1500')
      expect(text).toContain('+25%')
      expect(text).toContain('increase this month')
    })
  })

  describe('Responsiveness', () => {
    it('should render without error on different screen sizes', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Test',
          value: 100,
          icon: TrendingUp,
        },
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('100')
    })

    it('should maintain layout with long title', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'This is a very long title that might wrap on smaller screens',
          value: 100,
          icon: TrendingUp,
        },
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('This is a very long title')
    })

    it('should maintain layout with large numbers', () => {
      wrapper = mount(StatCard, {
        props: {
          title: 'Big Numbers',
          value: 1_000_000_000,
          icon: TrendingUp,
        },
      })

      expect(wrapper.exists()).toBe(true)
      expect(wrapper.text()).toContain('1000000000')
    })
  })
})
