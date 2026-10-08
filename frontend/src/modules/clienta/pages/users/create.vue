<template>
  <v-layout class="create-user-layout">
    <!-- Fixed Sidebar -->
    <v-navigation-drawer
      class="sidebar-drawer"
      permanent
      :width="260"
    >
      <div class="sidebar-content">
        <!-- Logo/Brand -->
        <div class="logo-section">
          <v-icon color="primary" size="32">mdi-shield-check</v-icon>
          <span class="logo-text">{{ appName }}</span>
        </div>

        <!-- Navigation -->
        <v-list class="nav-list" density="compact">
          <v-list-item
            v-for="item in navigationItems"
            :key="item.title"
            :active="item.active"
            class="nav-item"
            :prepend-icon="item.icon"
            :title="item.title"
            :to="item.to"
          />

          <v-divider class="my-4" />

          <v-list-subheader class="nav-subheader">UTILISATEURS</v-list-subheader>
          <v-list-item
            v-for="item in userManagementItems"
            :key="item.title"
            class="nav-item"
            :prepend-icon="item.icon"
            :title="item.title"
            :to="item.to"
          />

          <v-divider class="my-4" />

          <v-list-subheader class="nav-subheader">MODULES QHSE</v-list-subheader>
          <v-list-item
            v-for="item in qhseItems"
            :key="item.title"
            class="nav-item"
            :prepend-icon="item.icon"
            :title="item.title"
            :to="item.to"
          />
        </v-list>

        <!-- Bottom Section -->
        <div class="sidebar-bottom">
          <div class="bottom-actions">
            <LanguageSwitcher />
            <v-btn
              :icon="darkMode ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent'"
              size="small"
              variant="text"
              @click="toggleDarkMode"
            />
          </div>

          <v-divider class="mb-3" />

          <!-- User Profile -->
          <div class="user-profile">
            <v-avatar color="primary" size="40">
              <span class="text-h6">{{ userInitials }}</span>
            </v-avatar>
            <div class="user-info">
              <div class="user-name">{{ currentUser.name }}</div>
              <div class="user-role">{{ currentUser.role }}</div>
            </div>
            <v-btn
              icon="mdi-logout"
              size="small"
              variant="text"
              @click="handleLogout"
            />
          </div>
        </div>
      </div>

      <!-- Main Content Area -->
      <v-main class="main-content">
        <!-- Top Bar -->
        <v-app-bar
          class="top-bar"
          elevation="0"
          flat
          height="64"
        >
          <div class="top-bar-content">
            <div class="page-title">
              <v-icon class="mr-2">mdi-account-group</v-icon>
              <span class="text-h6">Collaborateurs</span>
            </div>

            <div class="search-container">
              <v-text-field
                class="search-field"
                density="compact"
                hide-details
                placeholder="Rechercher..."
                prepend-inner-icon="mdi-magnify"
                single-line
                variant="outlined"
              />
            </div>

            <div class="top-bar-actions">
              <v-btn
                class="notification-btn"
                icon="mdi-bell-outline"
                variant="text"
              >
                <v-icon>mdi-bell-outline</v-icon>
                <v-badge
                  color="error"
                  content="3"
                  floating
                />
              </v-btn>
              <v-avatar class="ml-2" color="primary" size="40">
                <span class="text-body-2">{{ userInitials }}</span>
              </v-avatar>
            </div>
          </div>
        </v-app-bar>

        <!-- Create User Content -->
        <div class="create-content">
          <!-- Header Section -->
          <div class="header-section">
            <div class="d-flex align-center gap-3">
              <v-btn
                icon="mdi-arrow-left"
                variant="text"
                @click="goBack"
              />
              <div>
                <v-breadcrumbs
                  class="pa-0"
                  density="compact"
                  :items="breadcrumbs"
                >
                  <template #divider>
                    <v-icon icon="mdi-chevron-right" size="small" />
                  </template>
                </v-breadcrumbs>
                <h1 class="text-h4 font-weight-bold">Nouveau Collaborateur</h1>
              </div>
            </div>
          </div>

          <!-- Form Card -->
          <v-card class="form-card create-user-loader-scope">
            <div v-if="loading" class="create-user-loader-overlay">
              <UnifiedLoader
                description="Validation et création du compte collaborateur"
                :show-skeleton="true"
                title="Création du collaborateur en cours..."
                variant="local"
              />
            </div>
            <v-form @submit.prevent="handleSubmit">
              <v-card-text class="pa-8">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.name"
                      density="comfortable"
                      label="Nom complet"
                      placeholder="Jean Dupont"
                      prepend-inner-icon="mdi-account"
                      required
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.username"
                      density="comfortable"
                      label="Nom d'utilisateur"
                      placeholder="jean.dupont"
                      prepend-inner-icon="mdi-account-circle"
                      required
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.email"
                      density="comfortable"
                      label="Email"
                      placeholder="jean.dupont@example.com"
                      prepend-inner-icon="mdi-email"
                      required
                      :rules="[rules.required, rules.email]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="formData.phone"
                      density="comfortable"
                      label="Téléphone"
                      placeholder="+33 6 12 34 56 78"
                      prepend-inner-icon="mdi-phone"
                      variant="outlined"
                    />
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12">
                    <v-alert
                      color="info"
                      icon="mdi-email-lock"
                      variant="tonal"
                    >
                      Le mot de passe est généré automatiquement et envoyé par email au collaborateur.
                    </v-alert>
                  </v-col>
                </v-row>

                <v-row>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="formData.site_id"
                      clearable
                      density="comfortable"
                      item-title="name"
                      item-value="id"
                      :items="sites"
                      label="Site"
                      placeholder="Sélectionner un site"
                      prepend-inner-icon="mdi-office-building"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="formData.user_type"
                      density="comfortable"
                      item-title="title"
                      item-value="value"
                      :items="userTypeOptions"
                      label="Type d'utilisateur"
                      prepend-inner-icon="mdi-account-badge"
                      required
                      :rules="[rules.required]"
                      variant="outlined"
                    />
                  </v-col>

                  <v-col cols="12">
                    <v-select
                      v-model="selectedRoles"
                      chips
                      closable-chips
                      density="comfortable"
                      hint="Sélectionnez un ou plusieurs rôles"
                      item-title="name"
                      item-value="id"
                      :items="roles"
                      label="Rôles"
                      multiple
                      persistent-hint
                      prepend-inner-icon="mdi-shield-account"
                      variant="outlined"
                    >
                      <template #chip="{ item, props }">
                        <v-chip v-bind="props" color="primary" size="small">
                          {{ item.title }}
                        </v-chip>
                      </template>
                    </v-select>
                  </v-col>

                  <v-col cols="12">
                    <v-switch
                      v-model="formData.is_active"
                      color="success"
                      hide-details
                      label="Compte actif"
                    >
                      <template #label>
                        <div class="d-flex align-center">
                          <v-icon
                            class="mr-2"
                            :color="formData.is_active ? 'success' : 'grey'"
                            :icon="formData.is_active ? 'mdi-check-circle' : 'mdi-close-circle'"
                            size="20"
                          />
                          <span>{{ formData.is_active ? 'Compte actif' : 'Compte inactif' }}</span>
                        </div>
                      </template>
                    </v-switch>
                  </v-col>
                </v-row>
              </v-card-text>

              <!-- Actions -->
              <v-card-actions class="px-8 pb-8 pt-0">
                <v-spacer />
                <v-btn
                  :disabled="loading"
                  size="large"
                  variant="outlined"
                  @click="goBack"
                >
                  <v-icon start>mdi-close</v-icon>
                  Annuler
                </v-btn>
                <v-btn
                  color="primary"
                  :loading="loading"
                  size="large"
                  type="submit"
                >
                  <v-icon start>mdi-content-save</v-icon>
                  Enregistrer
                </v-btn>
              </v-card-actions>
            </v-form>
          </v-card>

          <!-- Success Snackbar -->
          <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            location="top right"
            :timeout="3000"
          >
            <div class="d-flex align-center gap-2">
              <v-icon>{{ snackbar.icon }}</v-icon>
              {{ snackbar.message }}
            </div>
          </v-snackbar>
        </div></v-main>
    </v-navigation-drawer></v-layout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import { useTheme } from 'vuetify'
  import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue'
  import { STORAGE_KEYS } from '@/config/constants'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'
  import siteService from '@/services/siteService'
  import userService, { type CreateUserRequest, type Role } from '@/services/userService'
  import { storage } from '@/utils/storage'

  const router = useRouter()
  const appName = String(import.meta.env.VITE_APP_NAME || 'BestQHSE').trim() || 'BestQHSE'
  const theme = useTheme()
  const toast = useToast()

  // State
  const formRef = ref()
  const loading = ref(false)
  const darkMode = ref(theme.global.name.value === 'dark')
  const sites = ref<any[]>([])
  const roles = ref<Role[]>([])
  const selectedRoles = ref<number[]>([])

  const formData = ref<CreateUserRequest>({
    name: '',
    username: '',
    email: '',
    phone: '',
    site_id: undefined,
    user_type: 'company',
    is_active: true,
    roles: [],
    generate_password: true,
    send_welcome_email: true,
  })

  const snackbar = ref({
    show: false,
    message: '',
    color: 'success',
    icon: 'mdi-check-circle',
  })

  // Demo Data
  const currentUser = ref({
    name: 'Admin User',
    role: 'Administrateur',
  })

  const userTypeOptions = [
    { title: 'Entreprise (Company)', value: 'company' },
    { title: 'Client B', value: 'clientb' },
  ]

  const breadcrumbs = [
    { title: 'Collaborateurs', disabled: false, to: '/company/users' },
    { title: 'Nouveau', disabled: true },
  ]

  const navigationItems = [
    { title: 'Tableau de bord', icon: 'mdi-view-dashboard', to: '/company/dashboard', active: false },
    { title: 'Documents', icon: 'mdi-file-document-multiple', to: '/company/documents', active: false },
    { title: 'Non-conformités', icon: 'mdi-alert-circle', to: '/company/nonconformities', active: false },
  ]

  const userManagementItems = [
    { title: 'Collaborateurs', icon: 'mdi-account-group', to: '/company/users', active: true },
    { title: 'Sites', icon: 'mdi-office-building', to: '/company/sites', active: false },
  ]

  const qhseItems = [
    { title: 'Audits', icon: 'mdi-clipboard-check', to: '/company/audits', active: false },
    { title: 'Risques', icon: 'mdi-shield-alert', to: '/company/risks', active: false },
    { title: 'Indicateurs', icon: 'mdi-chart-line', to: '/company/indicators', active: false },
  ]

  // Validation Rules
  const rules = {
    required: (value: any) => !!value || 'Ce champ est requis',
    email: (value: string) => {
      const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return pattern.test(value) || 'Email invalide'
    },
  }

  // Computed
  const userInitials = computed(() => {
    const name = currentUser.value.name
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
  })

  // Methods
  async function loadSites () {
    try {
      const response = await siteService.getAll()
      sites.value = response.data || []
    } catch (error) {
      console.error('Error loading sites:', error)
      toast.error('Erreur lors du chargement des sites')
    }
  }

  async function loadRoles () {
    try {
      roles.value = await userService.getRoles()
    } catch (error) {
      console.error('Error loading roles:', error)
      toast.error('Erreur lors du chargement des rôles')
    }
  }

  async function handleSubmit () {
    const validation = await formRef.value?.validate()
    if (!validation?.valid) return

    loading.value = true

    try {
      // Assigner les rôles sélectionnés
      formData.value.roles = selectedRoles.value

      await userService.create(formData.value)
      toast.success('Collaborateur créé avec succès !')

      setTimeout(() => {
        router.push('/company/users')
      }, 500)
    } catch (error: any) {
      console.error('Error creating user:', error)
      toast.error(error.response?.data?.message || 'Erreur lors de la création du collaborateur')
    } finally {
      loading.value = false
    }
  }

  function goBack () {
    router.push('/company/users')
  }

  function toggleDarkMode () {
    darkMode.value = !darkMode.value
    theme.global.name.value = darkMode.value ? 'dark' : 'light'
  }

  function handleLogout () {
    storage.remove(STORAGE_KEYS.ACCESS_TOKEN)
    storage.remove(STORAGE_KEYS.USER)
    router.push('/auth/login')
  }

  onMounted(() => {
    loadSites()
    loadRoles()
  })
</script>

<style scoped>
.create-user-loader-scope {
  position: relative;
}

.create-user-loader-overlay {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}
</style>
