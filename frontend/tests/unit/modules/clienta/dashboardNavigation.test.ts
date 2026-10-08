import { describe, expect, it } from 'vitest'
import {
  DASHBOARD_QUICK_ACTIONS,
  DASHBOARD_STAT_ROUTES,
} from '@/modules/clienta/constants/dashboardNavigation'

describe('dashboardNavigation constants', () => {
  it('defines expected quick actions', () => {
    expect(DASHBOARD_QUICK_ACTIONS.length).toBe(4)
    expect(DASHBOARD_QUICK_ACTIONS.map(action => action.id)).toEqual([
      'create-document',
      'report-nc',
      'plan-audit',
      'add-collaborator',
    ])
  })

  it('defines required stat routes', () => {
    expect(DASHBOARD_STAT_ROUTES[1]?.route).toBe('/company/sites')
    expect(DASHBOARD_STAT_ROUTES[2]?.route).toBe('/company/users')
    expect(DASHBOARD_STAT_ROUTES[6]?.permissions).toContain('processes.read')
  })
})
