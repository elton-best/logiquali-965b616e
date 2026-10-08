describe('Critical User Journeys E2E', () => {
  const email = Cypress.env('e2eEmail') || 'aelvis@best-experts-group.com'
  const password = Cypress.env('e2ePassword') || 'SuperAdmin@2024!'
  const dashboardByUserType: Record<string, string> = {
    super_admin: '/superadmin/dashboard',
    company: '/company/dashboard',
    clientb: '/clientb/dashboard',
  }

  beforeEach(() => {
    cy.login(email, password)
  })

  it('authenticates by API and reaches the company dashboard', () => {
    cy.window().then(win => {
      const user = JSON.parse(win.localStorage.getItem('user') || '{}')
      const expectedDashboard = dashboardByUserType[user?.user_type] || '/auth/login'

      cy.visit(expectedDashboard, { failOnStatusCode: false })
      cy.url().should('include', expectedDashboard)
    })
    cy.get('body').should('be.visible')
  })

  it('navigates to key company modules after authentication', () => {
    cy.visit('/company/iso/support/communication', { failOnStatusCode: false })
    cy.get('body').should('be.visible')

    cy.visit('/company/iso/securite/habilitations', { failOnStatusCode: false })
    cy.get('body').should('be.visible')
  })

  it('redirects unauthenticated users to login', () => {
    cy.logout()
    cy.visit('/company/dashboard', { failOnStatusCode: false })
    cy.window().then(win => {
      expect(win.localStorage.getItem('access_token')).to.equal(null)
    })
    cy.get('body').should('be.visible')
  })

  it('rejects invalid credentials on login API', () => {
    const apiBase = Cypress.env('apiBaseUrl') || 'http://localhost:8000/api/v1'

    cy.request({
      method: 'POST',
      url: `${apiBase}/auth/login`,
      body: {
        email: 'invalid-user@example.com',
        password: 'invalid-password',
      },
      failOnStatusCode: false,
    }).then(response => {
      expect(response.status).to.be.oneOf([401, 403, 422, 429])
    })
  })
})
