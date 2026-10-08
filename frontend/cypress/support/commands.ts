// Custom Cypress Commands

Cypress.Commands.add('login', (email: string, password: string) => {
  const apiBase = Cypress.env('apiBaseUrl') || 'http://localhost:8000/api/v1'
  const envEmail = Cypress.env('e2eEmail')
  const envPassword = Cypress.env('e2ePassword')
  const envMfaCode = Cypress.env('e2eMfaCode')

  const candidates = [
    { email, password },
    { email: envEmail, password: envPassword },
    { email: 'admin@BestQHSE.com', password: 'password123' },
    { email: 'aelvis@best-experts-group.com', password: 'SuperAdmin@2024!' },
  ].filter(candidate =>
    typeof candidate.email === 'string'
    && candidate.email.length > 0
    && typeof candidate.password === 'string'
    && candidate.password.length > 0,
  ) as Array<{ email: string, password: string }>

  const uniqueCandidates = candidates.filter(
    (candidate, index, array) => array.findIndex(
      item => item.email === candidate.email && item.password === candidate.password,
    ) === index,
  )

  const persistAuthSession = (token: string, user: Record<string, any>) => cy.window().then(win => {
    win.localStorage.setItem('access_token', token)
    win.localStorage.setItem('user', JSON.stringify(user))

    const siteId = user?.site?.id || user?.enterprise?.sites?.[0]?.id
    if (siteId) {
      win.localStorage.setItem('current_site_id', String(siteId))
    }
  })

  const tryLogin = (index: number): Cypress.Chainable<void> => {
    if (index >= uniqueCandidates.length) {
      const allowMockFallback = String(Cypress.env('allowMockAuthFallback') || 'false') === 'true'
      if (!allowMockFallback) {
        throw new Error(
          'Unable to authenticate with all configured E2E credentials. '
          + 'If MFA is enabled, provide CYPRESS_e2eMfaCode or enable backend MFA_EXPOSE_OTP_FOR_E2E=true in local/testing.',
        )
      }

      return cy.window().then(win => {
        const mockUser = {
          id: 1,
          name: 'E2E Mock User',
          email: 'e2e-mock@BestQHSE.local',
          user_type: 'company',
          roles: [{ name: 'admin_entreprise' }],
          permissions: [
            'audits.read',
            'audits.create',
            'audits.update',
            'management_reviews.read',
            'management_reviews.create',
            'management_reviews.update',
            'non_conformities.read',
            'non_conformities.create',
            'non_conformities.update',
            'performance.read',
          ],
          enterprise: {
            id: 1,
            status: 'active',
            domaine_activite_set: true,
            domaine_activite: 'Services',
            enterprise_subscriptions: [{ is_active: true }],
            sites: [{ id: 1, name: 'Site E2E', is_headquarter: true }],
          },
          site: { id: 1, name: 'Site E2E' },
        }
        win.localStorage.setItem('access_token', 'e2e-mock-token')
        win.localStorage.setItem('user', JSON.stringify(mockUser))
        win.localStorage.setItem('current_site_id', '1')
      })
    }

    const current = uniqueCandidates[index]

    return cy.request({
      method: 'POST',
      url: `${apiBase}/auth/login`,
      body: current,
      failOnStatusCode: false,
    }).then(response => {
      const token = response.body?.token
      const user = response.body?.user

      if (response.status >= 200 && response.status < 300 && typeof token === 'string' && token.length > 0 && user && typeof user === 'object') {
        return persistAuthSession(token, user)
      }

      const mfaRequired = response.body?.mfa_required === true
      const mfaToken = response.body?.mfa_token
      const mfaCode = String(response.body?.mfa_code || envMfaCode || '').trim()
      if (mfaRequired && typeof mfaToken === 'string' && mfaToken.length > 0 && mfaCode.length > 0) {
        return cy.request({
          method: 'POST',
          url: `${apiBase}/auth/mfa/verify`,
          body: {
            token: mfaToken,
            code: mfaCode,
          },
          failOnStatusCode: false,
        }).then(verifyResponse => {
          const verifyToken = verifyResponse.body?.token
          const verifyUser = verifyResponse.body?.user
          if (
            verifyResponse.status >= 200
            && verifyResponse.status < 300
            && typeof verifyToken === 'string'
            && verifyToken.length > 0
            && verifyUser
            && typeof verifyUser === 'object'
          ) {
            return persistAuthSession(verifyToken, verifyUser)
          }

          return tryLogin(index + 1)
        })
      }

      return tryLogin(index + 1)
    })
  }

  return cy.session([email, password], () => {
    cy.visit('/auth/login')
    return tryLogin(0)
  }, {
    cacheAcrossSpecs: true,
  })
})

Cypress.Commands.add('logout', () => {
  cy.visit('/auth/login')
  cy.window().then(win => {
    win.localStorage.removeItem('access_token')
    win.localStorage.removeItem('user')
    win.localStorage.removeItem('current_site_id')
  })
  cy.visit('/auth/login')
  cy.url().should('include', '/auth/login')
})
