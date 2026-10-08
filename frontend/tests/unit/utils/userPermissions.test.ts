import { describe, expect, it } from 'vitest'
import {
  extractPermissionNames,
  getEffectivePermissionNames,
  getEffectivePermissionSet,
  getNavigationPermissionNames,
} from '@/modules/clienta/utils/userPermissions'

describe('userPermissions utils', () => {
  it('extracts permission names from mixed payloads', () => {
    const input = [
      'users.read',
      { name: 'users.update' },
      { attributes: { name: 'users.create' } },
      { foo: 'bar' },
      null,
      undefined,
    ]

    expect(extractPermissionNames(input as any)).toEqual([
      'users.read',
      'users.update',
      'users.create',
    ])
  })

  it('builds normalized effective permissions from all supported sources', () => {
    const user = {
      permissions: ['sites.read'],
      role_permissions: [{ name: 'users.read' }],
      effective_permissions: [{ attributes: { name: 'audits.read' } }],
      active_scoped_permissions: ['documents.read'],
    }

    expect(getEffectivePermissionNames(user as any)).toEqual([
      'documents.read',
    ])
  })

  it('falls back to effective permissions when active scoped permissions are absent', () => {
    const user = {
      effective_permissions: ['users.read', 'users.update'],
    }

    expect(getEffectivePermissionNames(user as any)).toEqual([
      'users.read',
      'users.update',
    ])
  })

  it('does not fallback to legacy/effective permissions when active scope enforcement is enabled', () => {
    const user = {
      authz_enforce_active_scope: true,
      active_scoped_permissions: [],
      effective_permissions: ['users.read', 'users.update'],
      all_permissions: ['roles.create'],
    }

    expect(getEffectivePermissionNames(user as any)).toEqual([])
  })

  it('returns a Set for efficient access checks', () => {
    const user = {
      effective_permissions: ['users.read', 'users.update'],
    }

    const permissionSet = getEffectivePermissionSet(user as any)
    expect(permissionSet.has('users.read')).toBe(true)
    expect(permissionSet.has('users.update')).toBe(true)
    expect(permissionSet.size).toBe(2)
  })

  it('prefers active scoped permissions for navigation checks', () => {
    const user = {
      active_scoped_permissions: ['documents.read'],
      effective_permissions: ['actions.read', 'audits.read'],
    }

    expect(getNavigationPermissionNames(user as any)).toEqual([
      'documents.read',
    ])
  })
})
