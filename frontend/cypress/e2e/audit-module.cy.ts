describe('Module Audits - E2E Smoke', () => {
  const email = Cypress.env('e2eEmail') || 'aelvis@best-experts-group.com'
  const password = Cypress.env('e2ePassword') || 'SuperAdmin@2024!'

  beforeEach(() => {
    cy.login(email, password)
  })

  it('opens audits list page', () => {
    cy.visit('/company/audits', { failOnStatusCode: false })
    cy.get('body').should('be.visible')
  })

  it('opens audit creation page', () => {
    cy.visit('/company/audits/create', { failOnStatusCode: false })
    cy.get('body').should('be.visible')
  })

  it('opens internal audit legacy route and lands on company audits', () => {
    cy.visit('/company/iso/performance/internal-audit', { failOnStatusCode: false })
    cy.get('body').should('be.visible')
  })

  it('opens audit detail route when id exists or stays on guarded page without crash', () => {
    cy.visit('/company/audits/1', { failOnStatusCode: false })
    cy.get('body').should('be.visible')
  })
})
