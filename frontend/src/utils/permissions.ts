function splitPermission (permission: string): { resource: string, action: string } | null {
  if (!permission || typeof permission !== 'string') {
    return null
  }
  const index = permission.lastIndexOf('.')
  if (index <= 0 || index >= permission.length - 1) {
    return null
  }
  return {
    resource: permission.slice(0, index),
    action: permission.slice(index + 1),
  }
}

function normalizePermission (permission: string): string | null {
  const parsed = splitPermission(permission)
  if (!parsed) {
    return null
  }

  return `${parsed.resource}.${parsed.action}`
}

export function expandPermissionAliases (permission: string): string[] {
  const normalized = normalizePermission(permission)
  if (!normalized) {
    return []
  }

  return [normalized]
}

export function normalizePermissionList (permissions: Array<string | null | undefined>): string[] {
  const normalized = new Set<string>()
  for (const permission of permissions) {
    if (!permission) {
      continue
    }
    const normalizedPermission = normalizePermission(permission)
    if (normalizedPermission) {
      normalized.add(normalizedPermission)
    }
  }
  return Array.from(normalized)
}
