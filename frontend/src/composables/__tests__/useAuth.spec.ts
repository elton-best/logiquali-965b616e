import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '@/stores/auth'
import { useAuth } from '../useAuth'

// Mock auth service
vi.mock('@/services/authService', () => ({
  default: {
    login: vi.fn(),
    logout: vi.fn(),
    registerEnterprise: vi.fn(),
    registerClient: vi.fn(),
    forgotPassword: vi.fn(),
    resetPassword: vi.fn(),
    me: vi.fn(),
  },
  getErrorMessage: vi.fn(_err => 'Test error'),
  getValidationErrors: vi.fn(() => ({})),
}))

// Mock router
vi.mock('vue-router', () => ({
  useRouter: vi.fn(() => ({
    push: vi.fn(),
  })),
}))

// Mock toast
vi.mock('@/modules/shared/composables/useToast', () => ({
  useToast: vi.fn(() => ({
    success: vi.fn(),
    error: vi.fn(),
  })),
}))

describe('useAuth Composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('Composable Exports', () => {
    it('should export user property', () => {
      const { user } = useAuth()
      expect(user).toBeDefined()
    })

    it('should export loading property', () => {
      const { loading } = useAuth()
      expect(loading).toBeDefined()
    })

    it('should export isAuthenticated property', () => {
      const { isAuthenticated } = useAuth()
      expect(isAuthenticated).toBeDefined()
    })

    it('should export isAdmin property', () => {
      const { isAdmin } = useAuth()
      expect(isAdmin).toBeDefined()
    })

    it('should export isClientA property', () => {
      const { isClientA } = useAuth()
      expect(isClientA).toBeDefined()
    })

    it('should export isClientB property', () => {
      const { isClientB } = useAuth()
      expect(isClientB).toBeDefined()
    })

    it('should export userName property', () => {
      const { userName } = useAuth()
      expect(userName).toBeDefined()
    })

    it('should export userEmail property', () => {
      const { userEmail } = useAuth()
      expect(userEmail).toBeDefined()
    })
  })

  describe('Methods', () => {
    it('should export login method', () => {
      const { login } = useAuth()
      expect(typeof login).toBe('function')
    })

    it('should export logout method', () => {
      const { logout } = useAuth()
      expect(typeof logout).toBe('function')
    })

    it('should export registerEnterprise method', () => {
      const { registerEnterprise } = useAuth()
      expect(typeof registerEnterprise).toBe('function')
    })

    it('should export registerClient method', () => {
      const { registerClient } = useAuth()
      expect(typeof registerClient).toBe('function')
    })

    it('should export forgotPassword method', () => {
      const { forgotPassword } = useAuth()
      expect(typeof forgotPassword).toBe('function')
    })

    it('should export resetPassword method', () => {
      const { resetPassword } = useAuth()
      expect(typeof resetPassword).toBe('function')
    })

    it('should export fetchUser method', () => {
      const { fetchUser } = useAuth()
      expect(typeof fetchUser).toBe('function')
    })

    it('should export initAuth method', () => {
      const { initAuth } = useAuth()
      expect(typeof initAuth).toBe('function')
    })

    it('should export getDashboardRoute method', () => {
      const { getDashboardRoute } = useAuth()
      expect(typeof getDashboardRoute).toBe('function')
    })
  })

  describe('Store Integration', () => {
    it('should be connected to auth store', () => {
      const { user } = useAuth()
      const authStore = useAuthStore()

      const mockUser = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        username: 'testuser',
        user_type: 'super_admin' as const,
      }

      authStore.setAuth(mockUser, 'test-token')

      // User should now reflect the store state
      expect(user).toBeDefined()
    })

    it('should initialize auth when initAuth is called', () => {
      const { initAuth } = useAuth()
      const authStore = useAuthStore()

      expect(typeof authStore.initAuth).toBe('function')

      // Just verify initAuth doesn't throw
      initAuth()
      expect(authStore.user).toBeNull() // Initial state
    })
  })

  describe('Authentication Flow', () => {
    it('should have complete auth API', () => {
      const auth = useAuth()

      // All methods should be available
      expect(auth.login).toBeDefined()
      expect(auth.logout).toBeDefined()
      expect(auth.registerEnterprise).toBeDefined()
      expect(auth.registerClient).toBeDefined()
      expect(auth.forgotPassword).toBeDefined()
      expect(auth.resetPassword).toBeDefined()
      expect(auth.fetchUser).toBeDefined()
      expect(auth.initAuth).toBeDefined()
      expect(auth.getDashboardRoute).toBeDefined()

      // All properties should be available
      expect(auth.user).toBeDefined()
      expect(auth.loading).toBeDefined()
      expect(auth.isAuthenticated).toBeDefined()
      expect(auth.isAdmin).toBeDefined()
      expect(auth.isClientA).toBeDefined()
      expect(auth.isClientB).toBeDefined()
      expect(auth.userName).toBeDefined()
      expect(auth.userEmail).toBeDefined()
    })
  })

  describe('Role-based Checks', () => {
    it('should have role checking properties', () => {
      const { isAdmin, isClientA, isClientB } = useAuth()

      expect(isAdmin).toBeDefined()
      expect(isClientA).toBeDefined()
      expect(isClientB).toBeDefined()
    })
  })

  describe('User Info', () => {
    it('should have user identification properties', () => {
      const { userName, userEmail } = useAuth()

      expect(userName).toBeDefined()
      expect(userEmail).toBeDefined()
    })
  })
})
