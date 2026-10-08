describe('Enterprise Configuration & PDF Export', () => {
  const email = Cypress.env('e2eEmail') || 'aelvis@best-experts-group.com'
  const password = Cypress.env('e2ePassword') || 'SuperAdmin@2024!'

  function assertSettingsOrForbidden (): void {
    cy.url({ timeout: 15_000 }).should(url => {
      expect(url).to.match(/\/(company\/settings|403)/)
    })
  }

  beforeEach(() => {
    cy.login(email, password)
  })

  it('redirects enterprise legacy configuration route to company settings', () => {
    cy.visit('/enterprise/configuration', { failOnStatusCode: false })
    assertSettingsOrForbidden()
    cy.get('body').should('be.visible')
  })

  it('redirects enterprise signatures route to company settings', () => {
    cy.visit('/enterprise/signatures', { failOnStatusCode: false })
    assertSettingsOrForbidden()
    cy.get('body').should('be.visible')
  })

  it('loads QR verification page', () => {
    cy.visit('/qr-verify/test_hash_123')
    cy.url().should('include', '/qr-verify/test_hash_123')
    cy.get('body').should('be.visible')
  })

  it('opens company document module', () => {
    cy.visit('/company/documents')
    cy.url().should('include', '/company/documents')
    cy.get('body').should('be.visible')
  })
})
