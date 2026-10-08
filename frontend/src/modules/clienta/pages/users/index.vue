<template>
  <ClientALayout current-page="users">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-account-group"
        subtitle="Gérez les utilisateurs de votre entreprise"
        title="Collaborateurs"
      >
        <template #actions>
          <v-btn
            v-if="canCreateUser"
            color="primary"
            prepend-icon="mdi-plus"
            @click="goToCreate"
          >
            Nouveau Collaborateur
          </v-btn>
        </template>
      </PageHeader>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <AppWidget
          clickable
          :icon="Users"
          title="Total"
          :value="totalUsers"
          variant="primary"
          @click="filters.status = null"
        />
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Actifs"
          :value="activeCount"
          variant="success"
          @click="filters.status = true"
        />
        <AppWidget
          clickable
          :icon="XCircle"
          title="Inactifs"
          :value="inactiveCount"
          variant="error"
          @click="filters.status = false"
        />
        <AppWidget
          clickable
          :icon="MapPin"
          title="Sites"
          :value="sitesCount"
          variant="info"
        />
      </div>

      <FilterCard>
        <v-row>
          <v-col cols="12" md="4">
            <v-text-field
              v-model="filters.search"
              density="comfortable"
              hide-details
              placeholder="Rechercher par nom, email, poste..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.site"
              clearable
              density="comfortable"
              hide-details
              item-title="title"
              item-value="value"
              :items="siteOptions"
              placeholder="Tous les sites"
              prepend-inner-icon="mdi-map-marker"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.role"
              clearable
              density="comfortable"
              hide-details
              :items="roleOptions"
              placeholder="Tous les rôles"
              prepend-inner-icon="mdi-shield-account"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              clearable
              density="comfortable"
              hide-details
              item-title="title"
              item-value="value"
              :items="statusOptions"
              placeholder="Statut"
              prepend-inner-icon="mdi-filter"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </FilterCard>

      <div class="d-flex justify-end mb-4">
        <v-btn-toggle v-model="viewMode" divided mandatory variant="outlined">
          <v-btn icon="mdi-table" value="table" />
          <v-btn icon="mdi-view-grid" value="grid" />
        </v-btn-toggle>
      </div>

      <DataTable
        v-if="viewMode === 'table'"
        :headers="headers"
        :items="filteredUsers"
        :items-per-page="itemsPerPage"
        :loading="loading"
      >
        <template #item.user="{ item }">
          <div class="d-flex align-center py-2">
            <v-avatar class="mr-3" :color="getAvatarColor(item.id)" size="40">
              <span class="text-white font-weight-bold">{{ getUserInitials(item.name) }}</span>
            </v-avatar>
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-medium-emphasis">
                <v-icon class="mr-1" size="14">mdi-email</v-icon>
                {{ item.email }}
              </div>
            </div>
          </div>
        </template>

        <template #item.position="{ item }">
          <div class="text-body-2">{{ item.position || 'Non spécifié' }}</div>
        </template>

        <template #item.role="{ item }">
          <v-chip :color="getRoleBadgeColor(item.user_type)" size="small" variant="flat">
            {{ getRoleLabel(item.user_type) }}
          </v-chip>
        </template>

        <template #item.site="{ item }">
          <div v-if="item.site" class="d-flex align-center">
            <v-icon class="mr-2 text-medium-emphasis" size="16">mdi-map-marker</v-icon>
            {{ item.site.name }}
          </div>
          <div v-else class="text-medium-emphasis text-caption">Aucun site</div>
        </template>

        <template #item.status="{ item }">
          <StatusChip :status="item.is_active ? 'active' : 'inactive'" type="user" />
        </template>

        <template #item.actions="{ item }">
          <div class="d-flex gap-1">
            <v-tooltip location="top" text="Voir détails">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  density="comfortable"
                  icon="mdi-eye"
                  size="small"
                  variant="text"
                  @click="goToEdit(item)"
                />
              </template>
            </v-tooltip>
            <v-tooltip v-if="item.user_type === 'company' && canManageUserPermissions" location="top" text="Gérer permissions">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  color="primary"
                  density="comfortable"
                  icon="mdi-shield-account"
                  size="small"
                  variant="text"
                  @click="goToPermissions(item)"
                />
              </template>
            </v-tooltip>
            <v-tooltip v-if="canUpdateUser" location="top" :text="item.is_active ? 'Désactiver' : 'Activer'">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  :color="item.is_active ? 'warning' : 'success'"
                  density="comfortable"
                  :icon="item.is_active ? 'mdi-account-off' : 'mdi-account-check'"
                  size="small"
                  variant="text"
                  @click="toggleUserStatus(item)"
                />
              </template>
            </v-tooltip>
            <v-tooltip v-if="canDeleteUser" location="top" text="Supprimer">
              <template #activator="{ props }">
                <v-btn
                  v-bind="props"
                  color="error"
                  density="comfortable"
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  @click="deleteUser(item)"
                />
              </template>
            </v-tooltip>
          </div>
        </template>
      </DataTable>

      <v-row v-else>
        <template v-if="loading">
          <v-col
            v-for="n in 8"
            :key="`skeleton-${n}`"
            cols="12"
            lg="3"
            md="4"
            sm="6"
          >
            <v-skeleton-loader type="card" />
          </v-col>
        </template>

        <v-col
          v-for="user in filteredUsers"
          v-else
          :key="user.id"
          cols="12"
          lg="3"
          md="4"
          sm="6"
        >
          <v-card class="h-100" hover>
            <v-card-text class="text-center">
              <v-avatar class="mb-3" :color="getAvatarColor(user.id)" size="64">
                <span class="text-h6 text-white font-weight-bold">{{ getUserInitials(user.name) }}</span>
              </v-avatar>
              <div class="font-weight-bold text-h6 mb-1">{{ user.name }}</div>
              <div class="text-caption text-medium-emphasis mb-3">{{ user.email }}</div>
              <v-chip class="mb-2" :color="getRoleBadgeColor(user.user_type)" size="small">
                {{ getRoleLabel(user.user_type) }}
              </v-chip>
              <StatusChip class="mb-3 ml-1" :status="user.is_active ? 'active' : 'inactive'" type="user" />
              <v-divider class="my-3" />
              <div class="text-left">
                <div class="text-caption text-medium-emphasis mb-1">
                  <v-icon class="mr-1" size="14">mdi-account</v-icon>
                  {{ user.username }}
                </div>
                <div v-if="user.site" class="text-caption text-medium-emphasis">
                  <v-icon class="mr-1" size="14">mdi-map-marker</v-icon>
                  {{ user.site.name }}
                </div>
                <div v-else class="text-caption text-medium-emphasis">
                  <v-icon class="mr-1" size="14">mdi-map-marker-off</v-icon>
                  Aucun site
                </div>
              </div>
            </v-card-text>
            <v-card-actions>
              <v-btn prepend-icon="mdi-eye" size="small" variant="text" @click="goToEdit(user)">Voir</v-btn>
              <v-spacer />
              <v-btn
                v-if="canUpdateUser"
                :color="user.is_active ? 'warning' : 'success'"
                :icon="user.is_active ? 'mdi-account-off' : 'mdi-account-check'"
                size="small"
                variant="text"
                @click="toggleUserStatus(user)"
              />
              <v-btn
                v-if="canDeleteUser"
                color="error"
                icon="mdi-delete"
                size="small"
                variant="text"
                @click="deleteUser(user)"
              />
            </v-card-actions>
          </v-card>
        </v-col>

        <v-col v-if="!loading && filteredUsers.length === 0" cols="12">
          <v-card>
            <v-card-text class="text-center py-12">
              <v-icon color="grey-lighten-1" size="64">mdi-account-off</v-icon>
              <div class="text-h6 mt-4 mb-2">Aucun collaborateur trouvé</div>
              <div class="text-body-2 text-medium-emphasis mb-4">Essayez de modifier vos filtres</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { CheckCircle, MapPin, Users, XCircle } from 'lucide-vue-next'
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import siteService from '@/services/siteService'
  import userService, { type User } from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()

  const viewMode = ref('table')
  const loading = ref(false)
  const users = ref<User[]>([])
  const sites = ref<any[]>([])
  const page = ref(1)
  const itemsPerPage = ref(10)
  const totalUsers = ref(0)

  const filters = ref({
    search: '',
    site: null as number | null,
    role: null as string | null,
    status: null as boolean | null,
  })

  const headers = [
    { title: 'Collaborateur', key: 'user', sortable: true },
    { title: 'Poste', key: 'position', sortable: true },
    { title: 'Rôle', key: 'role', sortable: true },
    { title: 'Site', key: 'site', sortable: true },
    { title: 'Statut', key: 'status', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const roleOptions = ['Tous', 'super_admin', 'company', 'clientb']
  const statusOptions = [
    { title: 'Tous', value: null },
    { title: 'Actif', value: true },
    { title: 'Inactif', value: false },
  ]

  const siteOptions = computed(() => {
    return [
      { title: 'Tous les sites', value: null },
      ...sites.value.map(s => ({ title: s.name, value: s.id })),
    ]
  })

  const filteredUsers = computed(() => {
    return users.value.filter(user => {
      const matchSearch = !filters.value.search
        || user.name.toLowerCase().includes(filters.value.search.toLowerCase())
        || user.email.toLowerCase().includes(filters.value.search.toLowerCase())
        || user.username.toLowerCase().includes(filters.value.search.toLowerCase())

      const matchSite = filters.value.site === null || user.site_id === filters.value.site
      const matchRole = !filters.value.role || filters.value.role === 'Tous' || user.user_type === filters.value.role
      const matchStatus = filters.value.status === null || user.is_active === filters.value.status

      return matchSearch && matchSite && matchRole && matchStatus
    })
  })

  const activeCount = computed(() => users.value.filter(u => u.is_active).length)
  const inactiveCount = computed(() => users.value.filter(u => !u.is_active).length)
  const sitesCount = computed(() => new Set(users.value.filter(u => u.site_id).map(u => u.site_id)).size)

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

  const canCreateUser = computed(() => canAccess(['users.create']))
  const canUpdateUser = computed(() => canAccess(['users.update']))
  const canDeleteUser = computed(() => canAccess(['users.delete']))
  const canManageUserPermissions = computed(() => canAccess(['users.manage']))

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
      super_admin: 'Super Admin',
      company: 'Entreprise',
      clientb: 'Client B',
    }
    return labels[userType] || userType
  }

  function getRoleBadgeColor (userType: string) {
    const colors: Record<string, string> = {
      super_admin: 'error',
      company: 'primary',
      clientb: 'info',
    }
    return colors[userType] || 'grey'
  }

  function goToCreate () {
    if (!canCreateUser.value) return
    router.push('/company/users/create')
  }

  function goToEdit (user: User) {
    router.push(`/company/users/${user.id}`)
  }

  function goToPermissions (user: User) {
    if (!canManageUserPermissions.value) return
    router.push(`/company/users/${user.id}/permissions`)
  }

  async function toggleUserStatus (user: User) {
    if (!canUpdateUser.value) return
    try {
      await userService.toggleActive(user.id)
      toast.success(`Utilisateur ${user.is_active ? 'désactivé' : 'activé'} avec succès`)
      await loadUsers()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de la modification du statut')
    }
  }

  async function deleteUser (user: User) {
    if (!canDeleteUser.value) return
    if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur ${user.name} ?`)) return

    try {
      await userService.delete(user.id)
      toast.success('Utilisateur supprimé avec succès')
      await loadUsers()
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Erreur lors de la suppression')
    }
  }

  async function loadUsers () {
    loading.value = true
    try {
      const response = await userService.getAll({
        page: page.value,
        per_page: itemsPerPage.value,
      })
      users.value = response.data
      totalUsers.value = response.meta?.total ?? response.data.length
    } catch (error: any) {
      console.error('Error loading users:', error)
      toast.error(error.response?.data?.message || 'Erreur lors du chargement des utilisateurs')
    } finally {
      loading.value = false
    }
  }

  async function loadSites () {
    try {
      const response = await siteService.getAll()
      sites.value = response.data || []
    } catch (error) {
      console.error('Error loading sites:', error)
    }
  }

  onMounted(() => {
    loadUsers()
    loadSites()
  })
</script>
