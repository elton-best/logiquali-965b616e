describe('Process Review Critical Flow', () => {
  function buildCompanyUser (requiredPermissions: string[] = []) {
    const required = ['performance.read', ...requiredPermissions]
    return {
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
      site: {
        id: 1,
        name: 'Site E2E',
      },
    }
  }

  function bootAsCompanyUser (requiredPermissions: string[] = []) {
    const user = buildCompanyUser(requiredPermissions)
    cy.clearCookies()
    cy.clearLocalStorage()
    cy.visit('/company/performance/surveillance', {
      failOnStatusCode: false,
      onBeforeLoad: win => {
        win.localStorage.setItem('access_token', 'e2e-company-token')
        win.localStorage.setItem('user', JSON.stringify(user))
        win.localStorage.setItem('current_site_id', '1')
      },
    })
  }

  function mockApiFallbacks () {
    cy.intercept('GET', '**/api/v1/superadmin/**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.intercept('GET', '**/api/v1/risks-opportunities**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.intercept('GET', '**/api/v1/non-conformities**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.intercept('GET', '**/api/v1/evaluation-responses**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.intercept('GET', '**/api/v1/**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.intercept('POST', '**/api/v1/**', {
      statusCode: 200,
      body: { success: true, data: {} },
    })
    cy.intercept('PUT', '**/api/v1/**', {
      statusCode: 200,
      body: { success: true, data: {} },
    })
    cy.intercept('PATCH', '**/api/v1/**', {
      statusCode: 200,
      body: { success: true, data: {} },
    })
    cy.intercept('DELETE', '**/api/v1/**', {
      statusCode: 200,
      body: { success: true },
    })
  }

  function mockCompanyShellApis () {
    cy.intercept('GET', '**/api/v1/sites**', {
      statusCode: 200,
      body: { data: [{ id: 1, name: 'Site E2E' }] },
    })
    cy.intercept('GET', '**/api/v1/subscription/status**', {
      statusCode: 200,
      body: { hasActiveSubscription: true, can_access_dashboard: true, status: 'active' },
    })
    cy.intercept('GET', '**/api/v1/access/catalog**', {
      statusCode: 200,
      body: { norms: [], modules: [], sub_modules: [], sections: [], meta: {} },
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
    cy.intercept('GET', '**/api/v1/notifications**', {
      statusCode: 200,
      body: { success: true, data: [] },
    })
    cy.intercept('GET', '**/api/v1/notifications/**', {
      statusCode: 200,
      body: { success: true, data: [] },
    })
    cy.intercept('GET', '**/api/v1/aspects-environnementaux/stats**', {
      statusCode: 200,
      body: { success: true, data: {} },
    })
    cy.intercept('GET', '**/api/v1/enterprises/**/config**', {
      statusCode: 200,
      body: { data: { id: 1, domaine_activite_set: true } },
    })
  }

  function mockProcessById (id: number, payload: {
    title: string
    type: string
    category: string
    ref: string
    code: string
    pilotId: number
    pilotName: string
    copilotId: number
    copilotName: string
  }, alias: string) {
    cy.intercept('GET', `**/api/v1/processes/${id}**`, {
      statusCode: 200,
      body: {
        enterprise: {
          id: 1,
        },
        data: {
          id,
          type: 'processes',
          attributes: {
            title: payload.title,
            type: payload.type,
            category: payload.category,
            ref: payload.ref,
            code: payload.code,
          },
          relationships: {
            pilot: { id: payload.pilotId, attributes: { name: payload.pilotName } },
            copilot: { id: payload.copilotId, attributes: { name: payload.copilotName } },
            objectives: { data: [] },
            indicators: { data: [] },
            risks_opportunities: { data: [] },
            sequences: { data: [] },
          },
        },
      },
    }).as(alias)
  }

  beforeEach(() => {
    mockApiFallbacks()
    mockCompanyShellApis()
    bootAsCompanyUser(['performance.read'])
  })

  it('opens the process-review entry page from surveillance hub', () => {
    cy.intercept('GET', '**/api/v1/processes**', {
      statusCode: 200,
      body: { data: [] },
    })
    cy.visit('/company/performance/surveillance', { failOnStatusCode: false })
    cy.contains('Revue Processus').should('be.visible').click({ force: true })
    cy.url().should('include', '/company/performance/surveillance/process-review')
    cy.contains('Revue Processus').should('be.visible')
  })

  it('shows management section only for process "Management"', () => {
    mockProcessById(123, {
      title: 'Management',
      type: 'management',
      category: 'pilotage',
      ref: 'PRC-MAN-001',
      code: 'MAN-001',
      pilotId: 1,
      pilotName: 'Pilot A',
      copilotId: 2,
      copilotName: 'CoPilot A',
    }, 'getManagementProcess')

    mockProcessById(124, {
      title: 'Support achats',
      type: 'support',
      category: 'support',
      ref: 'PRC-SUP-001',
      code: 'SUP-001',
      pilotId: 1,
      pilotName: 'Pilot B',
      copilotId: 2,
      copilotName: 'CoPilot B',
    }, 'getSupportProcess')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 1, name: 'Pilot A' },
        { id: 2, name: 'CoPilot A' },
      ],
    }).as('getUsers')

    cy.intercept('GET', '**/api/v1/processes/123/reviews/current', {
      success: true,
      data: {
        id: 9001,
        status: 'in_progress',
        identification: {
          rq_name: 'RQ A',
          include_pilot: true,
          include_copilot: true,
          present_user_ids: [1, 2],
          coverage_start: '2026-01-01',
          coverage_end: '2026-03-31',
          started_at: '01/01/2026 08:00',
          ended_at: '',
        },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: true,
        },
        updated_at: '2026-04-17T11:00:00Z',
      },
    }).as('getReview123')

    cy.intercept('GET', '**/api/v1/processes/124/reviews/current', {
      success: true,
      data: {
        id: 9002,
        status: 'in_progress',
        identification: {
          rq_name: 'RQ B',
        },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: true,
        },
        updated_at: '2026-04-17T11:05:00Z',
      },
    }).as('getReview124')

    cy.intercept('GET', '**/api/v1/risks-opportunities*', { data: [] })
    cy.intercept('GET', '**/api/v1/non-conformities*', { data: [] })
    cy.intercept('GET', '**/api/v1/evaluation-responses*', { data: [] })
    cy.intercept('PUT', '**/api/v1/processes/*/reviews/current', { success: true, data: { status: 'in_progress', updated_at: '2026-04-17T11:06:00Z' } })

    cy.visit('/company/performance/surveillance/process-review/123', { failOnStatusCode: false })
    cy.wait('@getManagementProcess')
    cy.wait('@getReview123')
    cy.contains('6. Leadership / DUERP (affichage)')
      .should('be.visible')
      .closest('.v-card')
      .within(() => {
        cy.get('textarea').should('exist')
      })

    cy.visit('/company/performance/surveillance/process-review/124', { failOnStatusCode: false })
    cy.wait('@getSupportProcess')
    cy.wait('@getReview124')
    cy.contains('6. Leadership / DUERP (affichage)').should('be.visible')
    cy.contains('visible uniquement pour le processus Management').should('be.visible')
  })

  it('locks editing when API returns read-only capabilities', () => {
    mockProcessById(125, {
      title: 'Support maintenance',
      type: 'support',
      category: 'support',
      ref: 'PRC-SUP-002',
      code: 'SUP-002',
      pilotId: 3,
      pilotName: 'Pilot C',
      copilotId: 4,
      copilotName: 'CoPilot C',
    }, 'getSupportProcessReadOnly')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 3, name: 'Pilot C' },
        { id: 4, name: 'CoPilot C' },
      ],
    }).as('getUsersReadOnly')

    cy.intercept('GET', '**/api/v1/processes/125/reviews/current', {
      success: true,
      data: {
        id: 9003,
        status: 'in_progress',
        identification: {
          rq_name: 'RQ C',
        },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: false,
          can_edit_identification: false,
        },
        updated_at: '2026-04-17T11:10:00Z',
      },
    }).as('getReview125')

    cy.visit('/company/performance/surveillance/process-review/125', { failOnStatusCode: false })
    cy.wait('@getSupportProcessReadOnly')
    cy.wait('@getUsersReadOnly')
    cy.wait('@getReview125')

    cy.contains('lecture seule').should('be.visible')
    cy.contains('button', 'Clôturer la revue').should('be.disabled')
    cy.contains('button', 'Enregistrer').should('not.exist')
  })

  it('shows empty linked data state for management process', () => {
    mockProcessById(127, {
      title: 'Management',
      type: 'management',
      category: 'pilotage',
      ref: 'PRC-MAN-002',
      code: 'MAN-002',
      pilotId: 7,
      pilotName: 'Pilot E',
      copilotId: 8,
      copilotName: 'CoPilot E',
    }, 'getProcess127')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 7, name: 'Pilot E' },
        { id: 8, name: 'CoPilot E' },
      ],
    }).as('getUsers127')

    cy.intercept('GET', '**/api/v1/processes/127/reviews/current', {
      success: true,
      data: {
        id: 9005,
        status: 'in_progress',
        identification: { rq_name: 'RQ E' },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: true,
        },
        updated_at: '2026-04-21T10:00:00Z',
      },
    }).as('getReview127')

    cy.intercept('GET', '**/api/v1/processes/127/reviews/current/linked-data', {
      success: true,
      data: {
        duerp_top: [],
        aes_top: [],
        summary: {
          duerp_overdue_actions: 0,
          aes_missing_actions: 0,
        },
      },
    }).as('getLinkedData127')

    cy.visit('/company/performance/surveillance/process-review/127', { failOnStatusCode: false })
    cy.wait('@getProcess127')
    cy.wait('@getUsers127')
    cy.wait('@getReview127')
    cy.wait('@getLinkedData127')

    cy.contains('Données liées DUERP / AES').should('be.visible')
    cy.contains('Aucune donnée DUERP/AES liée.').should('be.visible')
  })

  it('shows linked DUERP and AES data when available', () => {
    mockProcessById(128, {
      title: 'Management',
      type: 'management',
      category: 'pilotage',
      ref: 'PRC-MAN-003',
      code: 'MAN-003',
      pilotId: 9,
      pilotName: 'Pilot F',
      copilotId: 10,
      copilotName: 'CoPilot F',
    }, 'getProcess128')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 9, name: 'Pilot F' },
        { id: 10, name: 'CoPilot F' },
      ],
    }).as('getUsers128')

    cy.intercept('GET', '**/api/v1/processes/128/reviews/current', {
      success: true,
      data: {
        id: 9006,
        status: 'in_progress',
        identification: { rq_name: 'RQ F' },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: true,
        },
        updated_at: '2026-04-21T10:05:00Z',
      },
    }).as('getReview128')

    cy.intercept('GET', '**/api/v1/processes/128/reviews/current/linked-data', {
      success: true,
      data: {
        duerp_top: [
          {
            id: 701,
            process_id: 128,
            danger_type: 'Risque incendie',
            danger_description: 'Stockage produit inflammable',
            criticality_score: 16,
            criticality_level: 'unacceptable',
            overdue_actions: 2,
          },
        ],
        aes_top: [
          {
            id: 801,
            process_id: 128,
            designation: 'Rejets aqueux',
            type: 'rejet_eau',
            criticite: 54,
            aspect_significatif: true,
            action_status: 'missing',
          },
        ],
        summary: {
          duerp_overdue_actions: 2,
          aes_missing_actions: 1,
        },
      },
    }).as('getLinkedData128')

    cy.visit('/company/performance/surveillance/process-review/128', { failOnStatusCode: false })
    cy.wait('@getProcess128')
    cy.wait('@getUsers128')
    cy.wait('@getReview128')
    cy.wait('@getLinkedData128')

    cy.contains('Données liées DUERP / AES').should('be.visible')
    cy.contains('Actions DUERP en retard: 2').should('be.visible')
    cy.contains('AES sans plan: 1').should('be.visible')
    cy.contains('Risque incendie').should('be.visible')
    cy.contains('Rejets aqueux').should('be.visible')
    cy.contains('Plan manquant').should('be.visible')
  })

  it('creates a linked action from a DUERP row with traceable payload', () => {
    mockProcessById(129, {
      title: 'Management',
      type: 'management',
      category: 'pilotage',
      ref: 'PRC-MAN-004',
      code: 'MAN-004',
      pilotId: 11,
      pilotName: 'Pilot G',
      copilotId: 12,
      copilotName: 'CoPilot G',
    }, 'getProcess129')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 11, name: 'Pilot G' },
        { id: 12, name: 'CoPilot G' },
      ],
    }).as('getUsers129')

    cy.intercept('GET', '**/api/v1/processes/129/reviews/current', {
      success: true,
      data: {
        id: 9007,
        status: 'in_progress',
        identification: { rq_name: 'RQ G' },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: true,
        },
        updated_at: '2026-04-21T11:00:00Z',
      },
    }).as('getReview129')

    let linkedDataCall = 0
    cy.intercept('GET', '**/api/v1/processes/129/reviews/current/linked-data', req => {
      linkedDataCall += 1
      if (linkedDataCall === 1) {
        req.reply({
          success: true,
          data: {
            duerp_top: [
              {
                id: 901,
                process_id: 129,
                danger_type: 'Risque incendie',
                danger_description: 'Produit inflammable',
                criticality_score: 18,
                criticality_level: 'unacceptable',
                overdue_actions: 1,
                linked_actions_count: 0,
              },
            ],
            aes_top: [],
            summary: {
              duerp_overdue_actions: 1,
              aes_missing_actions: 0,
            },
          },
        })
        return
      }

      req.reply({
        success: true,
        data: {
          duerp_top: [
            {
              id: 901,
              process_id: 129,
              danger_type: 'Risque incendie',
              danger_description: 'Produit inflammable',
              criticality_score: 18,
              criticality_level: 'unacceptable',
              overdue_actions: 1,
              linked_actions_count: 1,
            },
          ],
          aes_top: [],
          summary: {
            duerp_overdue_actions: 1,
            aes_missing_actions: 0,
          },
        },
      })
    }).as('getLinkedData129')

    cy.intercept('POST', '**/api/v1/processes/129/reviews/current/linked-data/actions', req => {
      expect(req.body).to.include({
        source_type: 'duerp_danger',
        source_id: 901,
      })
      expect(String(req.body.title || '')).to.contain('Action DUERP')
      req.reply({
        statusCode: 201,
        body: {
          success: true,
          data: {
            id: 12_001,
            ref: 'ACT-12001',
            source_type: 'duerp_danger',
            source_id: 901,
            title: req.body.title,
          },
        },
      })
    }).as('createLinkedAction129')

    cy.visit('/company/performance/surveillance/process-review/129', { failOnStatusCode: false })
    cy.wait('@getProcess129')
    cy.wait('@getUsers129')
    cy.wait('@getReview129')
    cy.wait('@getLinkedData129')

    cy.get('[data-testid="create-linked-action-duerp-901"]').click({ force: true })
    cy.get('[data-testid="linked-action-source"]').should('contain.text', 'Risque incendie')
    cy.get('[data-testid="linked-action-title"]').clear().type('Action DUERP - Test E2E')
    cy.get('[data-testid="linked-action-description"]').clear().type('Création action liée depuis revue.')
    cy.get('[data-testid="linked-action-deadline"] input')
      .clear({ force: true })
      .type('2026-12-31', { force: true })
    cy.get('[data-testid="linked-action-submit"]').click({ force: true })

    cy.wait('@createLinkedAction129')
    cy.wait('@getLinkedData129')
    cy.contains('Actions: 1').should('be.visible')
  })

  it('locks identification only when API denies identification edition', () => {
    mockProcessById(126, {
      title: 'Processus opérationnel',
      type: 'operational',
      category: 'operationnel',
      ref: 'PRC-OPE-001',
      code: 'OPE-001',
      pilotId: 5,
      pilotName: 'Pilot D',
      copilotId: 6,
      copilotName: 'CoPilot D',
    }, 'getProcess126')

    cy.intercept('GET', '**/api/v1/users*', {
      data: [
        { id: 5, name: 'Pilot D' },
        { id: 6, name: 'CoPilot D' },
      ],
    }).as('getUsers126')

    cy.intercept('GET', '**/api/v1/processes/126/reviews/current', {
      success: true,
      data: {
        id: 9004,
        status: 'in_progress',
        identification: {
          rq_name: 'RQ D',
          include_pilot: true,
          include_copilot: false,
        },
        sections: {},
        metrics_snapshot: {},
        capabilities: {
          can_update_review: true,
          can_edit_identification: false,
        },
        updated_at: '2026-04-17T11:15:00Z',
      },
    }).as('getReview126')

    cy.intercept('GET', '**/api/v1/risks-opportunities*', { data: [] })
    cy.intercept('GET', '**/api/v1/non-conformities*', { data: [] })
    cy.intercept('GET', '**/api/v1/evaluation-responses*', { data: [] })
    cy.intercept('PUT', '**/api/v1/processes/*/reviews/current', {
      success: true,
      data: { status: 'in_progress', updated_at: '2026-04-17T11:16:00Z' },
    })

    cy.visit('/company/performance/surveillance/process-review/126', { failOnStatusCode: false })
    cy.wait('@getProcess126')
    cy.wait('@getUsers126')
    cy.wait('@getReview126')

    cy.contains('Identification est verrouillée').should('be.visible')
    cy.contains('button', 'Enregistrer').should('be.visible')
  })
})
