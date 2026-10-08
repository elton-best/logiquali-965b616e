describe('Flux critique - Clôture revue + rapport auto', () => {
  function bootAsCompanyUser (requiredPermissions: string[] = []) {
    const required = ['management_reviews.read', ...requiredPermissions]
    const user = {
      id: 1,
      name: 'E2E Company User',
      email: 'e2e-company@bestqhse.local',
      user_type: 'company',
      roles: [{ name: 'admin_entreprise' }],
      active_scoped_permissions: required,
      permissions: required,
      enterprise: {
        id: 1,
        status: 'active',
        domaine_activite_set: true,
        enterprise_subscriptions: [{ is_active: true }],
        sites: [{ id: 1, name: 'Site E2E', is_headquarter: true }],
      },
      site: { id: 1, name: 'Site E2E' },
    }

    cy.clearCookies()
    cy.clearLocalStorage()
    cy.visit('/company/management-reviews/1', {
      failOnStatusCode: false,
      onBeforeLoad: win => {
        win.localStorage.setItem('access_token', 'e2e-company-token')
        win.localStorage.setItem('user', JSON.stringify(user))
        win.localStorage.setItem('current_site_id', '1')
      },
    })
  }

  function mockApiFallbacks () {
    cy.intercept('GET', '**/api/v1/superadmin/**', { statusCode: 200, body: { data: [] } })
    cy.intercept('GET', '**/api/v1/**', { statusCode: 200, body: { data: [] } })
    cy.intercept('POST', '**/api/v1/**', { statusCode: 200, body: { success: true, data: {} } })
    cy.intercept('PUT', '**/api/v1/**', { statusCode: 200, body: { success: true, data: {} } })
    cy.intercept('PATCH', '**/api/v1/**', { statusCode: 200, body: { success: true, data: {} } })
    cy.intercept('DELETE', '**/api/v1/**', { statusCode: 200, body: { success: true } })
  }

  beforeEach(() => {
    mockApiFallbacks()
    cy.intercept('GET', '**/api/v1/sites**', {
      statusCode: 200,
      body: { data: [{ id: 1, name: 'Site E2E' }] },
    })
    cy.intercept('GET', '**/api/v1/subscription/status**', {
      statusCode: 200,
      body: { hasActiveSubscription: true, can_access_dashboard: true, status: 'active' },
    })
    cy.intercept('GET', '**/api/v1/subscription/current**', {
      statusCode: 200,
      body: {
        success: true,
        data: {
          id: 1,
          status: 'active',
          hasActiveSubscription: true,
          can_access_dashboard: true,
        },
      },
    })
    cy.intercept('GET', '**/api/v1/access/catalog**', {
      statusCode: 200,
      body: { norms: [], modules: [], sub_modules: [], sections: [], meta: {} },
    })
    cy.intercept('GET', '**/api/v1/notifications**', {
      statusCode: 200,
      body: { success: true, data: [] },
    })
    cy.intercept('GET', '**/api/v1/notifications/**', {
      statusCode: 200,
      body: { success: true, data: [] },
    })
    cy.intercept('GET', '**/api/v1/enterprises/**/config**', {
      statusCode: 200,
      body: { data: { id: 1, domaine_activite_set: true } },
    })
    cy.intercept('GET', '**/api/v1/geo/countries**', { statusCode: 200, body: [] })
    cy.intercept('GET', '**/api/v1/geo/countries/*/cities**', { statusCode: 200, body: [] })
    bootAsCompanyUser(['management_reviews.read'])
  })

  it('ouvre une revue et expose les actions de clôture/export', () => {
    cy.intercept('GET', '**/api/v1/management-reviews/1*', {
      statusCode: 200,
      body: {
        data: {
          id: 1,
          ref: 'REV-2026-001',
          title: 'Revue de direction Q1',
          site_id: 1,
          planned_date: '2026-03-01',
          chairman_id: 1,
          participants: [1],
          status: 'planned',
          decisions: [],
          action_items: [],
        },
      },
    }).as('getReview')
    cy.intercept('GET', '**/api/v1/users**', {
      statusCode: 200,
      body: { data: [] },
    }).as('getUsers')

    cy.visit('/company/management-reviews/1', { failOnStatusCode: false })
    cy.wait('@getReview')
    cy.get('body').should('be.visible')
    cy.contains('Saisie de la revue').should('be.visible')
    cy.contains('button', 'Export DOCX').should('be.visible')
  })
})
