/**
 * Environment variables with type safety
 */

interface _ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly VITE_API_TIMEOUT: string
  readonly VITE_APP_NAME: string
  readonly VITE_APP_DESCRIPTION: string
  readonly VITE_APP_VERSION: string
  readonly VITE_OAUTH_GOOGLE_CLIENT_ID: string
  readonly VITE_OAUTH_REDIRECT_URI: string
  readonly VITE_ENABLE_PWA: string
  readonly VITE_ENABLE_ANALYTICS: string
}

export const config = {
  api: {
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    timeout: Number.parseInt(import.meta.env.VITE_API_TIMEOUT || '30000'),
  },
  app: {
    name: import.meta.env.VITE_APP_NAME || 'BestQHSE',
    description: import.meta.env.VITE_APP_DESCRIPTION || 'Plateforme SaaS de Gestion QHSE Multi-tenants',
    version: import.meta.env.VITE_APP_VERSION || '1.0.0',
  },
  oauth: {
    google: {
      clientId: import.meta.env.VITE_OAUTH_GOOGLE_CLIENT_ID || '',
      redirectUri: import.meta.env.VITE_OAUTH_REDIRECT_URI || `${window.location.origin}/auth/oauth/callback`,
    },
  },
  features: {
    pwa: import.meta.env.VITE_ENABLE_PWA === 'true',
    analytics: import.meta.env.VITE_ENABLE_ANALYTICS === 'true',
  },
} as const
