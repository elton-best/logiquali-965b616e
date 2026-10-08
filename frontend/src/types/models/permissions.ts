/**
 * Permission System Types
 * Granular permissions by module
 */

export interface Permission {
  id: number
  name: string
  slug: string
  module: ModuleName
  description: string
  created_at: string
  updated_at: string
}

export type ModuleName = string

export interface PermissionGroup {
  module: ModuleName
  label: string
  permissions: Permission[]
}

export type PermissionSlug = string
