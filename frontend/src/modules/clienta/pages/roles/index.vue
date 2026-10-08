<template>
  <ClientALayout current-page="roles">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="mb-6">
        <div class="d-flex align-center justify-space-between">
          <div>
            <h1 class="text-h4 font-weight-bold mb-2">
              <v-icon class="mr-2">mdi-shield-account</v-icon>
              Rôles & Permissions
            </h1>
            <p class="text-body-2 text-medium-emphasis">
              Gérez les rôles et leurs permissions d'accès aux modules
            </p>
          </div>
          <v-btn
            v-if="canCreateRole"
            color="primary"
            prepend-icon="mdi-plus"
            size="large"
            @click="openCreateDialog"
          >
            Nouveau Rôle
          </v-btn>
        </div>
      </div>

      <!-- Roles Cards/Table -->
      <div v-if="loading" class="d-flex justify-center align-center py-12">
        <UnifiedLoader
          centered
          message="Chargement des rôles..."
          size="md"
          variant="spinner"
        />
      </div>

      <v-row v-else>
        <v-col
          v-for="role in roles"
          :key="role.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card class="role-card" @click="handleRoleCardClick(role)">
            <v-card-text>
              <div class="d-flex align-center justify-space-between mb-3">
                <v-avatar :color="role.color" size="48">
                  <v-icon color="white">{{ role.icon }}</v-icon>
                </v-avatar>
                <v-chip
                  :color="role.color"
                  size="small"
                  variant="tonal"
                >
                  {{ role.usersCount }} utilisateurs
                </v-chip>
              </div>

              <h3 class="text-h6 font-weight-bold mb-2">{{ role.name }}</h3>
              <p class="text-body-2 text-medium-emphasis mb-3">
                {{ role.description }}
              </p>

              <v-divider class="my-3" />

              <div class="permissions-summary">
                <div class="d-flex align-center justify-space-between mb-2">
                  <div class="text-caption text-medium-emphasis">Permissions actives par défaut</div>
                  <v-chip color="primary" size="x-small" variant="tonal">
                    {{ role.totalPermissions }}
                  </v-chip>
                </div>
                <div class="d-flex flex-wrap gap-1">
                  <v-chip
                    v-for="perm in role.activePermissionsPreview"
                    :key="perm"
                    color="primary"
                    size="x-small"
                    variant="outlined"
                  >
                    {{ perm }}
                  </v-chip>
                  <v-chip
                    v-if="role.totalPermissions > 3"
                    size="x-small"
                    variant="text"
                  >
                    +{{ role.totalPermissions - 3 }}
                  </v-chip>
                </div>
              </div>
            </v-card-text>

            <v-card-actions>
              <v-btn
                v-if="canUpdateRole"
                prepend-icon="mdi-pencil"
                size="small"
                variant="text"
                @click.stop="openPermissionsDialog(role)"
              >
                Modifier
              </v-btn>
              <v-spacer />
              <v-btn
                v-if="canDeleteRole && !role.isSystem"
                color="error"
                prepend-icon="mdi-delete"
                size="small"
                variant="text"
                @click.stop="deleteRole(role.id)"
              >
                Supprimer
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Permissions Matrix Dialog -->
      <v-dialog
        v-model="permissionsDialog"
        max-width="1200"
        scrollable
      >
        <v-card v-if="selectedRole">
          <v-card-title class="d-flex align-center justify-space-between bg-grey-lighten-4">
            <div class="d-flex align-center gap-3">
              <v-avatar :color="selectedRole.color" size="40">
                <v-icon color="white" size="20">{{ selectedRole.icon }}</v-icon>
              </v-avatar>
              <div>
                <div class="text-h6">{{ selectedRole.name }}</div>
                <div class="text-caption text-medium-emphasis">
                  {{ selectedRole.description }}
                </div>
              </div>
            </div>
            <v-btn
              icon="mdi-close"
              variant="text"
              @click="permissionsDialog = false"
            />
          </v-card-title>

          <v-divider />

          <v-card-text class="pa-6" style="max-height: 600px;">
            <div class="mb-4">
              <v-alert
                density="compact"
                icon="mdi-information"
                type="info"
                variant="tonal"
              >
                Définissez ici les permissions actives par défaut héritées automatiquement par les utilisateurs de ce rôle.
              </v-alert>
            </div>

            <!-- Permissions Hierarchy -->
            <v-expansion-panels multiple variant="accordion">
              <v-expansion-panel
                v-for="module in permissionModules"
                :key="module.key"
              >
                <v-expansion-panel-title class="bg-grey-lighten-4">
                  <div class="d-flex align-center w-100">
                    <v-icon :icon="module.icon" class="mr-3" color="primary" />
                    <span class="font-weight-bold text-subtitle-1">{{ module.name }}</span>
                    <v-spacer />
                    <v-checkbox
                      :model-value="isModuleChecked(module.key)"
                      @update:model-value="toggleModule(module.key, $event)"
                      color="success"
                      density="compact"
                      hide-details
                      label="Tout cocher"
                      @click.stop
                      class="mr-4 flex-grow-0"
                    />
                  </div>
                </v-expansion-panel-title>
                
                <v-expansion-panel-text class="pt-4">
                  <v-list bg-color="transparent" class="pa-0">
                    <template v-for="(resource, index) in module.resources" :key="resource.key">
                      <v-list-item class="px-0">
                        <div class="d-flex flex-column w-100 pb-3">
                          <div class="d-flex align-center mb-3">
                            <span class="font-weight-medium text-primary">{{ resource.name }}</span>
                            <v-spacer />
                            <v-checkbox
                              :model-value="isResourceChecked(module.key, resource.key)"
                              @update:model-value="toggleResource(module.key, resource.key, $event)"
                              color="primary"
                              density="compact"
                              hide-details
                              label="Tout"
                              class="flex-grow-0"
                            />
                          </div>
                          <div class="d-flex flex-wrap gap-4 pl-4">
                            <v-checkbox
                              v-for="action in resource.actions"
                              :key="action.name"
                              v-model="(selectedRole as any).permissionMatrix[module.key][resource.key][action.action]"
                              :label="mapPermissionLabel(action.name)"
                              color="primary"
                              density="compact"
                              hide-details
                            />
                          </div>
                        </div>
                      </v-list-item>
                      <v-divider v-if="index < module.resources.length - 1" class="my-2" />
                    </template>
                  </v-list>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </v-card-text>

          <v-divider />

          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn
              variant="text"
              @click="permissionsDialog = false"
            >
              Annuler
            </v-btn>
            <v-btn
              v-if="canUpdateRole"
              color="primary"
              prepend-icon="mdi-content-save"
              variant="flat"
              @click="savePermissions"
            >
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Create Role Dialog -->
      <v-dialog
        v-model="createDialog"
        max-width="600"
      >
        <v-card>
          <v-card-title class="bg-grey-lighten-4">
            <v-icon class="mr-2">mdi-plus-circle</v-icon>
            Créer un nouveau rôle
          </v-card-title>

          <v-divider />

          <v-card-text class="pa-6">
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="newRole.name"
                  density="comfortable"
                  label="Nom du rôle"
                  placeholder="Ex: Auditeur Senior"
                  prepend-inner-icon="mdi-shield-account"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="newRole.description"
                  label="Description"
                  placeholder="Décrivez les responsabilités de ce rôle..."
                  rows="3"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="newRole.color"
                  density="comfortable"
                  :items="colorOptions"
                  label="Couleur"
                  variant="outlined"
                >
                  <template #item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template #prepend>
                        <v-avatar :color="item.value" size="24" />
                      </template>
                    </v-list-item>
                  </template>
                  <template #selection="{ item }">
                    <div class="d-flex align-center gap-2">
                      <v-avatar :color="item.value" size="24" />
                      <span>{{ item.title }}</span>
                    </div>
                  </template>
                </v-select>
              </v-col>
              <v-col cols="12">
                <v-select
                  v-model="newRole.icon"
                  density="comfortable"
                  :items="iconOptions"
                  label="Icône"
                  variant="outlined"
                >
                  <template #item="{ props, item }">
                    <v-list-item v-bind="props">
                      <template #prepend>
                        <v-icon>{{ item.value }}</v-icon>
                      </template>
                    </v-list-item>
                  </template>
                  <template #selection="{ item }">
                    <div class="d-flex align-center gap-2">
                      <v-icon>{{ item.value }}</v-icon>
                      <span>{{ item.title }}</span>
                    </div>
                  </template>
                </v-select>
              </v-col>
            </v-row>
          </v-card-text>

          <v-divider />

          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn
              variant="text"
              @click="createDialog = false"
            >
              Annuler
            </v-btn>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              variant="flat"
              @click="createRole"
            >
              Créer le rôle
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import api from '@/api/client'
  import { computed, onMounted, ref } from 'vue'
  import { useToast } from 'vue-toastification'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import permissionService, { type PermissionModule } from '@/services/permissionService'
  import { useSiteContextStore } from '@/stores/siteContext'
  import { mapPermissionLabel } from '@/config/permissionLabels'
  import userService, { type Role } from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const toast = useToast()
  const authStore = useAuthStore()
  const siteContextStore = useSiteContextStore()
  const loading = ref(true)
  const permissionsDialog = ref(false)
  const createDialog = ref(false)
  interface UiRole extends Role {
    color: string
    icon: string
    usersCount: number
    isSystem: boolean
    totalPermissions: number
    activePermissionsPreview: string[]
    permissionMatrix: Record<string, Record<string, Record<string, boolean>>>
  }

  interface PermissionMatrixAction {
    name: string
    action: string
  }

  interface PermissionMatrixResource {
    key: string
    name: string
    actions: PermissionMatrixAction[]
  }
  const selectedRole = ref<UiRole | null>(null)
  const roles = ref<UiRole[]>([])

  interface PermissionMatrixModule {
    key: string
    name: string
    icon: string
    resources: PermissionMatrixResource[]
  }

  const permissionModules = ref<PermissionMatrixModule[]>([])
  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canCreateRole = computed(() => canAccess(['roles.create']))
  const canUpdateRole = computed(() => canAccess(['roles.update']))
  const canDeleteRole = computed(() => canAccess(['roles.delete']))
  const currentEnterpriseId = computed(() => Number(authStore.user?.enterprise?.id ?? 0))

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
    settings: 'mdi-cog',
  }



  const newRole = ref({
    name: '',
    description: '',
    color: 'primary',
    icon: 'mdi-account',
  })

  const colorOptions = [
    { title: 'Rouge', value: 'error' },
    { title: 'Bleu', value: 'primary' },
    { title: 'Vert', value: 'success' },
    { title: 'Orange', value: 'warning' },
    { title: 'Cyan', value: 'info' },
    { title: 'Violet', value: 'secondary' },
  ]

  const iconOptions = [
    { title: 'Compte', value: 'mdi-account' },
    { title: 'Étoile', value: 'mdi-account-star' },
    { title: 'Couronne', value: 'mdi-shield-crown' },
    { title: 'Presse-papiers', value: 'mdi-clipboard-account' },
    { title: 'Cravate', value: 'mdi-account-tie' },
    { title: 'Clé', value: 'mdi-key' },
  ]

  async function loadRoles () {
    loading.value = true
    try {
      const [rolesData, permissionCatalog] = await Promise.all([
        userService.getRoles(),
        permissionService.getActivePermissions(siteContextStore.activeSiteId ? Number(siteContextStore.activeSiteId) : undefined),
      ])

      permissionModules.value = buildPermissionMatrixModules(permissionCatalog)
      roles.value = rolesData.map(role => ({
        ...role,
        permissions: role.permissions || [],
        color: getColorForRole(role.name),
        icon: getIconForRole(role.name),
        usersCount: role.users_count || 0,
        isSystem: isSystemRole(role.name),
        totalPermissions: role.permissions?.length || 0,
        activePermissionsPreview: role.permissions?.slice(0, 3) || [],
        permissionMatrix: parsePermissions(role.permissions || []),
      }))
    } catch (error: any) {
      console.error('Error loading roles:', error)
      toast.error('Erreur lors du chargement des rôles')
    } finally {
      loading.value = false
    }
  }

  function getColorForRole (name: string): string {
    const lowerName = name.toLowerCase()
    if (lowerName.includes('admin')) return 'error'
    if (lowerName.includes('responsable') || lowerName.includes('qualité')) return 'primary'
    if (lowerName.includes('auditeur')) return 'info'
    if (lowerName.includes('documentaliste')) return 'warning'
    if (lowerName.includes('consultant')) return 'secondary'
    return 'success'
  }

  function getIconForRole (name: string): string {
    const lowerName = name.toLowerCase()
    if (lowerName.includes('admin')) return 'mdi-shield-crown'
    if (lowerName.includes('responsable')) return 'mdi-account-star'
    if (lowerName.includes('auditeur')) return 'mdi-clipboard-account'
    if (lowerName.includes('documentaliste')) return 'mdi-file-document-edit'
    if (lowerName.includes('consultant')) return 'mdi-account-tie'
    return 'mdi-account'
  }

  function isSystemRole (name: string): boolean {
    return name.toLowerCase().includes('admin')
  }

  function parsePermissions (permissions: string[]): Record<string, Record<string, Record<string, boolean>>> {
    const perms: Record<string, Record<string, Record<string, boolean>>> = {}
    for (const module of permissionModules.value) {
      perms[module.key] = {}
      for (const resource of module.resources) {
        perms[module.key][resource.key] = {}
        for (const action of resource.actions) {
          perms[module.key][resource.key][action.action] = permissions.includes(action.name)
        }
      }
    }
    return perms
  }

  function buildPermissionMatrixModules (catalog: PermissionModule[]): PermissionMatrixModule[] {
    return catalog
      .map(moduleData => {
        const resourcesMap = new Map<string, PermissionMatrixAction[]>()
        
        for (const p of moduleData.permissions) {
          const resourceKey = p.resource || 'general'
          if (!resourcesMap.has(resourceKey)) {
            resourcesMap.set(resourceKey, [])
          }
          resourcesMap.get(resourceKey)!.push({ name: p.name, action: p.action })
        }

        const resources: PermissionMatrixResource[] = Array.from(resourcesMap.entries()).map(([key, actions]) => {
          let name = key === 'general' ? 'Général' : key.replace(/[_\-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
          return {
            key,
            name,
            actions: actions.toSorted((a, b) => a.action.localeCompare(b.action)),
          }
        }).toSorted((a, b) => a.name.localeCompare(b.name))

        return {
          key: moduleData.module,
          name: moduleData.module
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, c => c.toUpperCase()),
          icon: MODULE_ICONS[moduleData.module] || 'mdi-shield-key',
          resources,
        }
      })
      .toSorted((a, b) => a.name.localeCompare(b.name))
  }

  function openPermissionsDialog (role: UiRole) {
    if (!canUpdateRole.value) return
    selectedRole.value = structuredClone(role)
    permissionsDialog.value = true
  }

  function handleRoleCardClick (role: UiRole) {
    if (!canUpdateRole.value) return
    openPermissionsDialog(role)
  }

  function openCreateDialog () {
    if (!canCreateRole.value) return
    newRole.value = {
      name: '',
      description: '',
      color: 'primary',
      icon: 'mdi-account',
    }
    createDialog.value = true
  }

  function buildSelectedPermissions (): string[] {
    if (!selectedRole.value) return []
    const selected: string[] = []
    const matrix = selectedRole.value.permissionMatrix
    for (const modKey in matrix) {
      for (const resKey in matrix[modKey]) {
        for (const actKey in matrix[modKey][resKey]) {
          if (matrix[modKey][resKey][actKey]) {
            const modObj = permissionModules.value.find(m => m.key === modKey)
            const resObj = modObj?.resources.find(r => r.key === resKey)
            const actObj = resObj?.actions.find(a => a.action === actKey)
            if (actObj) selected.push(actObj.name)
          }
        }
      }
    }
    return selected
  }

  async function createRole () {
    if (!canCreateRole.value) return

    const enterpriseId = currentEnterpriseId.value
    if (!enterpriseId) {
      toast.error('Impossible de déterminer l\'entreprise courante.')
      return
    }

    try {
      await api.post('/roles', {
        name: newRole.value.name.trim(),
        description: newRole.value.description.trim() || null,
        enterprise_id: enterpriseId,
        permissions: [],
      })
      toast.success('Rôle créé avec succès')
      createDialog.value = false
      await loadRoles()
    } catch (error: any) {
      console.error('Error creating role:', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la création du rôle')
    }
  }

  async function deleteRole (roleId: number) {
    if (!canDeleteRole.value) return

    try {
      await api.delete(`/roles/${roleId}`)
      toast.success('Rôle supprimé avec succès')
      await loadRoles()
    } catch (error: any) {
      console.error('Error deleting role:', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de la suppression du rôle')
    }
  }

  async function savePermissions () {
    if (!canUpdateRole.value || !selectedRole.value) return

    const enterpriseId = Number(selectedRole.value.enterprise_id ?? currentEnterpriseId.value)
    if (!enterpriseId) {
      toast.error('Impossible de déterminer l\'entreprise du rôle.')
      return
    }

    try {
      await api.put(`/roles/${selectedRole.value.id}`, {
        name: selectedRole.value.name,
        description: selectedRole.value.description ?? null,
        enterprise_id: enterpriseId,
        permissions: buildSelectedPermissions(),
      })
      toast.success('Permissions enregistrées avec succès')
      permissionsDialog.value = false
      await loadRoles()
    } catch (error: any) {
      console.error('Error saving role permissions:', error)
      toast.error(error?.response?.data?.message || 'Erreur lors de l’enregistrement des permissions')
    }
  }

  function isResourceChecked (moduleKey: string, resourceKey: string): boolean {
    const matrix = selectedRole.value?.permissionMatrix
    if (!matrix?.[moduleKey]?.[resourceKey]) return false
    const actions = matrix[moduleKey][resourceKey]
    return Object.values(actions).every(val => val === true)
  }

  function toggleResource (moduleKey: string, resourceKey: string, value: boolean | null) {
    const matrix = selectedRole.value?.permissionMatrix
    if (!matrix?.[moduleKey]?.[resourceKey]) return
    const actions = matrix[moduleKey][resourceKey]
    for (const action in actions) {
      actions[action] = !!value
    }
  }

  function isModuleChecked (moduleKey: string): boolean {
    const matrix = selectedRole.value?.permissionMatrix
    if (!matrix?.[moduleKey]) return false
    return Object.values(matrix[moduleKey]).every(resourceActions => 
      Object.values(resourceActions).every(val => val === true)
    )
  }

  function toggleModule (moduleKey: string, value: boolean | null) {
    const matrix = selectedRole.value?.permissionMatrix
    if (!matrix?.[moduleKey]) return
    for (const resourceKey in matrix[moduleKey]) {
      for (const action in matrix[moduleKey][resourceKey]) {
        matrix[moduleKey][resourceKey][action] = !!value
      }
    }
  }

  onMounted(() => {
    loadRoles()
  })
</script>

<style scoped>
.role-card {
  cursor: pointer;
  transition: all 0.2s;
}

.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.permissions-matrix {
  border: 1px solid rgba(0, 0, 0, 0.12);
}

.permissions-matrix th {
  background-color: rgb(var(--v-theme-surface-variant));
  position: sticky;
  top: 0;
  z-index: 1;
}

.permission-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.permissions-summary {
  min-height: 60px;
}
</style>
