import { describe, expect, it } from 'vitest'
import { buildManagementDuerpSummary } from '@/modules/clienta/pages/performance/surveillance/process-review/utils/managementSummary'

describe('buildManagementDuerpSummary', () => {
  it('returns a fallback sentence when all DUERP/AES metrics are empty', () => {
    const summary = buildManagementDuerpSummary({
      duerp_versions_count: 0,
      duerp_dangers_count: 0,
      duerp_unacceptable_count: 0,
      aes_total_count: 0,
      aes_significant_count: 0,
    })

    expect(summary).toContain('Aucune donnée DUERP/AES consolidée')
  })

  it('builds an actionable summary when DUERP/AES metrics are available', () => {
    const summary = buildManagementDuerpSummary({
      duerp_versions_count: 2,
      duerp_dangers_count: 11,
      duerp_unacceptable_count: 3,
      aes_total_count: 7,
      aes_significant_count: 4,
    })

    expect(summary).toContain('DUERP: 2 version(s), 11 danger(s) identifié(s), 3 danger(s) critique(s)')
    expect(summary).toContain('AES: 7 aspect(s) environnemental(aux), dont 4 significatif(s).')
    expect(summary).toContain('Priorités recommandées')
  })

  it('sanitizes invalid values to safe non-negative integers', () => {
    const summary = buildManagementDuerpSummary({
      duerp_versions_count: -1,
      duerp_dangers_count: Number.NaN,
      duerp_unacceptable_count: undefined,
      aes_total_count: 2.8,
      aes_significant_count: -5,
    })

    expect(summary).toContain('DUERP: 0 version(s), 0 danger(s) identifié(s), 0 danger(s) critique(s)')
    expect(summary).toContain('AES: 3 aspect(s) environnemental(aux), dont 0 significatif(s).')
  })
})
