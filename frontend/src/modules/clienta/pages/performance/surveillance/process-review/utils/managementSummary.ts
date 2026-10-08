export interface ProcessReviewManagementMetrics {
  duerp_versions_count?: number
  duerp_dangers_count?: number
  duerp_unacceptable_count?: number
  aes_total_count?: number
  aes_significant_count?: number
}

function toSafeInt (value: unknown): number {
  const parsed = Number(value)
  if (!Number.isFinite(parsed) || parsed < 0) {
    return 0
  }
  return Math.round(parsed)
}

export function buildManagementDuerpSummary (metrics: ProcessReviewManagementMetrics): string {
  const duerpVersions = toSafeInt(metrics.duerp_versions_count)
  const duerpDangers = toSafeInt(metrics.duerp_dangers_count)
  const duerpCritical = toSafeInt(metrics.duerp_unacceptable_count)
  const aesTotal = toSafeInt(metrics.aes_total_count)
  const aesSignificant = toSafeInt(metrics.aes_significant_count)

  const hasNoData = duerpVersions === 0
    && duerpDangers === 0
    && duerpCritical === 0
    && aesTotal === 0
    && aesSignificant === 0

  if (hasNoData) {
    return 'Aucune donnée DUERP/AES consolidée pour la période. Vérifier la disponibilité des versions DUERP et des aspects environnementaux significatifs.'
  }

  return [
    `Synthèse automatique - DUERP: ${duerpVersions} version(s), ${duerpDangers} danger(s) identifié(s), ${duerpCritical} danger(s) critique(s) (niveau unacceptable).`,
    `AES: ${aesTotal} aspect(s) environnemental(aux), dont ${aesSignificant} significatif(s).`,
    'Priorités recommandées: traiter les dangers critiques en priorité, puis suivre les aspects significatifs via plans d\'actions et vérification en revue suivante.',
  ].join(' ')
}
