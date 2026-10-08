<template>
  <div>
    <div v-if="showRoleSelection" class="role-section">
      <div class="role-info">
        <v-icon color="info">mdi-information</v-icon>
        <span>{{ roleInfoMessage }}</span>
      </div>

      <v-alert
        v-if="normalizedRoles.length === 0"
        class="mb-4"
        density="comfortable"
        type="warning"
        variant="tonal"
      >
        {{ emptyRolesMessage }}
      </v-alert>

      <div class="roles-grid">
        <label
          v-for="role in normalizedRoles"
          :key="role.value"
          class="role-card"
          :class="{ selected: selectedRole === role.value }"
        >
          <input
            v-model="selectedRole"
            class="role-radio"
            type="radio"
            :value="role.value"
          >
          <div class="role-content">
            <v-icon :color="role.color" size="32">{{ role.icon }}</v-icon>
            <div class="role-label">{{ role.label }}</div>
            <div class="role-description">{{ role.description }}</div>
          </div>
        </label>
      </div>
    </div>

    <div v-if="showPermissions" class="permissions-section mt-6">
      <div class="permissions-info">
        <v-icon color="info">mdi-information</v-icon>
        <span>
          Permissions actives
          <template v-if="hasScopedPermissions">(site/souscription)</template>
          <template v-else>(estimées)</template>:
          <strong>{{ displayedActivePermissionsCount }}</strong>.
        </span>
      </div>

      <p v-if="hasScopedPermissions" class="text-caption text-medium-emphasis mt-2 mb-0">
        Inclut automatiquement les permissions de lecture liées aux normes actives du site.
      </p>

      <p v-if="showTechnicalBreakdown" class="text-caption text-medium-emphasis mt-2 mb-0">
        {{ permissionsInfoMessagePrefix }} <strong>{{ inheritedRolePermissions.length }}</strong> ·
        Permissions directes personnalisées: <strong>{{ directPermissionsCount }}</strong> ·
        Permissions effectives estimées: <strong>{{ effectivePermissionsCount }}</strong>.
      </p>

      <v-alert
        v-if="selectedRole && inheritedRolePermissions.length > 0"
        class="mt-3"
        density="comfortable"
        icon="mdi-information-outline"
        type="info"
        variant="tonal"
      >
        Les permissions héritées du rôle sont en lecture seule ici. Pour les modifier, changez le rôle attribué.
      </v-alert>

      <details v-if="selectedRole" class="role-permissions-preview">
        <summary class="role-preview-title">
          <v-icon color="primary" size="18">mdi-shield-account</v-icon>
          Permissions héritées du rôle (lecture seule)
        </summary>
        <div v-if="inheritedRolePermissions.length > 0" class="role-permissions-chips">
          <span
            v-for="perm in inheritedRolePermissions"
            :key="perm"
            class="role-permission-chip"
          >
            {{ formatPermissionLabel(perm) }}
          </span>
        </div>
        <div v-else class="role-permissions-empty">
          Ce rôle ne fournit pas de permissions héritées.
        </div>
      </details>

      <div class="permissions-grid">
        <div
          v-for="module in permissionGroups"
          :key="module.id"
          class="permission-module"
        >
          <div class="module-header">
            <v-icon :color="module.color">{{ module.icon }}</v-icon>
            <span class="module-name">{{ module.name }}</span>
          </div>
          <div class="module-permissions">
            <label
              v-for="perm in module.permissions"
              :key="perm"
              class="permission-checkbox"
            >
              <input
                :checked="isPermissionChecked(perm)"
                :disabled="isPermissionLocked(perm)"
                type="checkbox"
                :value="perm"
                @change="togglePermission(perm, ($event.target as HTMLInputElement).checked)"
              >
              <span>{{ formatPermissionLabel(perm) }}</span>
              <v-icon
                v-if="isInheritedPermission(perm)"
                color="primary"
                size="16"
                title="Permission héritée du rôle"
              >
                mdi-account-lock
              </v-icon>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PermissionModule, Role } from '@/services/permissionService'
  import { computed, watch } from 'vue'

  interface NormalizedRole {
    value: string
    label: string
    description: string
    icon: string
    color: string
    permissionsList: string[]
  }

  interface Props {
    modelValue: string
    permissions?: string[]
    roles?: Role[]
    permissionModules?: PermissionModule[]
    scopedPermissionNames?: string[]
    catalogReadPermissionNames?: string[]
    showRoleSelection?: boolean
    showPermissions?: boolean
    emptyRolesMessage?: string
    roleInfoMessage?: string
    permissionsInfoMessagePrefix?: string
    lockDefaultRead?: boolean
    showTechnicalBreakdown?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    permissions: () => [],
    roles: () => [],
    permissionModules: () => [],
    scopedPermissionNames: () => [],
    catalogReadPermissionNames: () => [],
    showRoleSelection: true,
    showPermissions: true,
    emptyRolesMessage: 'Aucun rôle n\'est éligible pour ce site. Vérifiez les normes actives du site.',
    roleInfoMessage: 'Choisissez le rôle puis ajustez les permissions directes si nécessaire.',
    permissionsInfoMessagePrefix: 'Permissions héritées du rôle:',
    lockDefaultRead: false,
    showTechnicalBreakdown: false,
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
    (e: 'update:permissions', value: string[]): void
  }>()

  const selectedRole = computed({
    get: () => props.modelValue,
    set: value => emit('update:modelValue', value),
  })

  const directPermissions = computed({
    get: () => props.permissions,
    set: value => emit('update:permissions', value),
  })

  const roleInfoMessage = computed(() => props.roleInfoMessage)
  const permissionsInfoMessagePrefix = computed(() => props.permissionsInfoMessagePrefix)
  const hasScopedPermissions = computed(() => props.scopedPermissionNames.length > 0)

  const roleVisuals: Record<string, { icon: string, color: string, description: string }> = {
    admin_entreprise: {
      icon: 'mdi-shield-crown',
      color: 'purple',
      description: 'Administration complete de l entreprise',
    },
    site_manager: {
      icon: 'mdi-office-building',
      color: 'primary',
      description: 'Gestion complete du site',
    },
    lecteur: {
      icon: 'mdi-eye',
      color: 'grey',
      description: 'Consultation uniquement',
    },
  }

  const moduleStyle: Record<string, { icon: string, color: string }> = {
    nc: { icon: 'mdi-alert-octagon', color: 'error' },
    audits: { icon: 'mdi-clipboard-check', color: 'info' },
    risks: { icon: 'mdi-alert-circle', color: 'warning' },
    actions: { icon: 'mdi-check-circle', color: 'success' },
    reclamations: { icon: 'mdi-comment-alert', color: 'deep-orange' },
    documents: { icon: 'mdi-file-document', color: 'primary' },
    processes: { icon: 'mdi-sitemap', color: 'indigo' },
    indicators: { icon: 'mdi-chart-line', color: 'teal' },
    objectives: { icon: 'mdi-bullseye-arrow', color: 'pink' },
    plans: { icon: 'mdi-file-document-edit', color: 'purple' },
    dashboard: { icon: 'mdi-view-dashboard', color: 'cyan' },
    leadership: { icon: 'mdi-account-tie', color: 'amber' },
    personnel: { icon: 'mdi-account-group', color: 'amber-darken-2' },
    sites: { icon: 'mdi-map-marker', color: 'green-darken-1' },
  }

  const normalizedRoles = computed<NormalizedRole[]>(() => {
    return props.roles.map(role => {
      const visual = roleVisuals[role.name] || {
        icon: 'mdi-shield-account',
        color: 'primary',
        description: 'Role metier',
      }

      return {
        value: role.name,
        label: role.label || role.name,
        description: visual.description,
        icon: visual.icon,
        color: visual.color,
        permissionsList: role.permissions || [],
      }
    })
  })

  const selectedRoleDefinition = computed(() => {
    if (!selectedRole.value) return null
    return normalizedRoles.value.find(role => role.value === selectedRole.value) || null
  })

  const inheritedRolePermissions = computed(() => {
    return Array.from(new Set(selectedRoleDefinition.value?.permissionsList || []))
  })

  const directPermissionsCount = computed(() => {
    return directPermissions.value.filter(
      permission => !inheritedRolePermissions.value.includes(permission),
    ).length
  })

  const effectivePermissionsCount = computed(() => {
    return Array.from(
      new Set([
        ...inheritedRolePermissions.value,
        ...directPermissions.value,
        ...props.catalogReadPermissionNames,
      ]),
    ).length
  })

  const activeScopedPermissionsCount = computed(() => {
    if (!hasScopedPermissions.value) {
      return 0
    }

    const effective = Array.from(
      new Set([
        ...inheritedRolePermissions.value,
        ...directPermissions.value,
        ...props.catalogReadPermissionNames,
      ]),
    )
    const scopedSet = new Set(props.scopedPermissionNames)
    return effective.filter(permission => scopedSet.has(permission)).length
  })

  const displayedActivePermissionsCount = computed(() => {
    if (hasScopedPermissions.value) {
      return activeScopedPermissionsCount.value
    }

    return effectivePermissionsCount.value
  })

  const permissionGroups = computed(() => {
    return props.permissionModules.map(module => {
      const style = moduleStyle[module.module] || {
        icon: 'mdi-shield-key',
        color: 'grey',
      }
      return {
        id: module.module,
        name: module.module,
        icon: style.icon,
        color: style.color,
        permissions: module.permissions.map(permission => permission.name),
      }
    })
  })

  function isInheritedPermission (permission: string): boolean {
    return inheritedRolePermissions.value.includes(permission)
  }

  function isPermissionLocked (permission: string): boolean {
    return isInheritedPermission(permission)
  }

  function isPermissionChecked (permission: string): boolean {
    if (isInheritedPermission(permission)) {
      return true
    }

    return directPermissions.value.includes(permission)
  }

  function togglePermission (permission: string, checked: boolean) {
    if (isPermissionLocked(permission)) {
      return
    }

    if (checked && !directPermissions.value.includes(permission)) {
      directPermissions.value = [...directPermissions.value, permission]
      return
    }

    if (!checked) {
      directPermissions.value = directPermissions.value.filter(current => current !== permission)
    }
  }

  function formatPermissionLabel (permission: string): string {
    const segments = permission.split('.').filter(Boolean)
    const moduleKey = segments[0] || ''
    const resourceKey = segments.length > 2 ? segments[1] : ''
    const actionKey = segments.length > 1 ? segments.at(-1) || '' : ''

    const humanize = (value: string) =>
      value.replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase())

    const moduleLabel = humanize(moduleKey)
    const resourceLabel = resourceKey ? humanize(resourceKey) : ''

    const actionLabels: Record<string, string> = {
      read: 'Lecture',
      create: 'Creation',
      update: 'Modification',
      delete: 'Suppression',
      validate: 'Validation',
      approve: 'Approbation',
      assign: 'Assignation',
      assign_roles: 'Assignation des roles',
      verify: 'Verification',
      analyze: 'Analyse',
      close: 'Cloture',
      track: 'Suivi',
      conduct: 'Conduite',
      export: 'Export',
      publish: 'Publication',
      archive: 'Archivage',
      assess: 'Evaluation',
      treat: 'Traitement',
      monitor: 'Surveillance',
      respond: 'Reponse',
      plan: 'Planification',
      manage: 'Gestion',
      configure: 'Configuration',
      download: 'Telechargement',
    }

    const actionLabel = actionLabels[actionKey] || humanize(actionKey)
    const scopeLabel = resourceLabel ? `${moduleLabel} / ${resourceLabel}` : moduleLabel
    return `${scopeLabel} - ${actionLabel}`
  }

  watch(selectedRole, () => {
    directPermissions.value = directPermissions.value.filter(
      permission => !inheritedRolePermissions.value.includes(permission),
    )
  })
</script>

<style scoped>
  .role-section {
    padding: 16px 0;
  }

  .role-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(91, 141, 217, 0.05);
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.875rem;
    color: #666;
  }

  .roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
  }

  .role-card {
    position: relative;
    padding: 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .role-card:hover {
    border-color: #5b8dd9;
    box-shadow: 0 4px 12px rgba(91, 141, 217, 0.15);
    transform: translateY(-2px);
  }

  .role-card.selected {
    border-color: #5b8dd9;
    background: rgba(91, 141, 217, 0.05);
    box-shadow: 0 4px 12px rgba(91, 141, 217, 0.2);
  }

  .role-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }

  .role-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
  }

  .role-label {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
    margin-top: 4px;
  }

  .role-description {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.4;
  }

  .permissions-section {
    margin-top: 24px;
  }

  .permissions-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 0.875rem;
    color: #1e40af;
  }

  .permissions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }

  .role-permissions-preview {
    border: 2px solid #dbeafe;
    border-radius: 12px;
    background: #f8fbff;
    padding: 16px;
    margin-bottom: 20px;
  }

  .role-preview-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 10px;
  }

  .role-permissions-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .role-permission-chip {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1d4ed8;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 0.75rem;
    font-weight: 600;
  }

  .role-permissions-empty {
    font-size: 0.875rem;
    color: #475569;
  }

  .permission-module {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
  }

  .module-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
  }

  .module-name {
    font-weight: 700;
    font-size: 1rem;
    color: #1e293b;
  }

  .module-permissions {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .permission-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .permission-checkbox input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .permission-checkbox input[type="checkbox"]:disabled {
    cursor: not-allowed;
  }

  @media (max-width: 768px) {
    .permissions-grid {
      grid-template-columns: 1fr;
    }

    .roles-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
