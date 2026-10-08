/**
 * User & Site Management Types
 */

import type { PermissionSlug } from './permissions'

export interface Site {
  id: number
  tenant_id: number
  name: string
  address: string
  city: string
  country: string
  phone?: string
  email?: string
  is_headquarters: boolean
  manager_id?: number
  manager?: User
  created_at: string
  updated_at: string
}

export interface User {
  id: number
  tenant_id: number
  name: string
  first_name?: string
  last_name?: string
  full_name?: string
  display_name?: string
  email: string
  phone?: string
  avatar?: string
  role: UserRole
  user_type: 'super_admin' | 'company' | 'clientb'
  position?: string
  job_title?: string
  site_id?: number
  site_name?: string
  site?: Site
  permissions: PermissionSlug[]
  is_active: boolean
  email_verified_at?: string
  created_at: string
  updated_at: string
}

export type UserRole = 'super_admin' | 'company' | 'clientb'

export interface Collaborator extends Omit<User, 'role'> {
  role: 'company'
  position: string
  site_id: number
  hired_date?: string
}

export interface CreateCollaboratorPayload {
  name: string
  email: string
  phone?: string
  position: string
  site_id: number
  permissions: PermissionSlug[]
  password: string
  password_confirmation: string
}

export interface UpdateCollaboratorPayload {
  name?: string
  email?: string
  phone?: string
  position?: string
  site_id?: number
  permissions?: PermissionSlug[]
  is_active?: boolean
}

export interface CreateSitePayload {
  name: string
  address: string
  city: string
  country: string
  phone?: string
  email?: string
  manager_id?: number
}

export interface UpdateSitePayload {
  name?: string
  address?: string
  city?: string
  country?: string
  phone?: string
  email?: string
  manager_id?: number
}
