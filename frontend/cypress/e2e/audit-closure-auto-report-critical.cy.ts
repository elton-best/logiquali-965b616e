describe('Flux critique - Clôture audit + rapport auto', () => {
  const email = Cypress.env('e2eEmail') || 'aelvis@best-experts-group.com'
  const password = Cypress.env('e2ePassword') || 'SuperAdmin@2024!'

  function ensureCompanySession (requiredPermissions: string[] = []) {
    cy.visit('/auth/login', { failOnStatusCode: false })
    cy.window().then(win => {
      const raw = win.localStorage.getItem('user')
      const base = raw ? JSON.parse(raw) : {}
      const user = {
        ...base,
        user_type: 'company',
        enterprise: {
          id: Number(base?.enterprise?.id || 1),
          status: 'active',
          domaine_activite_set: true,
          enterprise_subscriptions: [{ is_active: true }],
          sites: [{ id: 1, name: 'Site E2E', is_headquarter: true }],
          ...base?.enterprise,
        },
        site: {
          id: Number(base?.site?.id || 1),
          name: String(base?.site?.name || 'Site E2E'),
        },
      }

      const required = ['audits.read', ...requiredPermissions]
      const existing = Array.isArray(user?.active_scoped_permissions) ? user.active_scoped_permissions : []
      user.active_scoped_permissions = Array.from(new Set([...existing, ...required]))
      const directPermissions = Array.isArray(user?.permissions) ? user.permissions : []
      user.permissions = Array.from(new Set([...directPermissions, ...required]))

      win.localStorage.setItem('user', JSON.stringify(user))
      if (!win.localStorage.getItem('access_token')) {
        win.localStorage.setItem('access_token', 'e2e-company-token')
      }
      win.localStorage.setItem('current_site_id', String(user.site.id || 1))
    })
  }

  beforeEach(() => {
    cy.login(email, password)
    ensureCompanySession(['audits.read'])
    cy.intercept('GET', '**/api/v1/sites**', {
      statusCode: 200,
      body: { data: [{ id: 1, name: 'Site E2E' }] },
    })
    cy.intercept('GET', '**/api/v1/subscription/status**', {
      statusCode: 200,
      body: { hasActiveSubscription: true, can_access_dashboard: true, status: 'active' },
    })
    cy.intercept('GET', '**/api/v1/enterprises/**/config**', {
      statusCode: 200,
      body: { data: { id: 1, domaine_activite_set: true } },
    })
    cy.intercept('GET', '**/api/v1/geo/countries**', { statusCode: 200, body: [] })
    cy.intercept('GET', '**/api/v1/geo/countries/*/cities**', { statusCode: 200, body: [] })
  })

  it('accède au détail audit et affiche les actions de rapport', () => {
    cy.intercept('GET', '**/api/v1/audits/1*', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          reference: 'AUD-2026-001',
          title: 'Audit interne QHSE',
          type: 'interne',
          standard: 'ISO9001',
          site: { id: 1, name: 'Site E2E' },
          audit_date: '2026-03-01',
          duration_days: 1,
          status: 'in_progress',
          findings_count: 0,
          ncs_count: 0,
          scope: 'Processus support',
          lead_auditor: { id: 1, name: 'Auditeur E2E', email: 'auditor@e2e.local' },
          auditors_details: [],
          report_source: 'auto',
          report_version: 1,
        },
      },
    }).as('getAudit')
    cy.intercept('GET', '**/api/v1/audits/1/findings', {
      statusCode: 200,
      body: { data: [] },
    }).as('getAuditFindings')

    cy.visit('/company/audits/1', { failOnStatusCode: false })
    cy.wait('@getAudit')
    cy.get('body').should('be.visible')
    cy.contains('Statut').should('be.visible')
    cy.contains(/Générer le rapport|Télécharger le rapport/).should('exist')
  })
})
