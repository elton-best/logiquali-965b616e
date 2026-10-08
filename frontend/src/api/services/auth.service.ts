/**
 * Authentication API Service
 */

// import type { ApiResponse } from '@/types/api'
import type {
  AuthResponse,
  LoginCredentials,
  OAuthCredentials,
  RegisterData,
  User,
} from '@/types/models/auth'
import api from '@/api/client'
import { API_ENDPOINTS } from '@/constants'

// Demo accounts for testing (backend not ready)
const DEMO_ACCOUNTS = [
  { email: 'admin@bestexperts.com', password: 'demo', role: 'super_admin', name: 'Super Admin' },
  { email: 'clienta@demo.com', password: 'demo', role: 'client_a', name: 'Client A Demo', approved: true },
  { email: 'clienta-pending@demo.com', password: 'demo', role: 'client_a', name: 'Client A Pending', approved: false },
  { email: 'clientb@demo.com', password: 'demo', role: 'client_b', name: 'Client B Demo' },
]

export const authService = {
  /**
   * Login with email and password (JWT)
   */
  async login (credentials: LoginCredentials): Promise<AuthResponse> {
    // Check if it's a demo account
    const demoAccount = DEMO_ACCOUNTS.find(
      acc => acc.email === credentials.email && acc.password === credentials.password,
    )

    if (demoAccount) {
      // Return mock response for demo accounts
      return {
        user: {
          id: Math.floor(Math.random() * 1000),
          name: demoAccount.name,
          email: demoAccount.email,
          role: demoAccount.role as any,
          tenant_id: 1,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        tenant: {
          id: 1,
          name: 'Demo Company',
          slug: 'demo-company',
          domain: 'demo.BestQHSE.com',
          is_active: true,
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString(),
        },
        access_token: 'demo_access_token_' + Math.random().toString(36).slice(7),
        refresh_token: 'demo_refresh_token_' + Math.random().toString(36).slice(7),
        token_type: 'Bearer',
        expires_in: 3600,
      }
    }

    // If not demo account, call real backend (will fail for now)
    const response = await api.post<AuthResponse>(API_ENDPOINTS.LOGIN, credentials)
    return response.data
  },

  /**
   * Register new user/company
   */
  async register (data: RegisterData): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>(API_ENDPOINTS.REGISTER, data)
    return response.data
  },

  /**
   * Logout
   */
  async logout (): Promise<void> {
    await api.post(API_ENDPOINTS.LOGOUT)
  },

  /**
   * Get current authenticated user
   */
  async me (): Promise<User> {
    const response = await api.get<User>(API_ENDPOINTS.ME)
    return response.data
  },

  /**
   * Refresh access token
   */
  async refreshToken (refreshToken: string): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>(API_ENDPOINTS.REFRESH_TOKEN, {
      refresh_token: refreshToken,
    })
    return response.data
  },

  /**
   * OAuth2 Login (for Client B)
   */
  async oauthLogin (provider: string): Promise<{ redirect_url: string }> {
    const response = await api.post<{ redirect_url: string }>(
      API_ENDPOINTS.OAUTH_LOGIN,
      { provider },
    )
    return response.data
  },

  /**
   * OAuth2 Callback
   */
  async oauthCallback (credentials: OAuthCredentials): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>(
      API_ENDPOINTS.OAUTH_CALLBACK,
      credentials,
    )
    return response.data
  },
}
