const LEGACY_COMPANY_ROUTE_MAP: Record<string, string> = {
  '/company/contexte/profil': '/company/context/management-system',
  '/company/contexte/parties-interessees': '/company/context/stakeholders',
  '/company/contexte/domaine-application': '/company/context/application-scope',
  '/company/contexte/systeme-management': '/company/context/management-system',
  '/company/leadership/engagement': '/company/leadership/policy',
  '/company/leadership/consultation': '/company/leadership/consultation',
  '/company/leadership/job-description': '/company/leadership/fiche_poste',
  '/company/leadership/job-descriptions': '/company/leadership/fiche_poste',
  '/company/leadership/roles/fiche-poste': '/company/leadership/fiche_poste',
  '/company/leadership/roles/fiches': '/company/leadership/fiche_responsabilite',
  '/company/responsibilities': '/company/leadership/fiche_responsabilite',
  '/company/planification/risques': '/company/planning/risks-opportunities',
  '/company/planification/objectifs': '/company/planning/objectives',
  '/company/planification/plans-action': '/company/planning/action-plans',
  '/company/planification/indicateurs': '/company/indicators',
  '/company/iso/context/management-system': '/company/context/management-system',
  '/company/iso/context/management-system/new': '/company/context/management-system/new',
  '/company/iso/context/application-scope': '/company/context/application-scope',
  '/company/iso/context/application-scope/recap': '/company/context/application-scope/recap',
  '/company/iso/context/stakeholders': '/company/context/stakeholders',
  '/company/iso/context/swot-pestel': '/company/context/swot-pestel',
  '/company/iso/planning/risks-opportunities': '/company/planning/risks-opportunities',
  '/company/iso/planning/objectives': '/company/planning/objectives',
  '/company/iso/planning/action-plans': '/company/planning/action-plans',
  '/company/iso/operations/operational-planning-control': '/company/operations/operational-planning-control',
  '/company/iso/operations/product-service-requirements': '/company/operations/product-service-requirements',
  '/company/iso/operations/compliance-obligations': '/company/operations/compliance-obligations',
  '/company/iso/operations/conformity-obligations': '/company/operations/compliance-obligations',
  '/company/iso/operations/design-development-products-services': '/company/operations/design-development-products-services',
  '/company/iso/operations/provider-management': '/company/operations/provider-management',
  '/company/iso/operations/production-service-provision': '/company/operations/production-service-provision',
  '/company/iso/operations/release-products-services': '/company/operations/release-products-services',
  '/company/iso/operations/control-nonconforming-outputs': '/company/operations/control-nonconforming-outputs',
  '/company/iso/improvement/corrective-actions': '/company/actions',
  '/company/iso/improvement/continuous': '/company/improvement/continuous',
  '/company/iso/performance/indicators': '/company/indicators',
  '/company/iso/performance/management-review': '/company/management-reviews',
  '/company/iso/performance/accident-monitoring': '/company/indicators',
  '/company/iso/performance/incident-investigation': '/company/performance/revue-direction',
  '/company/iso/performance/security-incidents': '/company/performance/revue-direction',
  '/company/iso/performance/gdpr-compliance': '/company/performance/revue-direction',
  '/company/iso/performance/ccp-monitoring': '/company/indicators',
  '/company/iso/performance/haccp-verification': '/company/performance/revue-direction',
  '/company/iso/performance/microbiological-analysis': '/company/indicators',
  '/company/iso/performance/energy-monitoring': '/company/indicators',
  '/company/iso/performance/energy-compliance': '/company/performance/revue-direction',
  '/company/iso/performance/energy-audit': '/company/performance/audits',
  '/company/iso/support/training': '/company/support/training',
  '/company/iso/support/equipment': '/company/support/equipment',
  '/company/iso/support/communication': '/company/support/communication',
  '/company/iso/support/awareness': '/company/support/awareness',
  '/company/iso/support/document-inventory': '/company/support/document-inventory',
  '/company/support/ressources': '/company/support/equipment',
  '/company/support/competences': '/company/support/training',
  '/company/support/sensibilisation': '/company/support/awareness',
  '/company/support/awareness': '/company/support/awareness',
  '/company/support/communication': '/company/support/communication',
  '/company/support/competencies': '/company/support/training',
  '/company/support/resources': '/company/support/equipment',
  '/company/support/documents': '/company/support/document-inventory',
  '/company/non-conformities': '/company/nonconformities',
  '/company/subscriptions': '/company/subscription',
  '/company/planning/aspects-environmentaux': '/company/planning/risks-opportunities',
  '/company/planning/environmental-review': '/company/planning/risks-opportunities',
  '/company/performance/indicators': '/company/indicators',
  '/company/performance/management-review': '/company/management-reviews',
  '/company/performance/revue-processus': '/company/performance/surveillance/process-review',
  '/company/performance/process-review': '/company/performance/surveillance/process-review',
  '/company/operations/procedures': '/company/context/management-system',
  '/company/processes': '/company/context/management-system',
  '/company/processes/create': '/company/context/management-system?openCreate=1',
  '/company/processes/cartography': '/company/context/management-system',
  '/company/operations/operational-planning-control': '/company/operations/operational-planning-control',
  '/company/operations/product-service-requirements': '/company/operations/product-service-requirements',
  '/company/operations/compliance-obligations': '/company/operations/compliance-obligations',
  '/company/operations/obligations-conformite': '/company/operations/compliance-obligations',
  '/company/operations/obligations_conformite': '/company/operations/compliance-obligations',
  '/company/operations/conformity-obligations': '/company/operations/compliance-obligations',
  '/company/planning/obligations-conformite': '/company/operations/compliance-obligations',
  '/company/planning/obligations_conformite': '/company/operations/compliance-obligations',
  '/company/planning/compliance-obligations': '/company/operations/compliance-obligations',
  '/company/operations/design-development-products-services': '/company/operations/design-development-products-services',
  '/company/operations/provider-management': '/company/operations/provider-management',
  '/company/operations/production-service-provision': '/company/operations/production-service-provision',
  '/company/operations/release-products-services': '/company/operations/release-products-services',
  '/company/operations/control-nonconforming-outputs': '/company/operations/control-nonconforming-outputs',
  '/company/iso/planning/duerp': '/company/planning/duerp',
  '/company/improvement/corrective-actions': '/company/actions',
  '/company/improvement/continuous': '/company/improvement/continuous',
}

function normalizeToCompanyPath (rawRoute: string): string {
  const trimmed = rawRoute.trim()
  if (!trimmed) {
    return trimmed
  }

  if (trimmed === '/clienta') {
    return '/company'
  }

  if (trimmed.startsWith('clienta/')) {
    return `/${trimmed.replace(/^clienta\//, 'company/')}`
  }

  if (trimmed.startsWith('/clienta/')) {
    return trimmed.replace('/clienta/', '/company/')
  }

  if (trimmed.startsWith('/company/')) {
    return trimmed
  }

  if (trimmed.startsWith('/')) {
    return trimmed
  }

  return `/company/${trimmed}`
}

export function canonicalizeCompanyRoute (rawRoute: string | undefined, fallback: string): string {
  const candidate = normalizeToCompanyPath(rawRoute || fallback)
  if (!candidate) {
    return fallback
  }

  const [rawBaseWithQuery = '', hash = ''] = candidate.split('#')
  const [rawBasePath = '', query = ''] = rawBaseWithQuery.split('?')
  const basePath = rawBasePath || '/'
  const normalizedBasePath = basePath.endsWith('/') && basePath !== '/'
    ? basePath.slice(0, -1)
    : basePath

  const mappedBase = LEGACY_COMPANY_ROUTE_MAP[normalizedBasePath] || normalizedBasePath
  const withQuery = query ? `${mappedBase}?${query}` : mappedBase

  return hash ? `${withQuery}#${hash}` : withQuery
}
