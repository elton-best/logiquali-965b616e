export function hasReadAccess (permissions?: Record<string, boolean>): boolean {
  if (!permissions || Object.keys(permissions).length === 0) {
    return false
  }

  const directKeys = ['read', 'access', 'can_read']
  for (const key of directKeys) {
    if (key in permissions) {
      return Boolean(permissions[key])
    }
  }

  return Object.values(permissions).some(Boolean)
}
