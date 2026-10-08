/**
 * User & Authentication Types
 */

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  tenant_id: number
  avatar?: string
  permissions?: string[]
  created_at: string
  updated_at: string
}

export type UserRole = 'super_admin' | 'admin' | 'manager' | 'user' | 'client'

export interface Tenant {
  id: number
  name: string
  slug: string
  domain?: string
  logo?: string
  settings?: TenantSettings
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface TenantSettings {
  locale?: string
  timezone?: string
  theme?: string
  features?: string[]
}

export interface LoginCredentials {
  email: string
  password: string
  tenant_id?: number
  remember?: boolean
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  tenant_name?: string
  company_name?: string
}

export interface AuthResponse {
  user: User
  tenant: Tenant
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export interface OAuthCredentials {
  provider: 'google' | 'microsoft' | 'azure'
  code: string
  state?: string
}
