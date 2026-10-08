import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { habilitationService } from '@/services/habilitationService'
import { useHabilitationStore } from '@/stores/habilitationStore'

// Mock services
vi.mock('@/services/habilitationService', () => ({
  habilitationService: {
    getAll: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
    renew: vi.fn(),
  },
}))

vi.mock('@/plugins/notification', () => ({
  useNotification: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  }),
}))

describe('HabilitationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should initialize with empty state', () => {
    const store = useHabilitationStore()

    expect(store.habilitations).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBe(null)
  })

  it('should fetch habilitations successfully', async () => {
    const mockHabilitations = [
      { id: '1', user_id: '1', status: 'active', expiry_date: '2024-12-31' },
      { id: '2', user_id: '2', status: 'expired', expiry_date: '2024-01-01' },
    ]

    habilitationService.getAll.mockResolvedValue(mockHabilitations)

    const store = useHabilitationStore()
    await store.fetchAll()

    expect(store.habilitations).toEqual(mockHabilitations)
    expect(store.loading).toBe(false)
    expect(habilitationService.getAll).toHaveBeenCalledOnce()
  })

  it('should handle fetch error', async () => {
    const error = new Error('Network error')
    habilitationService.getAll.mockRejectedValue(error)

    const store = useHabilitationStore()

    await expect(store.fetchAll()).rejects.toThrow('Network error')
    expect(store.error).toBe('Network error')
    expect(store.loading).toBe(false)
  })

  it('should create habilitation successfully', async () => {
    const newHabilitation = { id: '3', user_id: '3', status: 'active', user: { name: 'John Doe' } }
    habilitationService.create.mockResolvedValue(newHabilitation)

    const store = useHabilitationStore()
    const result = await store.create({ user_id: '3', status: 'active' })

    expect(result).toEqual(newHabilitation)
    expect(store.habilitations).toEqual(expect.arrayContaining([newHabilitation]))
    expect(habilitationService.create).toHaveBeenCalledWith({ user_id: '3', status: 'active' })
  })

  it('should update habilitation successfully', async () => {
    const existingHabilitation = { id: '1', user_id: '1', status: 'active' }
    const updatedHabilitation = { id: '1', user_id: '1', status: 'suspended' }

    const store = useHabilitationStore()
    store.habilitations = [existingHabilitation]

    habilitationService.update.mockResolvedValue(updatedHabilitation)

    const result = await store.update('1', { status: 'suspended' })

    expect(result).toEqual(updatedHabilitation)
    expect(store.habilitations[0]).toEqual(updatedHabilitation)
    expect(habilitationService.update).toHaveBeenCalledWith('1', { status: 'suspended' })
  })

  it('should remove habilitation successfully', async () => {
    const habilitation = { id: '1', user_id: '1', status: 'active' }
    const store = useHabilitationStore()
    store.habilitations = [habilitation]

    habilitationService.delete.mockResolvedValue()

    await store.remove('1')

    expect(store.habilitations).toEqual([])
    expect(habilitationService.delete).toHaveBeenCalledWith('1')
  })

  it('should filter expiring habilitations', () => {
    const store = useHabilitationStore()
    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 15) // 15 days from now

    const pastDate = new Date()
    pastDate.setDate(pastDate.getDate() - 5) // 5 days ago

    store.habilitations = [
      { id: '1', expiry_date: futureDate.toISOString() }, // expiring soon
      { id: '2', expiry_date: pastDate.toISOString() }, // expired
      { id: '3', expiry_date: null }, // no expiry
    ]

    expect(store.expiring).toHaveLength(1)
    expect(store.expiring[0].id).toBe('1')
  })

  it('should filter expired habilitations', () => {
    const store = useHabilitationStore()
    const pastDate = new Date()
    pastDate.setDate(pastDate.getDate() - 5)

    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 15)

    store.habilitations = [
      { id: '1', expiry_date: pastDate.toISOString() }, // expired
      { id: '2', expiry_date: futureDate.toISOString() }, // active
      { id: '3', expiry_date: null }, // no expiry
    ]

    expect(store.expired).toHaveLength(1)
    expect(store.expired[0].id).toBe('1')
  })

  it('should filter habilitations by user', () => {
    const store = useHabilitationStore()
    store.habilitations = [
      { id: '1', user_id: 'user1' },
      { id: '2', user_id: 'user2' },
      { id: '3', user_id: 'user1' },
    ]

    const userHabilitations = store.byUser('user1')

    expect(userHabilitations).toHaveLength(2)
    expect(userHabilitations.map(h => h.id)).toEqual(['1', '3'])
  })

  it('should use cache for subsequent fetches', async () => {
    const mockHabilitations = [{ id: '1', user_id: '1' }]
    habilitationService.getAll.mockResolvedValue(mockHabilitations)

    const store = useHabilitationStore()

    // First fetch
    await store.fetchAll()
    expect(habilitationService.getAll).toHaveBeenCalledTimes(1)

    // Second fetch (should use cache)
    await store.fetchAll()
    expect(habilitationService.getAll).toHaveBeenCalledTimes(1)

    // Force refresh
    await store.fetchAll(true)
    expect(habilitationService.getAll).toHaveBeenCalledTimes(2)
  })
})
