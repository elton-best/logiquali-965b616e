/**
 * OAuth2 Service
 * Handles Google OAuth authentication flow
 */

import type { ApiResponse } from '@/types/api'
import type { User } from '@/types/models/users'
import api from '@/api/client'

export interface OAuthTokenResponse {
  access_token: string
  refresh_token: string
  user: User
  tenant_id?: number
}

class OAuth2Service {
  /**
   * Exchange Google authorization code for access token
   */
  async googleCallback (code: string): Promise<ApiResponse<OAuthTokenResponse>> {
    return api.post<OAuthTokenResponse>('/auth/oauth/google/callback', { code })
  }

  /**
   * Link Google account to existing user
   */
  async linkGoogleAccount (code: string): Promise<ApiResponse<{ success: boolean }>> {
    return api.post('/auth/oauth/google/link', { code })
  }

  /**
   * Unlink Google account from user
   */
  async unlinkGoogleAccount (): Promise<ApiResponse<{ success: boolean }>> {
    return api.delete('/auth/oauth/google/unlink')
  }

  /**
   * Get OAuth provider status (is account linked?)
   */
  async getProviderStatus (): Promise<ApiResponse<{
    google: { linked: boolean, email?: string }
  }>> {
    return api.get('/auth/oauth/status')
  }
}

export const oauth2Service = new OAuth2Service()
