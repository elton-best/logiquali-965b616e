import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { competenceService } from '@/services/competenceService'
import { useCompetenceStore } from '@/stores/competenceStore'

// Mock services
vi.mock('@/services/competenceService', () => ({
  competenceService: {
    getCompetencesRequises: vi.fn(),
    getMatrix: vi.fn(),
    getGapAnalysis: vi.fn(),
    generateTrainingPlan: vi.fn(),
  },
}))

describe('CompetenceStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should initialize with empty state', () => {
    const store = useCompetenceStore()

    expect(store.competencesRequises).toEqual([])
    expect(store.matrixData).toBe(null)
    expect(store.loading).toBe(false)
    expect(store.error).toBe(null)
  })

  it('should fetch competences requises successfully', async () => {
    const mockCompetences = [
      { id: '1', name: 'Sécurité', competence_type: 'technique' },
      { id: '2', name: 'Communication', competence_type: 'soft_skill' },
    ]

    competenceService.getCompetencesRequises.mockResolvedValue(mockCompetences)

    const store = useCompetenceStore()
    await store.fetchCompetencesRequises()

    expect(store.competencesRequises).toEqual(mockCompetences)
    expect(competenceService.getCompetencesRequises).toHaveBeenCalledOnce()
  })

  it('should fetch matrix data successfully', async () => {
    const mockMatrix = {
      users: [
        { id: '1', name: 'John', completion_rate: 85 },
        { id: '2', name: 'Jane', completion_rate: 92 },
      ],
      competences: [
        { id: '1', name: 'Sécurité' },
        { id: '2', name: 'Communication' },
      ],
    }

    competenceService.getMatrix.mockResolvedValue(mockMatrix)

    const store = useCompetenceStore()
    await store.fetchMatrix('job1')

    expect(store.matrixData).toEqual({ ...mockMatrix, jobId: 'job1' })
    expect(competenceService.getMatrix).toHaveBeenCalledWith('job1')
  })

  it('should use cache for matrix data', async () => {
    const mockMatrix = { users: [], competences: [] }
    competenceService.getMatrix.mockResolvedValue(mockMatrix)

    const store = useCompetenceStore()

    // First fetch
    await store.fetchMatrix('job1')
    expect(competenceService.getMatrix).toHaveBeenCalledTimes(1)

    // Second fetch with same job (should use cache)
    await store.fetchMatrix('job1')
    expect(competenceService.getMatrix).toHaveBeenCalledTimes(1)

    // Different job (should fetch)
    await store.fetchMatrix('job2')
    expect(competenceService.getMatrix).toHaveBeenCalledTimes(2)
  })

  it('should fetch gap analysis successfully', async () => {
    const mockGapAnalysis = {
      summary: { acquired: 5, gaps: 2, missing: 1 },
      categories: [
        {
          name: 'Technique',
          competences: [
            { id: '1', name: 'Sécurité', status: 'adequate' },
            { id: '2', name: 'Qualité', status: 'insufficient' },
          ],
        },
      ],
    }

    competenceService.getGapAnalysis.mockResolvedValue(mockGapAnalysis)

    const store = useCompetenceStore()
    const result = await store.fetchGapAnalysis('user1')

    expect(result).toEqual(mockGapAnalysis)
    expect(store.userGapAnalysis('user1')).toEqual(mockGapAnalysis)
    expect(competenceService.getGapAnalysis).toHaveBeenCalledWith('user1')
  })

  it('should generate training plan successfully', async () => {
    const mockTrainingPlan = {
      user_id: 'user1',
      formations: [
        { id: '1', name: 'Formation Sécurité', priority: 'high', cost: 1500 },
      ],
      estimated_cost: 1500,
      estimated_duration: 5,
    }

    competenceService.generateTrainingPlan.mockResolvedValue(mockTrainingPlan)

    const store = useCompetenceStore()
    const result = await store.generateTrainingPlan('user1')

    expect(result).toEqual(mockTrainingPlan)
    expect(store.userTrainingPlan('user1')).toEqual(mockTrainingPlan)
    expect(competenceService.generateTrainingPlan).toHaveBeenCalledWith('user1')
  })

  it('should group competences by type', () => {
    const store = useCompetenceStore()
    store.competencesRequises = [
      { id: '1', name: 'Sécurité', competence_type: 'technique' },
      { id: '2', name: 'Communication', competence_type: 'soft_skill' },
      { id: '3', name: 'Qualité', competence_type: 'technique' },
    ]

    const grouped = store.competencesByType

    expect(grouped).toEqual({
      technique: [
        { id: '1', name: 'Sécurité', competence_type: 'technique' },
        { id: '3', name: 'Qualité', competence_type: 'technique' },
      ],
      soft_skill: [
        { id: '2', name: 'Communication', competence_type: 'soft_skill' },
      ],
    })
  })

  it('should return matrix by job', () => {
    const store = useCompetenceStore()
    const mockMatrix = { users: [], competences: [], jobId: 'job1' }
    store.matrixData = mockMatrix

    expect(store.matrixByJob('job1')).toEqual(mockMatrix)
    expect(store.matrixByJob('job2')).toBe(null)
  })

  it('should clear cache', () => {
    const store = useCompetenceStore()
    store.matrixData = { users: [], competences: [] }
    // Note: gapAnalyses and trainingPlans are Maps, test the clearCache method instead

    store.clearCache()

    expect(store.matrixData).toBe(null)
  })

  it('should invalidate user data', () => {
    const store = useCompetenceStore()
    store.matrixData = {
      users: [{ id: 'user1', name: 'John' }],
      competences: [],
    }

    store.invalidateUser('user1')

    expect(store.matrixData).toBe(null)
  })

  it('should handle errors gracefully', async () => {
    const error = new Error('API Error')
    competenceService.getMatrix.mockRejectedValue(error)

    const store = useCompetenceStore()

    await expect(store.fetchMatrix()).rejects.toThrow('API Error')
    expect(store.error).toBe('API Error')
    expect(store.loading).toBe(false)
  })
})
