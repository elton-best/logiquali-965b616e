import type { PermissionModule, Role } from '@/services/permissionService'
import { computed, ref } from 'vue'
import permissionService from '@/services/permissionService'

export interface PermissionProfile {
  label: string
  description: string
  permissions: string[]
}

export interface PermissionGroupItem {
  label: string
  value: string
}

export interface PermissionGroup {
  name: string
  label: string
  icon: string
  permissions: PermissionGroupItem[]
}

// Deprecated static exports kept for compatibility with legacy imports.
export const permissionProfiles: Record<string, PermissionProfile> = {}
export const permissionGroups: PermissionGroup[] = []

const MODULE_ICONS: Record<string, string> = {
  site: 'mdi-map-marker',
  sites: 'mdi-map-marker',
  user: 'mdi-account-group',
  users: 'mdi-account-group',
  personnel: 'mdi-account-group',
  subscription: 'mdi-credit-card',
  subscriptions: 'mdi-credit-card',
  document: 'mdi-file-document',
  documents: 'mdi-file-document',
  process: 'mdi-sitemap',
  processes: 'mdi-sitemap',
  risk: 'mdi-alert',
  risks: 'mdi-alert',
  audit: 'mdi-clipboard-check',
  audits: 'mdi-clipboard-check',
  nc: 'mdi-alert-circle',
  action: 'mdi-check-circle',
  actions: 'mdi-check-circle',
  indicator: 'mdi-chart-line',
  indicators: 'mdi-chart-line',
}

const ACTION_LABELS: Record<string, string> = {
  read: 'Lecture',
  view: 'Consultation',
  create: 'Creation',
  update: 'Modification',
  edit: 'Modification',
  delete: 'Suppression',
  validate: 'Validation',
  approve: 'Approbation',
  assign: 'Assignation',
  verify: 'Verification',
  analyze: 'Analyse',
  close: 'Cloture',
  track: 'Suivi',
  conduct: 'Conduite',
  export: 'Export',
  publish: 'Publication',
  archive: 'Archivage',
  review: 'Revision',
  assess: 'Evaluation',
  treat: 'Traitement',
  monitor: 'Surveillance',
  respond: 'Reponse',
  plan: 'Planification',
  manage: 'Gestion',
  configure: 'Configuration',
}

function toLabel (value: string): string {
  return value
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase())
}

function formatPermissionLabel (moduleName: string, actionName: string): string {
  const moduleLabel = toLabel(moduleName)
  const actionLabel = ACTION_LABELS[actionName] || toLabel(actionName)
  return `${moduleLabel} - ${actionLabel}`
}

function buildGroups (modules: PermissionModule[]): PermissionGroup[] {
  return modules.map(module => ({
    name: module.module,
    label: toLabel(module.module),
    icon: MODULE_ICONS[module.module] || 'mdi-shield-key',
    permissions: module.permissions.map(permission => ({
      label: formatPermissionLabel(module.module, permission.action),
      value: permission.name,
    })),
  }))
}

function buildProfiles (roles: Role[]): Record<string, PermissionProfile> {
  return roles.reduce<Record<string, PermissionProfile>>((acc, role) => {
    acc[role.name] = {
      label: toLabel(role.name),
      description: `${role.permissions_count} permission(s)`,
      permissions: role.permissions,
    }
    return acc
  }, {})
}

export function usePermissions () {
  const selectedPermissions = ref<string[]>([])
  const loading = ref(false)
  const availableRoles = ref<Role[]>([])
  const availablePermissionModules = ref<PermissionModule[]>([])

  const dynamicPermissionGroups = computed(() => buildGroups(availablePermissionModules.value))
  const dynamicPermissionProfiles = computed(() => buildProfiles(availableRoles.value))

  async function loadCatalog (): Promise<void> {
    loading.value = true
    try {
      const [roles, permissions] = await Promise.all([
        permissionService.getAvailableRoles(),
        permissionService.getAvailablePermissions(),
      ])
      availableRoles.value = roles
      availablePermissionModules.value = permissions
    } finally {
      loading.value = false
    }
  }

  function selectAll () {
    selectedPermissions.value = dynamicPermissionGroups.value.flatMap(group =>
      group.permissions.map(permission => permission.value),
    )
  }

  function deselectAll () {
    selectedPermissions.value = []
  }

  function selectGroup (groupName: string) {
    const group = dynamicPermissionGroups.value.find(item => item.name === groupName)
    if (!group) {
      return
    }
    for (const permission of group.permissions) {
      if (!selectedPermissions.value.includes(permission.value)) {
        selectedPermissions.value.push(permission.value)
      }
    }
  }

  function deselectGroup (groupName: string) {
    const group = dynamicPermissionGroups.value.find(item => item.name === groupName)
    if (!group) {
      return
    }
    const groupPermissions = new Set(group.permissions.map(permission => permission.value))
    selectedPermissions.value = selectedPermissions.value.filter(permission => !groupPermissions.has(permission))
  }

  function togglePermission (permission: string, value: boolean) {
    if (value && !selectedPermissions.value.includes(permission)) {
      selectedPermissions.value.push(permission)
      return
    }
    if (!value) {
      selectedPermissions.value = selectedPermissions.value.filter(item => item !== permission)
    }
  }

  function applySuggested (permissions: string[]) {
    selectedPermissions.value = [...permissions]
  }

  return {
    selectedPermissions,
    loading,
    availableRoles,
    availablePermissionModules,
    permissionGroups: dynamicPermissionGroups,
    permissionProfiles: dynamicPermissionProfiles,
    loadCatalog,
    selectAll,
    deselectAll,
    selectGroup,
    deselectGroup,
    togglePermission,
    applySuggested,
  }
}
