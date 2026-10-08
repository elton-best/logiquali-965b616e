<template>
  <div class="user-permissions-manager">
    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h3 class="text-h6 mb-2">Permissions directes</h3>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Permissions spécifiques assignées directement à cet utilisateur (en plus de ses rôles).
        </p>
      </div>
      <div class="d-flex gap-2">
        <v-chip color="info" size="small" variant="tonal">
          {{ rolePermissionsCount }} via rôles
        </v-chip>
        <v-chip color="primary" size="small" variant="tonal">
          {{ directPermissionsCount }} directes actives
        </v-chip>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="d-flex justify-center py-8">
      <v-progress-circular indeterminate />
    </div>

    <!-- Error state -->
    <v-alert v-else-if="error" class="mb-6" type="error" variant="tonal">
      {{ error }}
      <v-btn class="ml-4" size="small" @click="loadPermissions">Réessayer</v-btn>
    </v-alert>

    <div v-else>
      <!-- Mode Édition (Matrice des permissions) -->
      <div v-if="editMode">
        <v-alert class="mb-4" type="info" variant="tonal" density="compact">
          Cochez les permissions que vous souhaitez assigner directement à l'utilisateur. Seules les permissions liées aux modules souscrits sont disponibles.
        </v-alert>

        <v-expansion-panels multiple variant="accordion" class="mb-6">
          <v-expansion-panel
            v-for="module in matrixModules"
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
                          v-model="permissionMatrix[module.key][resource.key][action.action]"
                          :label="formatPermissionLabel(action.name)"
                          color="primary"
                          density="compact"
                          hide-details
                          :disabled="hasRolePermission(action.name)"
                          :hint="hasRolePermission(action.name) ? 'Déjà inclus dans un rôle' : ''"
                          persistent-hint
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

        <div class="d-flex justify-end">
          <v-btn
            color="primary"
            prepend-icon="mdi-content-save"
            :loading="saving"
            @click="saveDirectPermissions"
          >
            Enregistrer les permissions directes
          </v-btn>
        </div>
      </div>

      <!-- Mode Lecture (Chips) -->
      <div v-else>
        <div class="permission-modules">
          <div
            v-for="module in matrixModules"
            :key="module.key"
            v-show="countModulePermissions(module.key) > 0"
            class="mb-6"
          >
            <v-card variant="outlined">
              <v-card-title class="d-flex align-center bg-surface-variant pa-4">
                <v-icon class="mr-3" :icon="module.icon" size="24" />
                <span class="text-h6">{{ module.name }}</span>
                <v-spacer />
                <v-chip color="primary" size="small" variant="tonal">
                  {{ countModulePermissions(module.key) }} permissions directes
                </v-chip>
              </v-card-title>

              <v-card-text class="pa-4">
                <v-row>
                  <template v-for="resource in module.resources" :key="resource.key">
                    <template v-for="action in resource.actions" :key="action.name">
                      <v-col
                        v-if="hasDirectPermission(action.name)"
                        cols="12"
                        lg="4"
                        md="6"
                      >
                        <v-chip
                          color="primary"
                          size="medium"
                          variant="elevated"
                        >
                          <v-icon
                            :icon="getActionIcon(action.action)"
                            size="18"
                            start
                          />
                          {{ formatPermissionLabel(action.name) }}
                        </v-chip>
                      </v-col>
                    </template>
                  </template>
                </v-row>
              </v-card-text>
            </v-card>
          </div>
        </div>

        <v-alert v-if="directPermissionsCount === 0" type="info" variant="tonal">
          Cet utilisateur n'a aucune permission directe assignée. Passez en mode édition pour en ajouter.
        </v-alert>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useToast } from 'vue-toastification'
  import permissionService, { type PermissionModule as ApiPermissionModule } from '@/services/permissionService'
  import { mapPermissionLabel } from '@/config/permissionLabels'
  import { useSiteContextStore } from '@/stores/siteContext'

  const props = defineProps<{
    userId: number
    editMode: boolean
    userDirectPermissions?: string[]
    userRolePermissions?: string[]
    user?: any
  }>()

  const emit = defineEmits(['updated'])

  const toast = useToast()
  const siteContextStore = useSiteContextStore()

  const loading = ref(true)
  const saving = ref(false)
  const error = ref<string | null>(null)

  const directPermissions = ref<string[]>(props.userDirectPermissions || [])
  const rolePermissions = ref<string[]>(props.userRolePermissions || [])

  const rolePermissionsCount = computed(() => rolePermissions.value.length)
  const directPermissionsCount = computed(() => directPermissions.value.length)

  interface PermissionMatrixAction {
    name: string
    action: string
  }

  interface PermissionMatrixResource {
    key: string
    name: string
    actions: PermissionMatrixAction[]
  }

  interface PermissionMatrixModule {
    key: string
    name: string
    icon: string
    resources: PermissionMatrixResource[]
  }

  const matrixModules = ref<PermissionMatrixModule[]>([])
  const permissionMatrix = ref<Record<string, Record<string, Record<string, boolean>>>>({})

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

  watch(() => props.userDirectPermissions, (newVal) => {
    directPermissions.value = newVal || []
    updateMatrixFromDirectPermissions()
  })

  watch(() => props.userRolePermissions, (newVal) => {
    rolePermissions.value = newVal || []
  })

  function hasDirectPermission (permissionName: string): boolean {
    return directPermissions.value.includes(permissionName)
  }

  function hasRolePermission (permissionName: string): boolean {
    return rolePermissions.value.includes(permissionName)
  }

  function countModulePermissions (moduleKey: string): number {
    const module = matrixModules.value.find(m => m.key === moduleKey)
    if (!module) return 0
    let count = 0
    for (const res of module.resources) {
      for (const act of res.actions) {
        if (hasDirectPermission(act.name)) count++
      }
    }
    return count
  }

  function getActionIcon (action: string): string {
    const icons: Record<string, string> = {
      read: 'mdi-eye-outline',
      create: 'mdi-plus-circle-outline',
      update: 'mdi-pencil-outline',
      delete: 'mdi-delete-outline',
      import: 'mdi-import',
      export: 'mdi-export',
      verify: 'mdi-check-circle-outline',
      approve: 'mdi-seal-outline',
      view_all: 'mdi-eye-multiple-outline',
      manage_holidays: 'mdi-calendar-blank-outline',
    }
    return icons[action] || 'mdi-shield-outline'
  }

  async function loadPermissions () {
    loading.value = true
    error.value = null
    try {
      // Load active permissions for this site
      const activeSiteId = siteContextStore.activeSiteId ? Number(siteContextStore.activeSiteId) : undefined
      const catalog = await permissionService.getActivePermissions(activeSiteId)
      
      matrixModules.value = buildPermissionMatrixModules(catalog)
      permissionMatrix.value = buildInitialMatrix()
      updateMatrixFromDirectPermissions()
    } catch (err: any) {
      console.error('Error loading permissions:', err)
      error.value = 'Erreur lors du chargement des permissions disponibles'
    } finally {
      loading.value = false
    }
  }

  function buildPermissionMatrixModules (catalog: ApiPermissionModule[]): PermissionMatrixModule[] {
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

  function buildInitialMatrix () {
    const perms: Record<string, Record<string, Record<string, boolean>>> = {}
    for (const module of matrixModules.value) {
      perms[module.key] = {}
      for (const resource of module.resources) {
        perms[module.key][resource.key] = {}
        for (const action of resource.actions) {
          perms[module.key][resource.key][action.action] = false
        }
      }
    }
    return perms
  }

  function updateMatrixFromDirectPermissions () {
    if (!matrixModules.value.length) return
    const matrix = permissionMatrix.value
    for (const mod of matrixModules.value) {
      for (const res of mod.resources) {
        for (const act of res.actions) {
          if (matrix[mod.key]?.[res.key]) {
            matrix[mod.key][res.key][act.action] = hasDirectPermission(act.name)
          }
        }
      }
    }
  }

  function buildSelectedPermissions (): string[] {
    const selected: string[] = []
    const matrix = permissionMatrix.value
    for (const modKey in matrix) {
      for (const resKey in matrix[modKey]) {
        for (const actKey in matrix[modKey][resKey]) {
          if (matrix[modKey][resKey][actKey]) {
            const modObj = matrixModules.value.find(m => m.key === modKey)
            const resObj = modObj?.resources.find(r => r.key === resKey)
            const actObj = resObj?.actions.find(a => a.action === actKey)
            if (actObj) selected.push(actObj.name)
          }
        }
      }
    }
    return selected
  }

  async function saveDirectPermissions () {
    saving.value = true
    try {
      const selected = buildSelectedPermissions()
      await permissionService.assignCustomPermissions(props.userId, selected)
      toast.success('Permissions directes enregistrées avec succès')
      directPermissions.value = selected
      emit('updated')
    } catch (err: any) {
      console.error('Error saving direct permissions:', err)
      toast.error(err.response?.data?.message || 'Erreur lors de l\'enregistrement')
    } finally {
      saving.value = false
    }
  }

  function formatPermissionLabel (actionOrName: string): string {
    return mapPermissionLabel(actionOrName)
  }

  // --- Helpers for Checkboxes ---
  function isResourceChecked (moduleKey: string, resourceKey: string): boolean {
    const matrix = permissionMatrix.value
    if (!matrix?.[moduleKey]?.[resourceKey]) return false
    const actions = matrix[moduleKey][resourceKey]
    return Object.values(actions).every(val => val === true)
  }

  function toggleResource (moduleKey: string, resourceKey: string, value: boolean | null) {
    const matrix = permissionMatrix.value
    if (!matrix?.[moduleKey]?.[resourceKey]) return
    const actions = matrix[moduleKey][resourceKey]
    for (const action in actions) {
      // Don't override if user already has it via role
      // But actually, we don't strictly prevent it, it's just visually hinted.
      actions[action] = !!value
    }
  }

  function isModuleChecked (moduleKey: string): boolean {
    const matrix = permissionMatrix.value
    if (!matrix?.[moduleKey]) return false
    return Object.values(matrix[moduleKey]).every(resourceActions => 
      Object.values(resourceActions).every(val => val === true)
    )
  }

  function toggleModule (moduleKey: string, value: boolean | null) {
    const matrix = permissionMatrix.value
    if (!matrix?.[moduleKey]) return
    for (const resourceKey in matrix[moduleKey]) {
      for (const action in matrix[moduleKey][resourceKey]) {
        matrix[moduleKey][resourceKey][action] = !!value
      }
    }
  }

  onMounted(() => {
    loadPermissions()
  })
</script>

<style scoped>
.v-card {
  transition: all 0.2s ease;
}

.v-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
