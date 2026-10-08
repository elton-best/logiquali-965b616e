import type { Risk } from '@/types/improvement'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useRiskStore } from '../improvement/riskStore'

// Mock risk service
vi.mock('@/services/improvement/riskService', () => ({
  default: {
    getAll: vi.fn().mockResolvedValue({
      data: [
        {
          id: 1,
          name: 'Risk 1',
          probability: 0.5,
          impact: 0.7,
          criticality: 'high',
          type: 'risk',
          status: 'active',
          axes: ['axis1'],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ],
    }),
    getById: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
    assess: vi.fn(),
    treat: vi.fn(),
  },
}))

describe('Risk Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('Initial State', () => {
    it('should have empty risks array initially', () => {
      const store = useRiskStore()
      expect(store.risks).toEqual([])
      expect(store.currentRisk).toBeNull()
      expect(store.loading).toBe(false)
      expect(store.error).toBeNull()
    })

    it('should have null matrix and statistics initially', () => {
      const store = useRiskStore()
      expect(store.riskMatrix).toBeNull()
      expect(store.statistics).toBeNull()
    })
  })

  describe('Computed Properties', () => {
    beforeEach(() => {
      vi.clearAllMocks()
    })

    it('should filter critical risks', () => {
      const store = useRiskStore()
      store.risks = [
        {
          id: 1,
          name: 'Critical Risk',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 20,
          probability: 0.9,
          impact: 0.9,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        {
          id: 2,
          name: 'High Risk',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ]

      const criticalRisks = store.criticalRisks
      expect(criticalRisks).toHaveLength(1)
      expect(criticalRisks[0].criticality).toBe(20)
    })

    it('should filter active risks', () => {
      const store = useRiskStore()
      store.risks = [
        {
          id: 1,
          name: 'Active Risk',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        {
          id: 2,
          name: 'Closed Risk',
          description: 'Description',
          type: 'opportunity',
          status: 'closed',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ]

      const activeRisks = store.activeRisks
      expect(activeRisks).toHaveLength(1)
      expect(activeRisks[0].type).toBe('risk')
    })

    it('should filter opportunities', () => {
      const store = useRiskStore()
      store.risks = [
        {
          id: 1,
          name: 'Risk',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        {
          id: 2,
          name: 'Opportunity',
          description: 'Description',
          type: 'opportunity',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ]

      const opportunities = store.opportunities
      expect(opportunities).toHaveLength(1)
      expect(opportunities[0].type).toBe('opportunity')
    })

    it('should group risks by axes', () => {
      const store = useRiskStore()
      store.risks = [
        {
          id: 1,
          name: 'Risk 1',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis1' }, { code: 'axis2' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        {
          id: 2,
          name: 'Risk 2',
          description: 'Description',
          type: 'risk',
          status: 'active',
          criticality: 10,
          probability: 0.7,
          impact: 0.7,
          axes: [{ code: 'axis2' }],
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
      ]

      const grouped = store.risksByAxe
      expect(Object.keys(grouped)).toContain('axis1')
      expect(Object.keys(grouped)).toContain('axis2')
      expect(grouped['axis1']).toHaveLength(1)
      expect(grouped['axis2']).toHaveLength(2)
    })
  })

  describe('Error Handling', () => {
    it('should handle error state', () => {
      const store = useRiskStore()

      expect(store.error).toBeNull()
      store.error = 'Test error'
      expect(store.error).toBe('Test error')
    })

    it('should clear error state', () => {
      const store = useRiskStore()
      store.error = 'Test error'

      store.error = null

      expect(store.error).toBeNull()
    })
  })

  describe('Loading State', () => {
    it('should manage loading state', () => {
      const store = useRiskStore()

      expect(store.loading).toBe(false)
      store.loading = true
      expect(store.loading).toBe(true)
    })
  })

  describe('Async Actions', () => {
    it('should have fetchRisks action', async () => {
      const store = useRiskStore()

      expect(typeof store.fetchRisks).toBe('function')
    })

    it('should have fetchRisk action', async () => {
      const store = useRiskStore()

      expect(typeof store.fetchRisk).toBe('function')
    })

    it('should have createRisk action', async () => {
      const store = useRiskStore()

      expect(typeof store.createRisk).toBe('function')
    })

    it('should have updateRisk action', async () => {
      const store = useRiskStore()

      expect(typeof store.updateRisk).toBe('function')
    })

    it('should have deleteRisk action', async () => {
      const store = useRiskStore()

      expect(typeof store.deleteRisk).toBe('function')
    })
  })

  describe('State Management', () => {
    it('should allow direct state modification for testing', () => {
      const store = useRiskStore()
      const mockRisk: Risk = {
        id: 1,
        name: 'Test Risk',
        description: 'Description',
        type: 'risk',
        status: 'active',
        criticality: 10,
        probability: 0.7,
        impact: 0.7,
        axes: [{ code: 'axis1' }] as any,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }

      store.risks = [mockRisk]

      expect(store.risks).toHaveLength(1)
      expect(store.risks[0].name).toBe('Test Risk')
    })

    it('should allow setting current risk directly', () => {
      const store = useRiskStore()
      const mockRisk: Risk = {
        id: 1,
        name: 'Current Risk',
        description: 'Description',
        type: 'risk',
        status: 'active',
        criticality: 10,
        probability: 0.7,
        impact: 0.7,
        axes: [{ code: 'axis1' }] as any,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      }

      store.currentRisk = mockRisk

      expect(store.currentRisk).toEqual(mockRisk)
    })
  })
})
