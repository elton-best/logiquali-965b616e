<template>
  <div class="permissions-section">
    <div class="permissions-info">
      <v-icon color="info">mdi-information</v-icon>
      <span>
        Permissions héritées du rôle:
        <strong>{{ inheritedRolePermissions.length }}</strong>. Permissions directes personnalisées:
        <strong>{{ directPermissionsCount }}</strong>. Permissions effectives estimées:
        <strong>{{ effectivePermissionsCount }}</strong>. Permissions actives selon site/souscription:
        <strong>{{ activeScopedPermissionsCount }}</strong>.
      </span>
    </div>
    <p class="text-caption text-medium-emphasis mt-2 mb-0">
      Les permissions actives dépendent du site et des souscriptions en cours.
    </p>

    <div class="permissions-search mt-3">
      <input
        v-model.trim="permissionSearch"
        class="permissions-search-input"
        placeholder="Rechercher une permission (module, action, ressource)"
        type="text"
      >
    </div>

    <div class="permissions-bulk-actions">
      <button
        class="bulk-action-btn"
        :disabled="!hasFilteredSelectablePermissions"
        type="button"
        @click="selectAllFilteredPermissions"
      >
        <v-icon size="16">mdi-checkbox-multiple-marked-outline</v-icon>
        Tout selectionner (resultats)
      </button>
      <button
        class="bulk-action-btn bulk-action-btn--ghost"
        :disabled="!hasFilteredSelectedPermissions"
        type="button"
        @click="deselectAllFilteredPermissions"
      >
        <v-icon size="16">mdi-checkbox-multiple-blank-outline</v-icon>
        Tout deselectionner (resultats)
      </button>
    </div>

    <div v-if="hasRole" class="role-permissions-preview">
      <div class="role-preview-title">
        <v-icon color="primary" size="18">mdi-shield-account</v-icon>
        Permissions héritées du rôle sélectionné (lecture seule)
      </div>
      <div
        v-if="inheritedRolePermissions.length > 0"
        class="role-permissions-chips"
      >
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
    </div>

    <div class="permissions-grid">
      <div
        v-for="module in filteredPermissionGroups"
        :key="module.id"
        class="permission-module"
      >
        <div class="module-header">
          <div class="module-meta">
            <v-icon :color="module.color">{{ module.icon }}</v-icon>
            <span class="module-name">{{ module.name }}</span>
            <span class="module-counter">
              {{ moduleSelectedCount(module) }}/{{
                moduleSelectableCount(module)
              }}
            </span>
          </div>
          <div class="module-actions">
            <button
              class="module-action-btn"
              :disabled="
                moduleSelectableCount(module) === 0 ||
                  isModuleFullySelected(module)
              "
              type="button"
              @click="selectModulePermissions(module)"
            >
              Tout
            </button>
            <button
              class="module-action-btn module-action-btn--ghost"
              :disabled="moduleSelectedCount(module) === 0"
              type="button"
              @click="deselectModulePermissions(module)"
            >
              Aucun
            </button>
          </div>
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
              @change="
                togglePermission(
                  perm,
                  ($event.target as HTMLInputElement).checked,
                )
              "
            >
            <span>{{ formatPermissionLabel(perm) }}</span>
            <v-icon
              v-if="isInheritedPermission(perm)"
              color="primary"
              size="16"
            >mdi-account-lock</v-icon>
            <v-icon
              v-else-if="isDefaultReadPermission(perm)"
              color="success"
              size="16"
            >mdi-lock-open</v-icon>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed, ref } from 'vue'

  type PermissionGroup = {
    id: string
    name: string
    icon: string
    color: string
    permissions: string[]
  }

  const props = defineProps({
    permissionGroups: {
      type: Array as PropType<PermissionGroup[]>,
      required: true,
    },
    inheritedRolePermissions: {
      type: Array as PropType<string[]>,
      required: true,
    },
    directPermissionsCount: {
      type: Number,
      required: true,
    },
    effectivePermissionsCount: {
      type: Number,
      required: true,
    },
    activeScopedPermissionsCount: {
      type: Number,
      required: true,
    },
    hasRole: {
      type: Boolean,
      required: true,
    },
    formatPermissionLabel: {
      type: Function as PropType<(permission: string) => string>,
      required: true,
    },
    isPermissionChecked: {
      type: Function as PropType<(permission: string) => boolean>,
      required: true,
    },
    isPermissionLocked: {
      type: Function as PropType<(permission: string) => boolean>,
      required: true,
    },
    isInheritedPermission: {
      type: Function as PropType<(permission: string) => boolean>,
      required: true,
    },
    isDefaultReadPermission: {
      type: Function as PropType<(permission: string) => boolean>,
      required: true,
    },
    togglePermission: {
      type: Function as PropType<(permission: string, checked: boolean) => void>,
      required: true,
    },
  })

  const permissionSearch = ref('')

  function selectablePermissions (permissions: string[]): string[] {
    return permissions.filter(
      permission => !props.isPermissionLocked(permission),
    )
  }

  function selectedPermissions (permissions: string[]): string[] {
    return permissions.filter(permission =>
      props.isPermissionChecked(permission),
    )
  }

  function moduleSelectableCount (module: PermissionGroup): number {
    return selectablePermissions(module.permissions).length
  }

  function moduleSelectedCount (module: PermissionGroup): number {
    return selectedPermissions(selectablePermissions(module.permissions)).length
  }

  function isModuleFullySelected (module: PermissionGroup): boolean {
    const selectable = moduleSelectableCount(module)
    return selectable > 0 && moduleSelectedCount(module) === selectable
  }

  function applyBulkToggle (permissions: string[], checked: boolean): void {
    for (const permission of permissions) {
      props.togglePermission(permission, checked)
    }
  }

  function selectModulePermissions (module: PermissionGroup): void {
    applyBulkToggle(selectablePermissions(module.permissions), true)
  }

  function deselectModulePermissions (module: PermissionGroup): void {
    applyBulkToggle(selectablePermissions(module.permissions), false)
  }

  const filteredPermissionGroups = computed(() => {
    const query = permissionSearch.value.trim().toLowerCase()
    if (!query) {
      return props.permissionGroups
    }

    return props.permissionGroups
      .map(group => {
        const groupName = String(group.name || '').toLowerCase()
        const permissions = group.permissions.filter(permission => {
          const raw = String(permission || '').toLowerCase()
          const label = props.formatPermissionLabel(permission).toLowerCase()
          return (
            raw.includes(query)
            || label.includes(query)
            || groupName.includes(query)
          )
        })

        return {
          ...group,
          permissions,
        }
      })
      .filter(group => group.permissions.length > 0)
  })

  const filteredSelectablePermissions = computed(() => {
    return filteredPermissionGroups.value.flatMap(group =>
      selectablePermissions(group.permissions),
    )
  })

  const hasFilteredSelectablePermissions = computed(
    () => filteredSelectablePermissions.value.length > 0,
  )

  const hasFilteredSelectedPermissions = computed(() => {
    return filteredSelectablePermissions.value.some(permission =>
      props.isPermissionChecked(permission),
    )
  })

  function selectAllFilteredPermissions (): void {
    applyBulkToggle(filteredSelectablePermissions.value, true)
  }

  function deselectAllFilteredPermissions (): void {
    applyBulkToggle(filteredSelectablePermissions.value, false)
  }
</script>

<style scoped>
.permissions-section {
  margin-top: 16px;
}

.permissions-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: rgba(59, 130, 246, 0.1);
  border-radius: 12px;
  margin-bottom: 16px;
  font-size: 0.8125rem;
  color: #1e40af;
}

.permissions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.permissions-search {
  margin-bottom: 12px;
}

.permissions-search-input {
  width: 100%;
  border: 2px solid #dbe3ef;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 0.8125rem;
  outline: none;
  transition: border-color 0.2s ease;
}

.permissions-search-input:focus {
  border-color: #5b8dd9;
}

.permissions-bulk-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.bulk-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid #1d4ed8;
  background: #1d4ed8;
  color: #ffffff;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.bulk-action-btn--ghost {
  border-color: #cbd5e1;
  background: #ffffff;
  color: #1e293b;
}

.bulk-action-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.role-permissions-preview {
  border: 2px solid #dbeafe;
  border-radius: 12px;
  background: #f8fbff;
  padding: 12px;
  margin-bottom: 12px;
}

.role-preview-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 700;
  color: #1e3a8a;
  margin-bottom: 6px;
}

.role-permissions-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.role-permission-chip {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  border-radius: 999px;
  padding: 3px 8px;
  font-size: 0.7rem;
  font-weight: 600;
}

.role-permissions-empty {
  font-size: 0.8125rem;
  color: #475569;
}

.permission-module {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px;
}

.module-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #f1f5f9;
}

.module-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.module-name {
  font-weight: 700;
  font-size: 0.9rem;
  color: #1e293b;
}

.module-counter {
  font-size: 0.72rem;
  font-weight: 700;
  color: #475569;
  background: #eef2ff;
  border: 1px solid #c7d2fe;
  border-radius: 999px;
  padding: 2px 8px;
}

.module-actions {
  display: flex;
  gap: 6px;
}

.module-action-btn {
  border: 1px solid #5b8dd9;
  background: #5b8dd9;
  color: white;
  border-radius: 6px;
  padding: 3px 8px;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
}

.module-action-btn--ghost {
  border-color: #cbd5e1;
  background: white;
  color: #334155;
}

.module-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.module-permissions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.permission-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 0.8125rem;
}

.permission-checkbox input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.permission-checkbox input[type="checkbox"]:disabled {
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .permissions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
