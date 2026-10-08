<template>
  <SuperAdminLayout current-page="users">
    <v-container class="pa-6" fluid>
      <!-- Header -->
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h4 font-weight-bold text-primary mb-2">
            Gestion des Utilisateurs
          </h1>
          <p class="text-subtitle-1 text-grey-darken-1">
            Vue d'ensemble de tous les comptes sur la plateforme
          </p>
        </div>
        <div class="d-flex align-center ga-2 flex-wrap">
          <v-btn-toggle v-model="viewMode" density="compact" mandatory>
            <v-btn value="table" variant="outlined">
              <v-icon start>mdi-table</v-icon>
              Table
            </v-btn>
            <v-btn value="cards" variant="outlined">
              <v-icon start>mdi-view-grid</v-icon>
              Cartes
            </v-btn>
          </v-btn-toggle>
          <v-btn
            color="primary"
            prepend-icon="mdi-account-plus"
            variant="flat"
            @click="openCreateDialog"
          >
            Nouveau Super Admin
          </v-btn>
          <v-btn
            color="primary"
            :loading="loading"
            prepend-icon="mdi-refresh"
            variant="outlined"
            @click="loadData"
          >
            Actualiser
          </v-btn>
        </div>
      </div>

      <!-- Statistics Cards -->
      <v-row v-if="stats" class="mb-6">
        <v-col cols="12" md="3" sm="6">
          <StatCard
            color="primary"
            icon="mdi-account-group"
            label="Total Utilisateurs"
            :value="stats.total"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <StatCard
            color="success"
            icon="mdi-account-check"
            label="Comptes Actifs"
            :value="stats.active"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <StatCard
            color="info"
            icon="mdi-briefcase-account"
            label="Clients B Inscrits"
            :value="stats.client_b_total"
          />
        </v-col>
        <v-col cols="12" md="3" sm="6">
          <StatCard
            color="warning"
            icon="mdi-clock-outline"
            label="Cette Semaine"
            :value="stats.recent.this_week"
          />
        </v-col>
      </v-row>

      <!-- User Type Distribution -->
      <v-card class="mb-6 sa-card sa-filters" elevation="2">
        <v-card-text class="pa-6">
          <h3 class="text-h6 font-weight-bold mb-4">
            Répartition par Type de Compte
          </h3>
          <v-row v-if="stats">
            <v-col
              v-for="(label, type) in userTypeLabels"
              :key="type"
              cols="6"
              lg="2"
              md="3"
              sm="4"
            >
              <div
                class="text-center pa-3 rounded-lg"
                style="background: rgb(var(--v-theme-surface-variant) / 0.3)"
              >
                <div class="text-h5 font-weight-bold mb-1 text-primary">
                  {{ stats.by_type[type] || 0 }}
                </div>
                <div class="text-caption text-medium-emphasis">
                  {{ label }}
                </div>
              </div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Filters -->
      <v-card class="mb-6" elevation="2">
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                clearable
                density="comfortable"
                hide-details
                placeholder="Rechercher par nom, email ou username..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                @input="debouncedSearch"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="filters.user_type"
                density="comfortable"
                hide-details
                :items="userTypeOptions"
                label="Type de compte"
                variant="outlined"
                @update:model-value="loadUsers"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="filters.is_active"
                density="comfortable"
                hide-details
                :items="statusOptions"
                label="Statut"
                variant="outlined"
                @update:model-value="loadUsers"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Users Table -->
      <v-card
        v-if="viewMode === 'table'"
        class="sa-card sa-table-card"
        elevation="2"
      >
        <v-card-text class="pa-0">
          <v-data-table
            class="sa-table"
            density="compact"
            :headers="headers"
            hide-default-footer
            :items="users"
            :items-per-page="filters.per_page"
            :loading="loading"
            loading-text="Chargement des utilisateurs..."
          >
            <!-- User Column -->
            <template #item.name="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar class="mr-3" color="primary" size="40">
                  <span class="text-white font-weight-bold">{{
                    getInitials(item.name)
                  }}</span>
                </v-avatar>
                <div>
                  <div class="font-weight-medium">{{ item.name }}</div>
                  <div
                    class="text-caption"
                    style="color: rgb(var(--v-theme-on-surface-variant))"
                  >
                    @{{ item.username }}
                  </div>
                </div>
              </div>
            </template>

            <!-- User Type Column -->
            <template #item.user_type="{ item }">
              <v-chip
                :color="getUserTypeColor(item.user_type)"
                label
                size="x-small"
                variant="tonal"
              >
                {{ getUserTypeLabel(item.user_type) }}
              </v-chip>
            </template>

            <!-- Enterprise Column -->
            <template #item.enterprise="{ item }">
              <span v-if="item.enterprise">{{ item.enterprise.name }}</span>
              <span
                v-else
                class="text-caption"
                style="color: rgb(var(--v-theme-on-surface-variant))"
              >-</span>
            </template>

            <!-- Site Column -->
            <template #item.site="{ item }">
              <span v-if="item.site">{{ item.site.name }}</span>
              <span
                v-else
                class="text-caption"
                style="color: rgb(var(--v-theme-on-surface-variant))"
              >-</span>
            </template>

            <!-- Status Column -->
            <template #item.is_active="{ item }">
              <v-chip
                :color="item.is_active ? 'success' : 'error'"
                label
                size="x-small"
                variant="tonal"
              >
                {{ item.is_active ? "Actif" : "Inactif" }}
              </v-chip>
            </template>

            <!-- Created At Column -->
            <template #item.created_at="{ item }">
              <span class="text-caption">{{
                formatDate(item.created_at)
              }}</span>
            </template>

            <!-- Last Login Column -->
            <template #item.last_login_at="{ item }">
              <span v-if="item.last_login_at" class="text-caption">{{
                formatDate(item.last_login_at)
              }}</span>
              <span
                v-else
                class="text-caption"
                style="color: rgb(var(--v-theme-on-surface-variant))"
              >Jamais</span>
            </template>
            <!-- Role Column -->
            <template #item.role="{ item }">
              <span v-if="getSuperAdminRoleLabel(item)">
                {{ getSuperAdminRoleLabel(item) }}
              </span>
              <span
                v-else
                class="text-caption"
                style="color: rgb(var(--v-theme-on-surface-variant))"
              >-</span>
            </template>

            <!-- Actions Column -->
            <template #item.actions="{ item }">
              <div class="d-flex align-center justify-center gap-2">
                <v-tooltip location="top" text="Modifier">
                  <template #activator="{ props: tooltipProps }">
                    <v-btn
                      v-if="item.user_type === 'super_admin'"
                      v-bind="tooltipProps"
                      icon="mdi-pencil"
                      size="small"
                      variant="text"
                      @click="openEditDialog(item)"
                    />
                  </template>
                </v-tooltip>
              </div>
            </template>

            <!-- No Data -->
            <template #no-data>
              <EmptyState
                description="Aucun resultat ne correspond a vos filtres."
                icon="mdi-account-off-outline"
                title="Aucun utilisateur trouve"
              />
            </template>
          </v-data-table>

          <!-- Pagination -->
          <v-divider />
          <div
            v-if="meta && meta.total > 0"
            class="d-flex justify-space-between align-center pa-4"
          >
            <div
              class="text-caption"
              style="color: rgb(var(--v-theme-on-surface-variant))"
            >
              Page {{ meta.current_page }} sur {{ meta.last_page }} -
              {{ meta.total }} utilisateur(s)
            </div>
            <div class="d-flex gap-2">
              <v-tooltip location="top" text="Page précédente">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    :disabled="meta.current_page === 1"
                    icon="mdi-chevron-left"
                    size="small"
                    variant="text"
                    @click="changePage(meta.current_page - 1)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Page suivante">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-bind="tooltipProps"
                    :disabled="meta.current_page === meta.last_page"
                    icon="mdi-chevron-right"
                    size="small"
                    variant="text"
                    @click="changePage(meta.current_page + 1)"
                  />
                </template>
              </v-tooltip>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-row v-else class="mt-2">
        <v-col v-if="users.length === 0" cols="12">
          <EmptyState
            description="Aucun resultat ne correspond a vos filtres."
            icon="mdi-account-off-outline"
            title="Aucun utilisateur trouve"
          />
        </v-col>
        <v-col
          v-for="user in users"
          :key="user.id"
          cols="12"
          lg="4"
          md="6"
        >
          <v-card elevation="2">
            <v-card-title class="d-flex justify-space-between align-center">
              <div>
                <div class="font-weight-bold">{{ user.name }}</div>
                <div class="text-caption text-medium-emphasis">
                  @{{ user.username }}
                </div>
              </div>
              <v-chip
                :color="user.is_active ? 'success' : 'error'"
                size="x-small"
                variant="tonal"
              >
                {{ user.is_active ? "Actif" : "Inactif" }}
              </v-chip>
            </v-card-title>
            <v-card-text>
              <div class="text-body-2 text-medium-emphasis mb-1">
                {{ user.email }}
              </div>
              <div class="d-flex flex-wrap ga-2 mt-2">
                <v-chip
                  :color="getUserTypeColor(user.user_type)"
                  size="x-small"
                  variant="tonal"
                >
                  {{ getUserTypeLabel(user.user_type) }}
                </v-chip>
                <v-chip
                  v-if="getSuperAdminRoleLabel(user)"
                  size="x-small"
                  variant="tonal"
                >
                  {{ getSuperAdminRoleLabel(user) }}
                </v-chip>
              </div>
              <div class="text-caption text-medium-emphasis mt-2">
                Créé le {{ formatDate(user.created_at) }}
              </div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-tooltip location="top" text="Modifier">
                <template #activator="{ props: tooltipProps }">
                  <v-btn
                    v-if="user.user_type === 'super_admin'"
                    v-bind="tooltipProps"
                    icon="mdi-pencil"
                    variant="text"
                    @click="openEditDialog(user)"
                  />
                </template>
              </v-tooltip>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>

      <!-- Super Admin Dialog -->
      <v-dialog v-model="dialogOpen" max-width="720">
        <v-card>
          <v-card-title class="text-h6 font-weight-bold">
            {{
              isEditMode ? "Modifier un Super Admin" : "Créer un Super Admin"
            }}
          </v-card-title>
          <v-card-text>
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.first_name"
                  :error-messages="fieldErrors.first_name"
                  label="Prénom"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.last_name"
                  :error-messages="fieldErrors.last_name"
                  label="Nom"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.username"
                  :error-messages="fieldErrors.username"
                  label="Username"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.email"
                  :error-messages="fieldErrors.email"
                  label="Email"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.role_name"
                  :error-messages="fieldErrors.role_name"
                  :items="roleOptions"
                  label="Rôle super admin"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-switch v-model="form.is_active" inset label="Compte actif" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.password"
                  :error-messages="fieldErrors.password"
                  label="Mot de passe"
                  :placeholder="
                    isEditMode ? 'Laisser vide pour ne pas changer' : ''
                  "
                  type="password"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.password_confirmation"
                  :error-messages="fieldErrors.password_confirmation"
                  label="Confirmation mot de passe"
                  :placeholder="
                    isEditMode ? 'Laisser vide pour ne pas changer' : ''
                  "
                  type="password"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="saveSuperAdmin">
              {{ isEditMode ? "Enregistrer" : "Créer" }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </SuperAdminLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useToast } from '@/composables/useToast'
  import StatCard from '@/modules/shared/components/StatCard.vue'
  import { useActionLock } from '@/modules/shared/composables/useActionLock'
  import EmptyState from '@/modules/superadmin/components/EmptyState.vue'
  import SuperAdminLayout from '@/modules/superadmin/components/SuperAdminLayout.vue'
  import superAdminService from '@/services/superAdminService'

  const users = ref<any[]>([])
  const stats = ref<any>(null)
  const loading = ref(false)
  const saving = ref(false)
  const meta = ref<any>(null)
  const roles = ref<Array<{ id: number, name: string, label: string }>>([])
  const viewMode = ref<'table' | 'cards'>(
    localStorage.getItem('sa_view_users') === 'cards' ? 'cards' : 'table',
  )
  const dialogOpen = ref(false)
  const isEditMode = ref(false)
  const editingUserId = ref<number | null>(null)
  const toast = useToast()
  const fieldErrors = ref<Record<string, string[]>>({})
  const { run: runLocked } = useActionLock()
  type UserTypeFilter = 'all' | 'super_admin' | 'company' | 'clientb'
  type ActiveFilter = 'all' | 'true' | 'false'

  const filters = ref({
    search: '',
    user_type: 'all' as UserTypeFilter,
    is_active: 'all' as ActiveFilter,
    page: 1,
    per_page: 20,
  })

  const headers = [
    { title: 'Utilisateur', key: 'name', sortable: false },
    { title: 'Email', key: 'email', sortable: false },
    { title: 'Type de Compte', key: 'user_type', sortable: false },
    { title: 'Role', key: 'role', sortable: false },
    { title: 'Entreprise', key: 'enterprise', sortable: false },
    { title: 'Site', key: 'site', sortable: false },
    { title: 'Statut', key: 'is_active', sortable: false },
    { title: 'Créé le', key: 'created_at', sortable: false },
    { title: 'Dernière Connexion', key: 'last_login_at', sortable: false },
    {
      title: 'Actions',
      key: 'actions',
      sortable: false,
      align: 'center' as const,
    },
  ]

  const userTypeLabels: Record<string, string> = {
    super_admin: 'Super Admin',
    company: 'Entreprise (Company)',
    clientb: 'Client B',
  }

  const userTypeOptions = [
    { title: 'Tous les types', value: 'all' },
    { title: 'Super Admin', value: 'super_admin' },
    { title: 'Entreprise (Company)', value: 'company' },
    { title: 'Client B', value: 'clientb' },
  ]

  const statusOptions = [
    { title: 'Tous les statuts', value: 'all' },
    { title: 'Actifs', value: 'true' },
    { title: 'Inactifs', value: 'false' },
  ]

  const roleOptions = computed(() =>
    roles.value.map(role => ({ title: role.label, value: role.name })),
  )

  const form = ref({
    first_name: '',
    last_name: '',
    username: '',
    email: '',
    role_name: '',
    is_active: true,
    password: '',
    password_confirmation: '',
  })

  let searchTimeout: any = null

  function debouncedSearch () {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      filters.value.page = 1
      loadUsers()
    }, 500)
  }

  async function loadUsers () {
    try {
      loading.value = true
      const response = await superAdminService.getAllUsers({
        ...filters.value,
        user_type:
          filters.value.user_type === 'all' ? undefined : filters.value.user_type,
        is_active:
          filters.value.is_active === 'all' ? undefined : filters.value.is_active,
      })
      users.value = response.data
      meta.value = response.meta
    } catch {
      toast.error('Erreur lors du chargement des utilisateurs')
    } finally {
      loading.value = false
    }
  }

  async function loadStats () {
    try {
      stats.value = await superAdminService.getUsersStats()
    } catch {
      toast.error('Erreur lors du chargement des statistiques')
    }
  }

  async function loadRoles () {
    try {
      roles.value = await superAdminService.getSuperAdminRoles()
    } catch {
      toast.error('Erreur lors du chargement des rôles')
    }
  }

  function loadData () {
    loadUsers()
    loadStats()
  }

  function openCreateDialog () {
    isEditMode.value = false
    editingUserId.value = null
    form.value = {
      first_name: '',
      last_name: '',
      username: '',
      email: '',
      role_name: roles.value[0]?.name ?? '',
      is_active: true,
      password: '',
      password_confirmation: '',
    }
    dialogOpen.value = true
  }

  function openEditDialog (user: any) {
    isEditMode.value = true
    editingUserId.value = user.id
    form.value = {
      first_name: user.first_name || '',
      last_name: user.last_name || '',
      username: user.username || '',
      email: user.email || '',
      role_name: getSuperAdminRoleName(user) || (roles.value[0]?.name ?? ''),
      is_active: Boolean(user.is_active),
      password: '',
      password_confirmation: '',
    }
    dialogOpen.value = true
  }

  function closeDialog () {
    dialogOpen.value = false
  }

  async function saveSuperAdmin () {
    await runLocked('superadmin-save', async () => {
      try {
        saving.value = true
        fieldErrors.value = {}
        if (isEditMode.value && editingUserId.value) {
          const payload: any = {
            first_name: form.value.first_name,
            last_name: form.value.last_name,
            username: form.value.username,
            email: form.value.email,
            role_name: form.value.role_name,
            is_active: form.value.is_active,
          }
          if (form.value.password) {
            payload.password = form.value.password
            payload.password_confirmation = form.value.password_confirmation
          }
          await superAdminService.updateSuperAdmin(editingUserId.value, payload)
          toast.success('Super admin mis à jour')
        } else {
          await superAdminService.createSuperAdmin({
            first_name: form.value.first_name,
            last_name: form.value.last_name,
            username: form.value.username,
            email: form.value.email,
            role_name: form.value.role_name,
            is_active: form.value.is_active,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation,
          })
          toast.success('Super admin créé')
        }
        dialogOpen.value = false
        loadUsers()
      } catch (error: any) {
        toast.error('Erreur lors de l\'opération')
        if (error?.response?.status === 422 && error?.response?.data?.errors) {
          fieldErrors.value = error.response.data.errors
        }
      } finally {
        saving.value = false
      }
    })
  }

  function changePage (page: number) {
    filters.value.page = page
    loadUsers()
  }

  function getInitials (name: string) {
    if (!name) return '??'
    const parts = name.split(' ').filter(Boolean)
    const first = parts[0]?.[0]
    const second = parts[1]?.[0]
    if (first && second) {
      return (first + second).toUpperCase()
    }
    return (
      first ? first + (parts[0]?.[1] || '') : name.slice(0, 2)
    ).toUpperCase()
  }

  function getUserTypeLabel (type: string) {
    return userTypeLabels[type] || type
  }

  function getUserTypeColor (type: string) {
    const colorMap: Record<string, string> = {
      super_admin: 'error',
      company: 'primary',
      clientb: 'info',
    }
    return colorMap[type] || 'grey'
  }

  function getSuperAdminRoleName (user: any) {
    const rolesList = user?.roles || []
    const role = rolesList.find(
      (r: any) => typeof r?.name === 'string' && r.name.startsWith('super_admin'),
    )
    return role?.name || ''
  }

  function getSuperAdminRoleLabel (user: any) {
    const roleName = getSuperAdminRoleName(user)
    if (!roleName) return ''
    const role = roles.value.find(r => r.name === roleName)
    return role?.label || roleName
  }

  function formatDate (dateString: string) {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(date)
  }

  onMounted(() => {
    loadData()
    loadRoles()
  })

  watch(viewMode, mode => {
    localStorage.setItem('sa_view_users', mode)
  })
</script>
