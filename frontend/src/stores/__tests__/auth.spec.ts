import type { User } from '@/types/api'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../auth'

describe('Auth Store', () => {
  beforeEach(() => {
    // Create a fresh pinia instance for each test
    setActivePinia(createPinia())
    // Clear localStorage mock
    vi.clearAllMocks()
  })

  describe('Initial State', () => {
    it('should have null user and token initially', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.loading).toBe(false)
    })

    it('should not be authenticated initially', () => {
      const store = useAuthStore()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('setAuth', () => {
    it('should set user and token', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }
      const mockToken = 'test-token-123'

      store.setAuth(mockUser, mockToken)

      expect(store.user).toEqual(mockUser)
      expect(store.token).toEqual(mockToken)
      expect(store.isAuthenticated).toBe(true)
    })

    it('should persist auth to localStorage', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'Jane Doe',
        email: 'jane@example.com',
        username: 'janedoe',
        user_type: 'company',
      }
      const mockToken = 'test-token-456'

      store.setAuth(mockUser, mockToken)

      expect(window.localStorage.setItem).toHaveBeenCalledWith(
        'user',
        JSON.stringify(mockUser),
      )

      const useHttpOnlyCookieAuth
        = String(import.meta.env.VITE_AUTH_USE_HTTPONLY_COOKIE ?? 'false')
          === 'true'

      if (useHttpOnlyCookieAuth) {
        expect(window.localStorage.setItem).not.toHaveBeenCalledWith(
          'access_token',
          mockToken,
        )
      } else {
        expect(window.localStorage.setItem).toHaveBeenCalledWith(
          'access_token',
          mockToken,
        )
      }
    })
  })

  describe('clearAuth', () => {
    it('should clear user and token', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'clientb',
      }

      store.setAuth(mockUser, 'test-token')
      expect(store.isAuthenticated).toBe(true)

      store.clearAuth()

      expect(store.user).toBeNull()
      expect(store.token).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })

    it('should remove auth from localStorage', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')
      store.clearAuth()

      expect(window.localStorage.removeItem).toHaveBeenCalledWith('access_token')
      expect(window.localStorage.removeItem).toHaveBeenCalledWith('user')
    })
  })

  describe('updateUser', () => {
    it('should update user data', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')
      store.updateUser({ name: 'John Updated' })

      expect(store.user?.name).toBe('John Updated')
      expect(store.user?.email).toBe('john@example.com')
    })

    it('should persist updated user to localStorage', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')
      vi.clearAllMocks()

      store.updateUser({ email: 'john.new@example.com' })

      expect(window.localStorage.setItem).toHaveBeenCalledWith(
        'user',
        expect.stringContaining('john.new@example.com'),
      )
    })

    it('should not update if user is null', () => {
      const store = useAuthStore()
      expect(store.user).toBeNull()

      store.updateUser({ name: 'Test User' })

      expect(store.user).toBeNull()
    })
  })

  describe('Computed Properties', () => {
    beforeEach(() => {
      vi.clearAllMocks()
    })

    it('should compute userType correctly', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.userType).toBe('super_admin')
      expect(store.isAdmin).toBe(true)
      expect(store.isClientA).toBe(false)
      expect(store.isClientB).toBe(false)
    })

    it('should identify company users correctly', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 2,
        name: 'Enterprise User',
        email: 'enterprise@example.com',
        username: 'enterprise',
        user_type: 'company',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.isClientA).toBe(true)
      expect(store.isAdmin).toBe(false)
      expect(store.isClientB).toBe(false)
    })

    it('should identify client users correctly', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 3,
        name: 'Client User',
        email: 'client@example.com',
        username: 'client',
        user_type: 'clientb',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.isClientB).toBe(true)
      expect(store.isAdmin).toBe(false)
      expect(store.isClientA).toBe(false)
    })

    it('should compute userName correctly', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.userName).toBe('johndoe')
    })

    it('should fallback to name if username is missing', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: '',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.userName).toBe('John Doe')
    })

    it('should compute userEmail correctly', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        username: 'johndoe',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')

      expect(store.userEmail).toBe('john@example.com')
    })
  })

  describe('Loading State', () => {
    it('should handle loading state', () => {
      const store = useAuthStore()

      expect(store.loading).toBe(false)
      store.loading = true
      expect(store.loading).toBe(true)
      store.loading = false
      expect(store.loading).toBe(false)
    })
  })

  describe('getDashboardRoute', () => {
    it('should return superadmin dashboard route for super admins', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com',
        username: 'admin',
        user_type: 'super_admin',
      }

      store.setAuth(mockUser, 'test-token')

      const route = store.getDashboardRoute()
      expect(route).toContain('admin')
    })

    it('should return company dashboard route for company users', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 2,
        name: 'Enterprise User',
        email: 'enterprise@example.com',
        username: 'enterprise',
        user_type: 'company',
      }

      store.setAuth(mockUser, 'test-token')

      const route = store.getDashboardRoute()
      expect(route).toContain('/company/dashboard')
    })

    it('should return client dashboard route for client users', () => {
      const store = useAuthStore()
      const mockUser: User = {
        id: 3,
        name: 'Client User',
        email: 'client@example.com',
        username: 'client',
        user_type: 'clientb',
      }

      store.setAuth(mockUser, 'test-token')

      const route = store.getDashboardRoute()
      expect(route).toContain('clientb')
    })
  })
})
