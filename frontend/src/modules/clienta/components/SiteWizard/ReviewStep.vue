<template>
  <v-card flat>
    <v-card-text>
      <v-alert
        class="mb-6"
        prominent
        type="success"
        variant="tonal"
      >
        <template #prepend>
          <v-icon>mdi-check-circle</v-icon>
        </template>
        <v-alert-title>Récapitulatif de la création</v-alert-title>
        Vérifiez les informations ci-dessous avant de créer le site
      </v-alert>

      <v-row>
        <!-- Site Info Card -->
        <v-col cols="12" md="4">
          <v-card variant="tonal">
            <v-card-title class="d-flex align-center gap-2">
              <v-icon color="primary">mdi-office-building</v-icon>
              Informations du site
            </v-card-title>
            <v-divider />
            <v-card-text>
              <div class="d-flex flex-column gap-3">
                <div>
                  <div class="text-caption text-medium-emphasis">Nom</div>
                  <div class="font-weight-medium">{{ siteInfo.name }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Adresse</div>
                  <div class="font-weight-medium">{{ siteInfo.address }}</div>
                  <div class="text-body-2">{{ siteInfo.city }}<span v-if="siteInfo.country">, {{ siteInfo.country }}</span></div>
                </div>
                <div v-if="siteInfo.phone">
                  <div class="text-caption text-medium-emphasis">Téléphone</div>
                  <div class="font-weight-medium">{{ siteInfo.phone }}</div>
                </div>
                <div v-if="siteInfo.email">
                  <div class="text-caption text-medium-emphasis">Email</div>
                  <div class="font-weight-medium">{{ siteInfo.email }}</div>
                </div>
                <div class="d-flex gap-2">
                  <v-chip
                    v-if="siteInfo.is_headquarter"
                    color="primary"
                    size="small"
                    variant="flat"
                  >
                    <v-icon start>mdi-office-building-marker</v-icon>
                    Siège social
                  </v-chip>
                  <v-chip
                    :color="siteInfo.is_active ? 'success' : 'error'"
                    size="small"
                    variant="flat"
                  >
                    <v-icon start>{{ siteInfo.is_active ? 'mdi-check-circle' : 'mdi-close-circle' }}</v-icon>
                    {{ siteInfo.is_active ? 'Actif' : 'Inactif' }}
                  </v-chip>
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Manager Card -->
        <v-col cols="12" md="4">
          <v-card variant="tonal">
            <v-card-title class="d-flex align-center gap-2">
              <v-icon color="primary">mdi-account-tie</v-icon>
              Responsable de site
            </v-card-title>
            <v-divider />
            <v-card-text>
              <div v-if="managerData.manager_id" class="d-flex flex-column gap-3">
                <div>
                  <div class="text-caption text-medium-emphasis">Collaborateur existant</div>
                  <div class="font-weight-medium">
                    {{ getCollaboratorName(managerData.manager_id) }}
                  </div>
                </div>
                <v-alert
                  density="compact"
                  type="info"
                  variant="tonal"
                >
                  Ce collaborateur sera assigné comme responsable de site
                </v-alert>
              </div>

              <div v-else-if="managerData.new_manager.email" class="d-flex flex-column gap-3">
                <div>
                  <div class="text-caption text-medium-emphasis">Nouveau collaborateur</div>
                  <div class="font-weight-medium">
                    {{ managerData.new_manager.first_name }} {{ managerData.new_manager.last_name }}
                  </div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Email</div>
                  <div class="font-weight-medium">{{ managerData.new_manager.email }}</div>
                </div>
                <div v-if="managerData.new_manager.phone">
                  <div class="text-caption text-medium-emphasis">Téléphone</div>
                  <div class="font-weight-medium">{{ managerData.new_manager.phone }}</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Poste</div>
                  <div class="font-weight-medium">{{ managerData.new_manager.position }}</div>
                </div>
                <div v-if="managerData.new_manager.start_date">
                  <div class="text-caption text-medium-emphasis">Date de prise de service</div>
                  <div class="font-weight-medium">{{ managerData.new_manager.start_date }}</div>
                </div>
                <div v-if="managerData.new_manager.address">
                  <div class="text-caption text-medium-emphasis">Adresse</div>
                  <div class="font-weight-medium">{{ managerData.new_manager.address }}</div>
                </div>
                <v-alert
                  density="compact"
                  type="success"
                  variant="tonal"
                >
                  Un compte sera créé et une invitation sera envoyée
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>

        <!-- Permissions Card -->
        <v-col cols="12" md="4">
          <v-card variant="tonal">
            <v-card-title class="d-flex align-center gap-2">
              <v-icon color="primary">mdi-shield-key</v-icon>
              Permissions attribuées
            </v-card-title>
            <v-divider />
            <v-card-text>
              <div class="d-flex flex-column gap-3">
                <div class="d-flex align-center justify-space-between">
                  <div class="text-caption text-medium-emphasis">Permissions actives</div>
                  <v-chip color="primary" size="small">
                    {{ effectivePermissions.length }} permissions
                  </v-chip>
                </div>

                <div v-if="selectedRole" class="text-caption text-medium-emphasis">
                  Rôle sélectionné: <strong>{{ selectedRole }}</strong>
                </div>

                <div style="max-height: 300px; overflow-y: auto;">
                  <div
                    v-for="(groupPerms, module) in groupedPermissions"
                    :key="module"
                    class="mb-3"
                  >
                    <div class="text-caption text-medium-emphasis mb-1">
                      {{ getModuleLabel(module) }}
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                      <v-chip
                        v-for="permission in groupPerms"
                        :key="permission"
                        :color="getPermissionColor(permission)"
                        size="small"
                        variant="tonal"
                      >
                        {{ getActionLabel(permission) }}
                      </v-chip>
                    </div>
                  </div>
                </div>

                <v-alert
                  v-if="effectivePermissions.length === 0"
                  density="compact"
                  type="warning"
                  variant="tonal"
                >
                  Aucune permission effective (ni héritée, ni directe)
                </v-alert>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  interface SiteInfo {
    name: string
    address: string
    country?: string
    city: string
    phone?: string
    email?: string
    is_headquarter: boolean
    is_active: boolean
  }

  interface NewManager {
    first_name: string
    last_name: string
    email: string
    phone?: string
    position: string
    address?: string
    start_date?: string
  }

  interface ManagerData {
    manager_id: number | null
    new_manager: NewManager
  }

  interface Collaborator {
    id: number
    full_name: string
    email: string
    position: string
  }

  interface Props {
    siteInfo: SiteInfo
    managerData: ManagerData
    selectedRole?: string
    rolePermissions?: string[]
    permissions: string[]
    collaborators: Collaborator[]
  }

  const props = defineProps<Props>()

  const inheritedRolePermissions = computed(() => {
    return Array.from(new Set(props.rolePermissions || []))
  })

  const effectivePermissions = computed(() => {
    return Array.from(new Set([
      ...inheritedRolePermissions.value,
      ...props.permissions,
    ]))
  })

  function parsePermission (permission: string): { module: string, action: string } {
    const [module = 'unknown', action = 'read'] = permission.split('.')
    return { module, action }
  }

  const groupedPermissions = computed(() => {
    const groups: Record<string, string[]> = {}
    for (const permission of effectivePermissions.value) {
      const { module } = parsePermission(permission)
      if (!groups[module]) {
        groups[module] = []
      }
      groups[module].push(permission)
    }
    return groups
  })

  function getCollaboratorName (id: number) {
    const collaborator = props.collaborators.find(c => c.id === id)
    return collaborator?.full_name || 'Inconnu'
  }

  function getModuleLabel (module: string) {
    const moduleLabels: Record<string, string> = {
      documents: 'Documents',
      processes: 'Processus',
      risks: 'Risques & Opportunités',
      nc: 'Non-Conformités',
      actions: 'Actions',
      audits: 'Audits',
      indicators: 'Indicateurs',
      sites: 'Sites',
    }
    return moduleLabels[module] || module
  }

  function getActionLabel (permission: string) {
    const { action } = parsePermission(permission)
    const actionLabels: Record<string, string> = {
      read: 'Consulter',
      create: 'Créer',
      update: 'Modifier',
      delete: 'Supprimer',
      approve: 'Approuver',
      evaluate: 'Évaluer',
      treat: 'Traiter',
      assign: 'Assigner',
      conduct: 'Réaliser',
      manage_users: 'Gérer utilisateurs',
    }
    return actionLabels[action] || action
  }

  function getPermissionColor (permission: string) {
    const { module } = parsePermission(permission)
    const colors: Record<string, string> = {
      documents: 'blue',
      processes: 'purple',
      risks: 'orange',
      nc: 'red',
      actions: 'green',
      audits: 'indigo',
      indicators: 'teal',
      sites: 'primary',
    }
    return colors[module] || 'grey'
  }

</script>
