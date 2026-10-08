<template>
  <v-container class="pa-6" fluid>
    <!-- Loading state -->
    <div v-if="loading" class="d-flex justify-center align-center" style="min-height: 320px;">
      <UnifiedLoader
        centered
        message="Chargement du collaborateur..."
        size="md"
        variant="spinner"
      />
    </div>

    <!-- Content -->
    <div v-else-if="user">
      <!-- Header -->
      <div class="d-flex align-center mb-6">
        <v-btn class="mr-3" icon="mdi-arrow-left" variant="text" @click="goBack" />
        <div class="flex-grow-1">
          <v-breadcrumbs class="pa-0" density="compact" :items="breadcrumbs">
            <template #divider>
              <v-icon icon="mdi-chevron-right" size="small" />
            </template>
          </v-breadcrumbs>
          <h1 class="text-h4 font-weight-bold mt-2">{{ user.name }}</h1>
        </div>
        <v-btn
          v-if="!editMode"
          color="primary"
          prepend-icon="mdi-pencil"
          @click="editMode = true"
        >
          Modifier
        </v-btn>
        <div v-else class="d-flex gap-2">
          <v-btn
            variant="outlined"
            @click="cancelEdit"
          >
            Annuler
          </v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            prepend-icon="mdi-content-save"
            @click="saveChanges"
          >
            Enregistrer
          </v-btn>
        </div>
      </div>

      <!-- User Profile Card -->
      <v-card class="mb-6 user-page-loader-scope">
        <div v-if="saving || deleting" class="user-page-loader-overlay">
          <UnifiedLoader
            centered
            :message="deleting ? 'Suppression en cours...' : 'Enregistrement en cours...'"
            size="sm"
            variant="spinner"
          />
        </div>
        <v-card-text class="pa-6">
          <div class="d-flex align-center">
            <v-avatar class="mr-6" :color="getAvatarColor(user.id)" size="80">
              <span class="text-h4 text-white font-weight-bold">{{ getUserInitials(user.name) }}</span>
            </v-avatar>
            <div class="flex-grow-1">
              <div class="d-flex align-center gap-2 mb-2">
                <h2 class="text-h5 font-weight-bold">{{ user.name }}</h2>
                <v-chip
                  :color="user.is_active ? 'success' : 'grey'"
                  size="small"
                >
                  <v-icon :icon="user.is_active ? 'mdi-check-circle' : 'mdi-close-circle'" size="16" start />
                  {{ user.is_active ? 'Actif' : 'Inactif' }}
                </v-chip>
                <v-chip :color="getRoleBadgeColor(user.user_type)" size="small">
                  {{ getRoleLabel(user.user_type) }}
                </v-chip>
              </div>
              <div class="text-body-1 text-medium-emphasis mb-1">
                <v-icon class="mr-2" size="16">mdi-email</v-icon>
                {{ user.email }}
              </div>
              <div v-if="user.phone" class="text-body-1 text-medium-emphasis mb-1">
                <v-icon class="mr-2" size="16">mdi-phone</v-icon>
                {{ user.phone }}
              </div>
              <div v-if="user.site" class="text-body-1 text-medium-emphasis">
                <v-icon class="mr-2" size="16">mdi-map-marker</v-icon>
                {{ user.site.name }}
              </div>
            </div>
            <div class="d-flex flex-column gap-2">
              <v-btn
                :color="user.is_active ? 'warning' : 'success'"
                prepend-icon="mdi-account-switch"
                variant="outlined"
                @click="toggleUserStatus"
              >
                {{ user.is_active ? 'Désactiver' : 'Activer' }}
              </v-btn>
              <v-btn
                color="error"
                :disabled="isCurrentUser"
                prepend-icon="mdi-delete"
                variant="outlined"
                @click="confirmDelete"
              >
                Supprimer
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- Tabs -->
      <v-card>
        <v-tabs v-model="tab" bg-color="transparent">
          <v-tab value="info">
            <v-icon start>mdi-information</v-icon>
            Informations
          </v-tab>
          <v-tab value="roles">
            <v-icon start>mdi-shield-account</v-icon>
            Rôles & Permissions
          </v-tab>
          <v-tab value="direct-permissions">
            <v-icon start>mdi-lock-outline</v-icon>
            Permissions directes
          </v-tab>
          <v-tab value="activity">
            <v-icon start>mdi-history</v-icon>
            Activité
          </v-tab>
        </v-tabs>

        <v-divider />

        <v-window v-model="tab">
          <!-- Tab 1: Informations -->
          <v-window-item value="info">
            <v-card-text class="pa-6">
              <v-form ref="formRef">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedUser.name"
                      density="comfortable"
                      label="Nom complet"
                      prepend-inner-icon="mdi-account"
                      :readonly="!editMode"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedUser.username"
                      density="comfortable"
                      label="Nom d'utilisateur"
                      prepend-inner-icon="mdi-account-circle"
                      :readonly="!editMode"
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedUser.email"
                      density="comfortable"
                      label="Email"
                      prepend-inner-icon="mdi-email"
                      :readonly="!editMode"
                      :rules="[rules.required, rules.email]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedUser.phone"
                      density="comfortable"
                      label="Téléphone"
                      prepend-inner-icon="mdi-phone"
                      :readonly="!editMode"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="editedUser.site_id"
                      clearable
                      density="comfortable"
                      item-title="name"
                      item-value="id"
                      :items="sites"
                      label="Site"
                      prepend-inner-icon="mdi-office-building"
                      :readonly="!editMode"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="editedUser.user_type"
                      density="comfortable"
                      :disabled="isCurrentUser"
                      item-title="title"
                      item-value="value"
                      :items="userTypeOptions"
                      label="Type d'utilisateur"
                      prepend-inner-icon="mdi-account-badge"
                      :readonly="!editMode"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col v-if="editMode" cols="12">
                    <v-divider class="mb-4" />
                    <h3 class="text-h6 mb-4">Changer le mot de passe (optionnel)</h3>
                  </v-col>

                  <v-col v-if="editMode" cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.password"
                      density="comfortable"
                      hint="Laisser vide pour ne pas modifier"
                      label="Nouveau mot de passe"
                      persistent-hint
                      prepend-inner-icon="mdi-lock"
                      :rules="passwordData.password ? [rules.password] : []"
                      type="password"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col v-if="editMode" cols="12" md="6">
                    <v-text-field
                      v-model="passwordData.password_confirmation"
                      density="comfortable"
                      label="Confirmer le mot de passe"
                      prepend-inner-icon="mdi-lock-check"
                      :rules="passwordData.password ? [rules.passwordMatch] : []"
                      type="password"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
          </v-window-item>

          <!-- Tab 2: Rôles & Permissions -->
          <v-window-item value="roles">
            <v-card-text class="pa-6">
              <div class="mb-6">
                <h3 class="text-h6 mb-4">Rôles assignés</h3>
                <v-select
                  v-model="selectedRoles"
                  chips
                  closable-chips
                  density="comfortable"
                  item-title="name"
                  item-value="id"
                  :items="roles"
                  label="Rôles"
                  multiple
                  prepend-inner-icon="mdi-shield-account"
                  :readonly="!editMode"
                  variant="outlined"
                >
                  <template #chip="{ item, props }">
                    <v-chip v-bind="props" color="primary" size="small" class="d-flex align-center">
                      <span class="mr-2">{{ item.title }}</span>
                      <v-icon class="ml-2" :icon="'mdi-information-outline'" size="16" @click.stop="openRoleModal(item.raw)" style="cursor:pointer"></v-icon>
                    </v-chip>
                  </template>
                </v-select>
              </div>

              <v-divider class="my-6" />

              <div>
                <div class="d-flex align-center justify-space-between mb-4">
                  <h3 class="text-h6 mb-0">Permissions actives</h3>
                  <v-chip color="primary" size="small" variant="tonal">
                    {{ activePermissionsCount }} actives
                  </v-chip>
                </div>
                <v-alert class="mb-4" type="info" variant="tonal">
                  Permissions actives actuellement appliquées à cet utilisateur sur le site courant et les souscriptions actives.
                </v-alert>

                <v-expansion-panels class="mb-4" variant="accordion">
                  <v-expansion-panel>
                    <v-expansion-panel-title>
                      Détails techniques (avancé)
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <div class="text-caption text-medium-emphasis">
                        Permissions de rôle: <strong>{{ rolePermissionsCount }}</strong> ·
                        Permissions directes: <strong>{{ directPermissionsCount }}</strong> ·
                        Permissions effectives: <strong>{{ effectivePermissionsCount }}</strong>
                      </div>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>

                <div v-if="activePermissions.length > 0" class="d-flex flex-wrap gap-2">
                  <v-chip
                    v-for="permission in activePermissions"
                    :key="permission"
                    color="primary"
                    size="small"
                    variant="outlined"
                  >
                    {{ permission }}
                  </v-chip>
                </div>
                <div v-else class="text-medium-emphasis">
                  Aucune permission assignée
                </div>
              </div>
            </v-card-text>
          </v-window-item>

          <!-- Tab 3: Permissions directes -->
          <v-window-item value="direct-permissions">
            <v-card-text class="pa-6">
              <UserPermissionsManager
                :edit-mode="editMode"
                :user="user"
                :user-direct-permissions="user.permissions || []"
                :user-id="user.id"
                :user-role-permissions="rolePermissions"
                @updated="loadUser"
              />
            </v-card-text>
          </v-window-item>

          <!-- Tab 4: Activité -->
          <v-window-item value="activity">
            <v-card-text class="pa-6">
              <v-timeline density="compact" side="end">
                <v-timeline-item
                  dot-color="success"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ formatDate(user.created_at) }}</div>
                  </template>
                  <div>
                    <div class="font-weight-medium">Compte créé</div>
                    <div class="text-caption text-medium-emphasis">
                      L'utilisateur a été créé dans le système
                    </div>
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  v-if="user.email_verified_at"
                  dot-color="info"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ formatDate(user.email_verified_at) }}</div>
                  </template>
                  <div>
                    <div class="font-weight-medium">Email vérifié</div>
                    <div class="text-caption text-medium-emphasis">
                      L'adresse email a été vérifiée
                    </div>
                  </div>
                </v-timeline-item>

                <v-timeline-item
                  dot-color="primary"
                  size="small"
                >
                  <template #opposite>
                    <div class="text-caption">{{ formatDate(user.updated_at) }}</div>
                  </template>
                  <div>
                    <div class="font-weight-medium">Dernière modification</div>
                    <div class="text-caption text-medium-emphasis">
                      Le profil a été modifié
                    </div>
                  </div>
                </v-timeline-item>
              </v-timeline>
            </v-card-text>
          </v-window-item>
        </v-window>
      </v-card>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="500">
        <v-card>
          <v-card-title class="text-h5">Confirmer la suppression</v-card-title>
          <v-card-text>
            Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ user.name }}</strong> ?
            Cette action est irréversible.
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
            <v-btn color="error" :loading="deleting" variant="flat" @click="deleteUser">
              Supprimer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Role details modal -->
      <v-dialog v-model="showRoleModal" max-width="720">
        <v-card>
          <v-card-title class="text-h5">Détails du rôle</v-card-title>
          <v-card-text>
            <RoleDescription :role="selectedRole" :current-permissions="activePermissions" />
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="showRoleModal = false">Fermer</v-btn>
            <v-btn color="primary" @click="openPermissionsDiff(selectedRole?.permissions || [], activePermissions)">Voir différences</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Permissions diff modal -->
      <PermissionsDiffModal v-if="showDiffModal" :base="diffLeft" :compare="diffRight" @close="showDiffModal = false" />
    </div>

    <!-- Error state -->
    <div v-else class="text-center py-12">
      <v-icon color="error" size="64">mdi-alert-circle</v-icon>
      <div class="text-h6 mt-4 mb-2">Utilisateur introuvable</div>
      <v-btn color="primary" @click="goBack">Retour à la liste</v-btn>
    </div>
  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { STORAGE_KEYS } from '@/config/constants'
  import UserPermissionsManager from '@/modules/clienta/components/UserPermissionsManager.vue'
  import RoleDescription from '@/modules/clienta/components/RoleDescription.vue'
  import PermissionsDiffModal from '@/modules/clienta/components/PermissionsDiffModal.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import siteService from '@/services/siteService'
  import userService, { type Role, type UpdateUserRequest, type User } from '@/services/userService'
  import { storage } from '@/utils/storage'

  const router = useRouter()
  const route = useRoute()
  const toast = useToast()
  type RoleDetail = Role & {
    description?: string
    permissions?: string[]
    title?: string
  }

  const selectedRole = ref<RoleDetail | null>(null)
  const showRoleModal = ref(false)
  const showDiffModal = ref(false)
  const diffLeft = ref<string[]>([])
  const diffRight = ref<string[]>([])

  function openRoleModal (role: RoleDetail) {
    selectedRole.value = role
    showRoleModal.value = true
  }

  function openPermissionsDiff (base: string[], compare: string[]) {
    diffLeft.value = base || []
    diffRight.value = compare || []
    showDiffModal.value = true
  }

  const loading = ref(true)
  const saving = ref(false)
  const deleting = ref(false)
  const editMode = ref(false)
  const deleteDialog = ref(false)
  const tab = ref('info')

  const userId = computed<number | null>(() => {
    const params = route.params as Record<string, unknown>
    const rawParam = params.id
    const rawId = Array.isArray(rawParam) ? rawParam[0] : rawParam
    const id = Number(rawId)
    return Number.isFinite(id) && id > 0 ? id : null
  })

  const user = ref<User | null>(null)
  const editedUser = ref<Partial<User>>({})
  const sites = ref<Array<{ id: number, name: string }>>([])
  const roles = ref<Role[]>([])
  const selectedRoles = ref<number[]>([])
  const formRef = ref()

  const passwordData = ref({
    password: '',
    password_confirmation: '',
  })

  const breadcrumbs = computed(() => [
    { title: 'Collaborateurs', disabled: false, to: '/company/users' },
    { title: user.value?.name || 'Détails', disabled: true },
  ])

  const userTypeOptions = [
    { title: 'Utilisateur', value: 'user' },
    { title: 'Collaborateur', value: 'collaborator' },
  ]

  const isCurrentUser = computed(() => {
    const currentUserData = storage.get<{ id?: number }>(STORAGE_KEYS.USER)
    return Boolean(currentUserData?.id && user.value?.id && currentUserData.id === user.value.id)
  })

  function normalizePermissionList (permissions: unknown): string[] {
    if (!Array.isArray(permissions)) return []
    return permissions
      .map((permission: any) => {
        if (typeof permission === 'string') return permission
        return permission?.name || permission?.slug || permission?.label || ''
      })
      .filter((permission: string) => typeof permission === 'string' && permission.length > 0)
  }

  const effectivePermissions = computed<string[]>(() => {
    const normalized = normalizePermissionList(user.value?.effective_permissions)
    if (normalized.length > 0) return normalized
    return normalizePermissionList(user.value?.permissions)
  })

  const directPermissionsCount = computed(() => {
    return normalizePermissionList(user.value?.permissions).length
  })

  const rolePermissionsCount = computed(() => {
    const fromRolePermissions = normalizePermissionList((user.value as any)?.role_permissions)
    if (fromRolePermissions.length > 0) {
      return fromRolePermissions.length
    }

    const rolesCollection = Array.isArray((user.value as any)?.roles) ? (user.value as any).roles : []
    const fromRoles = rolesCollection.flatMap((role: any) => {
      if (Array.isArray(role?.permissions)) return normalizePermissionList(role.permissions)
      if (Array.isArray(role?.relationships?.permissions)) return normalizePermissionList(role.relationships.permissions)
      return []
    })

    return fromRoles.length
  })

  const effectivePermissionsCount = computed(() => effectivePermissions.value.length)

  const rolePermissions = computed<string[]>(() => {
    const normalized = normalizePermissionList((user.value as any)?.role_permissions)
    if (normalized.length > 0) return normalized

    const rolesCollection = Array.isArray((user.value as any)?.roles) ? (user.value as any).roles : []
    return rolesCollection.flatMap((role: any) => {
      if (Array.isArray(role?.permissions)) return normalizePermissionList(role.permissions)
      if (Array.isArray(role?.relationships?.permissions)) return normalizePermissionList(role.relationships.permissions)
      return []
    })
  })

  const activePermissions = computed<string[]>(() => {
    const normalized = normalizePermissionList(user.value?.active_scoped_permissions)
    if (normalized.length > 0) return normalized
    return effectivePermissions.value
  })

  const activePermissionsCount = computed(() => {
    const countFromApi = Number((user.value as any)?.active_scoped_permissions_count)
    if (Number.isFinite(countFromApi) && countFromApi >= 0) {
      return countFromApi
    }
    return activePermissions.value.length
  })

  const rules = {
    required: (value: any) => !!value || 'Ce champ est requis',
    email: (value: string) => {
      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return pattern.test(value) || 'Email invalide'
    },
    password: (value: string) => {
      return value.length >= 8 || 'Le mot de passe doit contenir au moins 8 caractères'
    },
    passwordMatch: (value: string) => {
      return value === passwordData.value.password || 'Les mots de passe ne correspondent pas'
    },
  }

  function getUserInitials (name: string) {
    return name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  function getAvatarColor (id: number) {
    const colors = ['primary', 'success', 'info', 'warning', 'secondary', 'error', 'purple', 'teal']
    return colors[id % colors.length]
  }

  function getRoleLabel (userType: string) {
    const labels: Record<string, string> = {
      admin: 'Administrateur',
      entreprise: 'Entreprise',
      user: 'Utilisateur',
      collaborator: 'Collaborateur',
    }
    return labels[userType] || userType
  }

  function getRoleBadgeColor (userType: string) {
    const colors: Record<string, string> = {
      admin: 'error',
      entreprise: 'primary',
      user: 'info',
      collaborator: 'success',
    }
    return colors[userType] || 'grey'
  }

  function formatDate (dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  async function loadUser () {
    loading.value = true
    try {
      if (userId.value === null) {
        user.value = null
        return
      }
      user.value = await userService.getById(userId.value)
      editedUser.value = { ...user.value }
      selectedRoles.value = user.value.roles?.map(r => r.id) || []
    } catch (error: any) {
      console.error('Error loading user:', error)
      toast.error('Erreur lors du chargement de l\'utilisateur')
      user.value = null
    } finally {
      loading.value = false
    }
  }

  async function loadSites () {
    try {
      const response = await siteService.getAll()
      sites.value = response.data.map(site => ({ id: site.id, name: site.name }))
    } catch (error) {
      console.error('Error loading sites:', error)
    }
  }

  async function loadRoles () {
    try {
      roles.value = await userService.getRoles()
    } catch (error) {
      console.error('Error loading roles:', error)
    }
  }

  function cancelEdit () {
    editMode.value = false
    editedUser.value = { ...user.value! }
    selectedRoles.value = user.value?.roles?.map(r => r.id) || []
    passwordData.value = { password: '', password_confirmation: '' }
  }

  async function saveChanges () {
    const validation = await formRef.value?.validate()
    if (!validation?.valid) return

    saving.value = true
    try {
      const userId = user.value!.id
      const updateData: UpdateUserRequest = {
        name: editedUser.value.name,
        username: editedUser.value.username,
        email: editedUser.value.email,
        phone: editedUser.value.phone ?? undefined,
        site_id: editedUser.value.site_id ?? undefined,
        user_type: editedUser.value.user_type as any,
        roles: selectedRoles.value,
      }

      if (passwordData.value.password) {
        updateData.password = passwordData.value.password
        updateData.password_confirmation = passwordData.value.password_confirmation
      }

      user.value = await userService.update(userId, updateData)
      editedUser.value = { ...user.value }
      editMode.value = false
      passwordData.value = { password: '', password_confirmation: '' }
      toast.success('Utilisateur modifié avec succès')
    } catch (error: any) {
      console.error('Error updating user:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la modification')
    } finally {
      saving.value = false
    }
  }

  async function toggleUserStatus () {
    try {
      user.value = await userService.toggleActive(user.value!.id)
      toast.success(`Utilisateur ${user.value.is_active ? 'activé' : 'désactivé'} avec succès`)
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de la modification du statut')
    }
  }

  function confirmDelete () {
    deleteDialog.value = true
  }

  async function deleteUser () {
    deleting.value = true
    try {
      await userService.delete(user.value!.id)
      toast.success('Utilisateur supprimé avec succès')
      router.push('/company/users')
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
      deleteDialog.value = false
    } finally {
      deleting.value = false
    }
  }

  function goBack () {
    router.push('/company/users')
  }

  onMounted(() => {
    loadUser()
    loadSites()
    loadRoles()
  })
</script>

<style scoped>
.v-breadcrumbs :deep(.v-breadcrumbs-item) {
  font-size: 14px;
  color: #64748B;
}

.user-page-loader-scope {
  position: relative;
}

.user-page-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}
</style>
