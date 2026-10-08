import { describe, expect, it } from 'vitest'
import {
  canManageCustomRoleForActor,
  CUSTOM_ROLE_PLACEHOLDER,
  sanitizeSelectedRole,
} from '@/modules/clienta/pages/leadership/personnelRoleGuards'

describe('PersonnelList custom role guards', () => {
  describe('canManageCustomRoleForActor', () => {
    it('returns false when there is no authenticated actor', () => {
      expect(canManageCustomRoleForActor(null)).toBe(false)
      expect(canManageCustomRoleForActor(undefined)).toBe(false)
    })

    it('returns true for super admin', () => {
      expect(canManageCustomRoleForActor({
        user_type: 'super_admin',
      })).toBe(true)
    })

    it('returns true when navigation permissions include roles.create', () => {
      expect(canManageCustomRoleForActor({
        user_type: 'company',
        effective_permissions: ['users.read', 'roles.create'],
      })).toBe(true)
    })

    it('returns false when active scoped permissions do not include roles.create', () => {
      expect(canManageCustomRoleForActor({
        user_type: 'company',
        permissions: ['roles.create'],
        active_scoped_permissions: ['users.read'],
      })).toBe(false)
    })
  })

  describe('sanitizeSelectedRole', () => {
    it('replaces custom placeholder with fallback role when actor cannot manage custom roles', () => {
      expect(sanitizeSelectedRole({
        selectedRole: CUSTOM_ROLE_PLACEHOLDER,
        canManageCustomRole: false,
        fallbackRole: 'lecteur',
      })).toBe('lecteur')
    })

    it('keeps custom placeholder when actor can manage custom roles', () => {
      expect(sanitizeSelectedRole({
        selectedRole: CUSTOM_ROLE_PLACEHOLDER,
        canManageCustomRole: true,
        fallbackRole: 'lecteur',
      })).toBe(CUSTOM_ROLE_PLACEHOLDER)
    })

    it('returns normalized selected role when non-custom role is chosen', () => {
      expect(sanitizeSelectedRole({
        selectedRole: '  site_manager  ',
        canManageCustomRole: false,
        fallbackRole: 'lecteur',
      })).toBe('site_manager')
    })
  })
})
