import { describe, expect, it } from 'vitest'
import { canonicalizeCompanyRoute } from '@/modules/clienta/utils/routeCanonicalizer'

describe('canonicalizeCompanyRoute', () => {
  it('maps legacy process review aliases to the canonical path', () => {
    expect(canonicalizeCompanyRoute('/company/performance/revue-processus', '/company/dashboard'))
      .toBe('/company/performance/surveillance/process-review')

    expect(canonicalizeCompanyRoute('/company/performance/process-review', '/company/dashboard'))
      .toBe('/company/performance/surveillance/process-review')
  })

  it('keeps canonical process review path unchanged', () => {
    expect(canonicalizeCompanyRoute('/company/performance/surveillance/process-review', '/company/dashboard'))
      .toBe('/company/performance/surveillance/process-review')
  })

  it('maps consultation and duerp aliases to canonical paths', () => {
    expect(canonicalizeCompanyRoute('/company/leadership/consultation', '/company/dashboard'))
      .toBe('/company/leadership/consultation')

    expect(canonicalizeCompanyRoute('/company/iso/planning/duerp', '/company/dashboard'))
      .toBe('/company/planning/duerp')
  })

  it('preserves query strings and hash fragments while canonicalizing', () => {
    expect(canonicalizeCompanyRoute('/company/performance/revue-processus?site=3#report', '/company/dashboard'))
      .toBe('/company/performance/surveillance/process-review?site=3#report')
  })

  it('maps advanced iso performance routes to canonical pivots', () => {
    expect(canonicalizeCompanyRoute('/company/iso/performance/accident-monitoring', '/company/dashboard'))
      .toBe('/company/indicators')

    expect(canonicalizeCompanyRoute('/company/iso/performance/incident-investigation', '/company/dashboard'))
      .toBe('/company/performance/revue-direction')

    expect(canonicalizeCompanyRoute('/company/iso/performance/energy-audit', '/company/dashboard'))
      .toBe('/company/performance/audits')
  })

  it('normalizes legacy clienta prefixes to company paths', () => {
    expect(canonicalizeCompanyRoute('/clienta/performance/surveillance', '/company/dashboard'))
      .toBe('/company/performance/surveillance')

    expect(canonicalizeCompanyRoute('/clienta', '/company/dashboard'))
      .toBe('/company')

    expect(canonicalizeCompanyRoute('clienta/performance/revue-processus', '/company/dashboard'))
      .toBe('/company/performance/surveillance/process-review')
  })
})
