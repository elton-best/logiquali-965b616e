import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { userService } from '@/services/userService'
import { useUserStore } from '@/stores/userStore'

// Mock services
vi.mock('@/services/userService', () => ({
  userService: {
    getAll: vi.fn(),
    getJobDescriptions: vi.fn(),
    getUsersByJob: vi.fn(),
  },
}))

describe('UserStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('should initialize with empty state', () => {
    const store = useUserStore()

    expect(store.users).toEqual([])
    expect(store.jobDescriptions).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBe(null)
  })

  it('should fetch users successfully', async () => {
    const mockUsers = [
      { id: '1', name: 'John Doe', status: 'active', job_description_id: 'job1' },
      { id: '2', name: 'Jane Smith', status: 'active', job_description_id: 'job2' },
    ]

    userService.getAll.mockResolvedValue(mockUsers)

    const store = useUserStore()
    await store.fetchUsers()

    expect(store.users).toEqual(mockUsers)
    expect(userService.getAll).toHaveBeenCalledOnce()
  })

  it('should fetch job descriptions successfully', async () => {
    const mockJobDescriptions = [
      { id: 'job1', title: 'Développeur', department: 'IT' },
      { id: 'job2', title: 'Manager', department: 'Management' },
    ]

    userService.getJobDescriptions.mockResolvedValue(mockJobDescriptions)

    const store = useUserStore()
    await store.fetchJobDescriptions()

    expect(store.jobDescriptions).toEqual(mockJobDescriptions)
    expect(userService.getJobDescriptions).toHaveBeenCalledOnce()
  })

  it('should filter users by job', () => {
    const store = useUserStore()
    store.users = [
      { id: '1', name: 'John', job_description_id: 'job1' },
      { id: '2', name: 'Jane', job_description_id: 'job2' },
      { id: '3', name: 'Bob', job_description_id: 'job1' },
    ]

    const job1Users = store.usersByJob('job1')

    expect(job1Users).toHaveLength(2)
    expect(job1Users.map(u => u.name)).toEqual(['John', 'Bob'])
  })

  it('should filter active users', () => {
    const store = useUserStore()
    store.users = [
      { id: '1', name: 'John', status: 'active' },
      { id: '2', name: 'Jane', status: 'inactive' },
      { id: '3', name: 'Bob', status: 'active' },
    ]

    const activeUsers = store.activeUsers

    expect(activeUsers).toHaveLength(2)
    expect(activeUsers.map(u => u.name)).toEqual(['John', 'Bob'])
  })

  it('should get user by id', () => {
    const store = useUserStore()
    store.users = [
      { id: '1', name: 'John' },
      { id: '2', name: 'Jane' },
    ]

    const user = store.getUserById('1')

    expect(user).toEqual({ id: '1', name: 'John' })
    expect(store.getUserById('999')).toBeUndefined()
  })

  it('should filter users with expired habilitations', () => {
    const pastDate = new Date()
    pastDate.setDate(pastDate.getDate() - 5)

    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 15)

    const store = useUserStore()
    store.users = [
      {
        id: '1',
        name: 'John',
        habilitations: [
          { id: 'h1', expiry_date: pastDate.toISOString() },
        ],
      },
      {
        id: '2',
        name: 'Jane',
        habilitations: [
          { id: 'h2', expiry_date: futureDate.toISOString() },
        ],
      },
      {
        id: '3',
        name: 'Bob',
        habilitations: [],
      },
    ]

    const usersWithExpired = store.usersWithExpiredHabilitations

    expect(usersWithExpired).toHaveLength(1)
    expect(usersWithExpired[0].name).toBe('John')
  })

  it('should update user data', () => {
    const store = useUserStore()
    store.users = [
      { id: '1', name: 'John', email: 'john@example.com' },
    ]

    store.updateUser('1', { email: 'john.doe@example.com', phone: '123456789' })

    expect(store.users[0]).toEqual({
      id: '1',
      name: 'John',
      email: 'john.doe@example.com',
      phone: '123456789',
    })
  })

  it('should add habilitation to user', () => {
    const store = useUserStore()
    store.users = [
      { id: '1', name: 'John', habilitations: [] },
    ]

    const newHabilitation = { id: 'h1', competence_id: 'c1', level: 'base' }
    store.addHabilitation('1', newHabilitation)

    expect(store.users[0].habilitations).toEqual(expect.arrayContaining([newHabilitation]))
  })

  it('should update user habilitation', () => {
    const store = useUserStore()
    store.users = [
      {
        id: '1',
        name: 'John',
        habilitations: [
          { id: 'h1', competence_id: 'c1', level: 'base' },
        ],
      },
    ]

    store.updateHabilitation('1', 'h1', { level: 'advanced' })

    expect(store.users[0].habilitations[0].level).toBe('advanced')
  })

  it('should remove habilitation from user', () => {
    const store = useUserStore()
    store.users = [
      {
        id: '1',
        name: 'John',
        habilitations: [
          { id: 'h1', competence_id: 'c1' },
          { id: 'h2', competence_id: 'c2' },
        ],
      },
    ]

    store.removeHabilitation('1', 'h1')

    expect(store.users[0].habilitations).toHaveLength(1)
    expect(store.users[0].habilitations[0].id).toBe('h2')
  })

  it('should use cache for subsequent fetches', async () => {
    const mockUsers = [{ id: '1', name: 'John' }]
    userService.getAll.mockResolvedValue(mockUsers)

    const store = useUserStore()

    // First fetch
    await store.fetchUsers()
    expect(userService.getAll).toHaveBeenCalledTimes(1)

    // Second fetch (should use cache)
    await store.fetchUsers()
    expect(userService.getAll).toHaveBeenCalledTimes(1)

    // Force refresh
    await store.fetchUsers(true)
    expect(userService.getAll).toHaveBeenCalledTimes(2)
  })

  it('should handle fetch errors', async () => {
    const error = new Error('Network error')
    userService.getAll.mockRejectedValue(error)

    const store = useUserStore()

    await expect(store.fetchUsers()).rejects.toThrow('Network error')
    expect(store.error).toBe('Network error')
    expect(store.loading).toBe(false)
  })
})
