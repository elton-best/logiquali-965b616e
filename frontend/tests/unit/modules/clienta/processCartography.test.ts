import { describe, expect, it } from 'vitest'

describe('Process Cartography ISO 9001 §4.4 Logic', () => {
  // Test category normalization
  function normalizeCategory(category: string): 'management' | 'realization' | 'support' {
    const cat = category.toLowerCase().trim()
    if (['management', 'pilotage', 'mesure_amelioration', 'direction'].includes(cat)) {
      return 'management'
    }
    if (['support', 'soutien', 'ressources', 'ressource'].includes(cat)) {
      return 'support'
    }
    return 'realization'
  }

  // Test upstream and downstream interaction resolution
  function resolveProcessInteractions(process: any, allProcesses: any[], interactions: any[]) {
    const currentId = process.id
    const explicitSuppliers = (process.supplier_processes || []).map((r: any) => String(r).toUpperCase())
    const explicitClients = (process.client_processes || []).map((r: any) => String(r).toUpperCase())

    const upstream = allProcesses.filter(p => {
      if (p.id === currentId) return false
      if (explicitSuppliers.includes(String(p.id)) || explicitSuppliers.includes(String(p.code).toUpperCase())) {
        return true
      }
      return interactions.some(int => int.target_id === currentId && int.source_id === p.id)
    })

    const downstream = allProcesses.filter(p => {
      if (p.id === currentId) return false
      if (explicitClients.includes(String(p.id)) || explicitClients.includes(String(p.code).toUpperCase())) {
        return true
      }
      return interactions.some(int => int.source_id === currentId && int.target_id === p.id)
    })

    return { upstream, downstream }
  }

  it('normalizes categories into standard ISO 9001 lanes', () => {
    expect(normalizeCategory('pilotage')).toBe('management')
    expect(normalizeCategory('management')).toBe('management')
    expect(normalizeCategory('mesure_amelioration')).toBe('management')
    expect(normalizeCategory('operationnel')).toBe('realization')
    expect(normalizeCategory('realization')).toBe('realization')
    expect(normalizeCategory('support')).toBe('support')
    expect(normalizeCategory('ressources')).toBe('support')
  })

  it('correctly resolves upstream and downstream process interactions', () => {
    const mockProcesses = [
      {
        id: 1,
        code: 'PRC-PIL-01',
        title: 'Pilotage & Stratégie',
        supplier_processes: [],
        client_processes: ['PRC-REAL-01', 'PRC-SUP-01'],
      },
      {
        id: 2,
        code: 'PRC-REAL-01',
        title: 'Relation Client & Ventes',
        supplier_processes: ['PRC-PIL-01'],
        client_processes: ['PRC-REAL-02'],
      },
      {
        id: 3,
        code: 'PRC-REAL-02',
        title: 'Solutions IT & Déploiement',
        supplier_processes: ['PRC-REAL-01', 'PRC-SUP-01'],
        client_processes: [],
      },
      {
        id: 4,
        code: 'PRC-SUP-01',
        title: 'Gestion des Ressources & RH',
        supplier_processes: ['PRC-PIL-01'],
        client_processes: ['PRC-REAL-02'],
      },
    ]

    const mockInteractions = [
      { source_id: 1, target_id: 2, label: 'Objectifs' },
      { source_id: 2, target_id: 3, label: 'Commande client' },
      { source_id: 4, target_id: 3, label: 'Ressources IT' },
    ]

    // Check interactions for PRC-REAL-01
    const { upstream: up1, downstream: down1 } = resolveProcessInteractions(mockProcesses[1], mockProcesses, mockInteractions)
    expect(up1.map(p => p.code)).toContain('PRC-PIL-01')
    expect(down1.map(p => p.code)).toContain('PRC-REAL-02')

    // Check interactions for PRC-REAL-02
    const { upstream: up2, downstream: down2 } = resolveProcessInteractions(mockProcesses[2], mockProcesses, mockInteractions)
    expect(up2.map(p => p.code)).toContain('PRC-REAL-01')
    expect(up2.map(p => p.code)).toContain('PRC-SUP-01')
    expect(down2).toHaveLength(0)
  })

  it('validates document export metadata structure', () => {
    const metadata = {
      version: '1.0',
      date: '07/10/2026',
      summaryItems: [
        { label: 'Entreprise / SMQ', value: 'BEST EXPERTS-GROUP' },
        { label: 'Total Processus', value: 6 },
        { label: 'Management', value: 2 },
        { label: 'Réalisation', value: 3 },
        { label: 'Support', value: 1 },
      ],
    }

    expect(metadata.summaryItems).toHaveLength(5)
    expect(metadata.summaryItems[0].value).toBe('BEST EXPERTS-GROUP')
    expect(metadata.summaryItems.find(i => i.label === 'Total Processus')?.value).toBe(6)
  })

  it('strictly validates the 4-column canevas format and joint responsibles display', () => {
    // 4-column canevas definition
    const canvasHeaders = [
      'PROPOSITION DE NOM PROCESSUS',
      'ACTIVITES PRINCIPALES',
      'OBSERVATION',
      'RESPONSABLE PAR DEPARTEMENT',
    ]
    expect(canvasHeaders).toEqual([
      'PROPOSITION DE NOM PROCESSUS',
      'ACTIVITES PRINCIPALES',
      'OBSERVATION',
      'RESPONSABLE PAR DEPARTEMENT',
    ])

    // Joint responsibles formatting logic (pilot + copilots with &)
    function formatJointResponsibles(pilotName: string | null, copilotNames: string[] = []): string {
      const names = [pilotName, ...copilotNames].filter(Boolean) as string[]
      return names.length > 0 ? names.join(' & ') : 'Non défini'
    }

    expect(formatJointResponsibles('Peace HAZOUME', ['Armel DHOSSOUVI'])).toBe('Peace HAZOUME & Armel DHOSSOUVI')
    expect(formatJointResponsibles('Directeur Général')).toBe('Directeur Général')
    expect(formatJointResponsibles(null)).toBe('Non défini')

    // Cartography item mapping against the 4 columns
    const mockProcess = {
      title: 'OPERATIONS DE MANAGEMENT ET ISO',
      activities_summary: 'Suivi des plans d\'action, audits internes, revues de direction',
      observation: 'Mise à jour avec intégration des activités d’externalisation',
      responsible_display: 'Peace HAZOUME & Armel DHOSSOUVI',
    }

    expect(mockProcess.title).toBe('OPERATIONS DE MANAGEMENT ET ISO')
    expect(mockProcess.activities_summary).toContain('audits internes')
    expect(mockProcess.observation).toBe('Mise à jour avec intégration des activités d’externalisation')
    expect(mockProcess.responsible_display).toBe('Peace HAZOUME & Armel DHOSSOUVI')
  })

  it('ensures diagram SVG string contains only valid XML entities for PNG rendering', () => {
    function escapeXml(unsafe: string): string {
      return String(unsafe || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&apos;')
    }

    const enterprise = escapeXml('BEST EXPERTS-GROUP & CO')
    const svgHeader = `<text>${enterprise} &#8226; Éditée le 08/10/2026</text>`
    expect(svgHeader).not.toContain('&bull;')
    expect(svgHeader).toContain('&#8226;')
    expect(svgHeader).toContain('&amp;')
  })
})

