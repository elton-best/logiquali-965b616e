<template>
  <ClientALayout current-page="users">
    <v-container fluid>
      <v-row>
        <v-col cols="12">
          <div class="d-flex align-center mb-6">
            <v-btn icon variant="text" @click="$router.back()">
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            <div class="ml-4">
              <h1 class="text-h4 font-weight-bold">Gestion des permissions</h1>
              <p class="text-body-2 text-medium-emphasis mb-0">
                {{ user?.name || 'Collaborateur' }}
              </p>
            </div>
          </div>
        </v-col>
      </v-row>

      <v-row v-if="loading">
        <v-col cols="12">
          <UnifiedLoader
            class="mx-auto"
            description="Chargement des droits de l'utilisateur..."
            title="Chargement des permissions..."
            variant="local"
          />
        </v-col>
      </v-row>

      <v-row v-else>
        <v-col cols="12">
          <v-card>
            <v-card-title class="d-flex align-center justify-space-between">
              <span>Permissions actives</span>
              <v-chip color="primary" size="small">
                {{ activePermissionsCount }} actives
              </v-chip>
            </v-card-title>

            <v-divider />

            <v-card-text>
              <v-alert class="mb-4" color="primary" icon="mdi-shield-check" variant="tonal">
                Permissions actives (site/souscription): <strong>{{ activePermissionsCount }}</strong>
              </v-alert>

              <v-expansion-panels class="mb-4" variant="accordion">
                <v-expansion-panel>
                  <v-expansion-panel-title>
                    Détails techniques (avancé)
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <div class="text-caption text-medium-emphasis mb-2">
                      Permissions de rôle: <strong>{{ rolePermissionsCount }}</strong> ·
                      Permissions directes personnalisées:
                      <strong>{{ directPermissionsCount }}</strong>
                    </div>
                    <div class="text-caption text-medium-emphasis mb-2">
                      Permissions effectives (avant contexte site/souscription):
                      <strong>{{ effectivePermissionsCount }}</strong>
                    </div>
                    <div class="text-caption text-medium-emphasis">
                      Les permissions directes sont désactivées en mode RBAC roles-only.
                    </div>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>

              <v-alert v-if="!hasSubscription" class="mb-4" type="warning" variant="tonal">
                Aucune souscription active. Les modules disponibles dépendent de votre offre.
              </v-alert>

              <div v-for="module in permissionModules" :key="module.module" class="mb-4">
                <v-card variant="outlined">
                  <v-card-title class="d-flex align-center bg-surface-variant">
                    <v-icon class="mr-3" :icon="getModuleIcon(module.module)" />
                    <span>{{ formatModuleName(module.module) }}</span>
                  </v-card-title>

                  <v-card-text>
                    <v-row>
                      <v-col
                        v-for="permission in module.permissions"
                        :key="permission.name"
                        cols="12"
                        md="4"
                        sm="6"
                      >
                        <v-checkbox
                          v-model="selectedPermissions"
                          color="primary"
                          density="compact"
                          disabled
                          hide-details
                          :label="formatPermissionLabel(permission.action)"
                          :value="permission.name"
                        >
                          <template #prepend>
                            <v-icon :icon="getActionIcon(permission.action)" size="20" />
                          </template>
                        </v-checkbox>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </div>

              <v-alert v-if="permissionModules.length === 0" type="info" variant="tonal">
                Aucun module disponible. Veuillez vérifier votre souscription.
              </v-alert>
            </v-card-text>

            <v-divider />

            <v-card-actions class="pa-4">
              <v-spacer />
              <v-btn variant="text" @click="$router.back()">
                Annuler
              </v-btn>
              <v-btn
                color="primary"
                disabled
                variant="elevated"
                @click="savePermissions"
              >
                Lecture seule
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { PermissionModule } from '@/services/permissionService'
  import { computed, onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import permissionService from '@/services/permissionService'

  const route = useRoute()
  const toast = useToast()

  const userId = computed(() => {
    const rawId = (route.params as Record<string, unknown>).id
    const value = Array.isArray(rawId) ? rawId[0] : rawId
    return Number(value)
  })
  const user = ref<any>(null)
  const loading = ref(true)
  const permissionModules = ref<PermissionModule[]>([])
  const selectedPermissions = ref<string[]>([])
  const activePermissionsCount = ref(0)
  const rolePermissionsCount = ref(0)
  const effectivePermissionsCount = ref(0)
  const directPermissionsCount = computed(() => selectedPermissions.value.length)
  const hasSubscription = ref(true)

  const moduleIcons: Record<string, string> = {
    sites: 'mdi-map-marker',
    site: 'mdi-map-marker',
    users: 'mdi-account-group',
    user: 'mdi-account-group',
    personnel: 'mdi-account-group',
    subscriptions: 'mdi-credit-card',
    documents: 'mdi-file-document',
    document: 'mdi-file-document',
    processes: 'mdi-sitemap',
    process: 'mdi-sitemap',
    risks: 'mdi-alert',
    risk: 'mdi-alert',
    audits: 'mdi-clipboard-check',
    audit: 'mdi-clipboard-check',
    nc: 'mdi-alert-circle',
    actions: 'mdi-check-circle',
    action: 'mdi-check-circle',
    indicators: 'mdi-chart-line',
    indicator: 'mdi-chart-line',
  }

  const actionIcons: Record<string, string> = {
    read: 'mdi-eye',
    create: 'mdi-plus',
    update: 'mdi-pencil',
    delete: 'mdi-delete',
    validate: 'mdi-check-circle',
    approve: 'mdi-check-decagram',
    assign: 'mdi-account-switch',
    verify: 'mdi-shield-check',
    export: 'mdi-download',
    publish: 'mdi-send',
  }

  function formatModuleName (moduleName: string): string {
    return moduleName
      .replace(/[_-]+/g, ' ')
      .replace(/\b\w/g, l => l.toUpperCase())
  }

  function formatPermissionLabel (action: string): string {
    const labels: Record<string, string> = {
      read: 'Lecture',
      create: 'Creation',
      update: 'Modification',
      delete: 'Suppression',
      validate: 'Validation',
      approve: 'Approbation',
      assign: 'Assignation',
      verify: 'Verification',
      export: 'Export',
      publish: 'Publication',
    }
    return labels[action] || formatModuleName(action)
  }

  function getModuleIcon (moduleName: string): string {
    return moduleIcons[moduleName] || 'mdi-shield-key'
  }

  function getActionIcon (action: string): string {
    return actionIcons[action] || 'mdi-key'
  }

  async function fetchData () {
    loading.value = true
    try {
      // Fetch user
      const userResponse = await api.get(`/users/${userId.value}`)
      user.value = userResponse.data?.data || userResponse.data

      // Fetch user permissions
      const permResponse = await permissionService.getUserPermissions(userId.value)
      selectedPermissions.value = Array.isArray(permResponse.direct_permissions)
        ? permResponse.direct_permissions
        : []
      rolePermissionsCount.value = Number(permResponse.role_permissions_count || 0)
      const effectiveCountFromApi = Number(permResponse.effective_permissions_count)
      if (Number.isFinite(effectiveCountFromApi) && effectiveCountFromApi >= 0) {
        effectivePermissionsCount.value = effectiveCountFromApi
      } else if (Array.isArray(permResponse.effective_permissions)) {
        effectivePermissionsCount.value = permResponse.effective_permissions.length
      } else {
        effectivePermissionsCount.value = 0
      }

      const activeCountFromApi = Number(permResponse.active_scoped_permissions_count)
      if (Number.isFinite(activeCountFromApi) && activeCountFromApi >= 0) {
        activePermissionsCount.value = activeCountFromApi
      } else if (Array.isArray(permResponse.active_scoped_permissions)) {
        activePermissionsCount.value = permResponse.active_scoped_permissions.length
      } else if (Array.isArray(permResponse.effective_permissions)) {
        activePermissionsCount.value = permResponse.effective_permissions.length
      } else {
        activePermissionsCount.value = 0
      }

      // Fetch available permissions catalog from backend (single source of truth)
      permissionModules.value = await permissionService.getAvailablePermissions()
      hasSubscription.value = permissionModules.value.length > 0
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors du chargement')
      hasSubscription.value = false
    } finally {
      loading.value = false
    }
  }

  async function savePermissions () {
    toast.info('Cette page est désormais en lecture seule (RBAC roles-only).')
  }

  onMounted(() => {
    fetchData()
  })
</script>
