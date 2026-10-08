<template>
  <v-app>
    <!-- Navigation Drawer (Sidebar) -->
    <v-navigation-drawer
      elevation="1"
      permanent
      :width="280"
    >
      <!-- Logo Section -->
      <div class="pa-6 border-b">
        <div class="d-flex align-center">
          <AppLogo show-text :subtitle="roleLabel" variant="mark" />
        </div>
      </div>

      <!-- Super Admin Sidebar -->
      <div v-if="userRole === 'superadmin'" class="pa-4">
        <!-- Revenue Widget -->
        <v-card class="mb-4" color="success" dark elevation="3">
          <v-card-text class="pa-4">
            <div class="d-flex align-center mb-2">
              <v-icon class="mr-2" size="20">mdi-cash-multiple</v-icon>
              <span class="text-caption font-weight-medium">Chiffre d'affaires</span>
            </div>
            <h3 class="text-h5 font-weight-bold mb-1">3 560 000 FCFA</h3>
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption" style="opacity: 0.9">Mensuel</span>
              <v-chip color="white" size="x-small" variant="outlined">
                <v-icon size="12" start>mdi-trending-up</v-icon>
                +12%
              </v-chip>
            </div>
            <v-divider class="my-2" style="opacity: 0.3" />
            <div class="d-flex align-center justify-space-between">
              <span class="text-caption" style="opacity: 0.9">Annuel</span>
              <span class="text-body-2 font-weight-bold">42,7M FCFA</span>
            </div>
          </v-card-text>
        </v-card>

        <!-- Navigation Menu -->
        <v-list class="bg-transparent" density="compact" nav>
          <v-list-item
            v-for="item in superAdminMenuItems"
            :key="item.id"
            :active="currentPage === item.id"
            class="mb-1"
            color="primary"
            rounded="lg"
            :to="item.route"
            :value="item.id"
          >
            <template #prepend>
              <v-icon>{{ item.icon }}</v-icon>
            </template>
            <v-list-item-title>{{ item.label }}</v-list-item-title>
            <template v-if="item.badge" #append>
              <v-badge color="error" :content="item.badge" inline />
            </template>
          </v-list-item>
        </v-list>
      </div>

      <!-- Client A Sidebar (unchanged) -->
      <v-list v-else class="pa-4" density="compact" nav>
        <v-list-item
          v-for="item in clientAMenuItems"
          :key="item.id"
          :active="currentPage === item.id"
          class="mb-1"
          color="primary"
          rounded="lg"
          :to="item.route"
          :value="item.id"
        >
          <template #prepend>
            <v-icon>{{ item.icon }}</v-icon>
          </template>
          <v-list-item-title>{{ item.label }}</v-list-item-title>
          <template v-if="item.badge" #append>
            <v-badge color="error" :content="item.badge" inline />
          </template>
        </v-list-item>
      </v-list>

      <template #append>
        <!-- User Profile -->
        <div class="pa-4 border-t">
          <div class="d-flex align-center mb-3">
            <v-avatar class="mr-3" color="primary" size="40">
              <span class="text-body-2 font-weight-bold">{{ userInitials }}</span>
            </v-avatar>
            <div class="grow" style="min-width: 0;">
              <p class="text-body-2 font-weight-medium mb-0 text-truncate">{{ userName }}</p>
              <p class="text-caption text-medium-emphasis mb-0 text-truncate">{{ userEmail }}</p>
            </div>
          </div>
          <v-btn
            block
            color="error"
            prepend-icon="mdi-logout"
            variant="outlined"
            @click="handleLogout"
          >
            Déconnexion
          </v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <!-- App Bar -->
    <v-app-bar
      class="border-b"
      elevation="0"
      height="64"
    >
      <v-container class="d-flex align-center px-6" fluid>
        <!-- Search Bar -->
        <v-text-field
          class="mr-4"
          density="compact"
          hide-details
          placeholder="Rechercher..."
          prepend-inner-icon="mdi-magnify"
          single-line
          style="max-width: 500px;"
          variant="outlined"
        />

        <v-spacer />

        <!-- Right Actions -->
        <v-menu offset-y>
          <template #activator="{ props }">
            <v-btn
              class="mr-2"
              icon
              variant="text"
              v-bind="props"
            >
              <v-badge
                color="error"
                content="3"
                dot
              >
                <v-icon>mdi-bell-outline</v-icon>
              </v-badge>
            </v-btn>
          </template>
          <v-card max-width="400" min-width="350">
            <v-card-title class="d-flex align-center justify-space-between bg-surface-variant">
              <span class="font-weight-bold">Notifications</span>
              <v-btn color="primary" size="small" variant="text">
                Tout marquer comme lu
              </v-btn>
            </v-card-title>
            <v-list class="overflow-y-auto" lines="two" max-height="400">
              <v-list-item
                v-for="notif in notifications"
                :key="notif.id"
                :class="{ 'bg-blue-lighten-5': !notif.read }"
              >
                <template #prepend>
                  <v-avatar :color="notif.color" size="40" variant="tonal">
                    <v-icon :color="notif.color">{{ notif.icon }}</v-icon>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-medium">
                  {{ notif.title }}
                </v-list-item-title>
                <v-list-item-subtitle>{{ notif.message }}</v-list-item-subtitle>
                <template #append>
                  <span class="text-caption text-grey">{{ notif.time }}</span>
                </template>
              </v-list-item>
            </v-list>
            <v-divider />
            <v-card-actions>
              <v-btn block color="primary" to="/notifications" variant="text">
                Voir toutes les notifications
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-menu>

        <v-btn
          class="mr-2"
          icon
          variant="text"
          @click="toggleDarkMode"
        >
          <v-icon>{{ darkMode ? 'mdi-white-balance-sunny' : 'mdi-moon-waning-crescent' }}</v-icon>
        </v-btn>

        <v-menu offset-y>
          <template #activator="{ props }">
            <v-avatar class="cursor-pointer" color="primary" size="40" v-bind="props">
              <span class="text-body-2">{{ userInitials }}</span>
            </v-avatar>
          </template>
          <v-card min-width="250">
            <v-list>
              <v-list-item class="mb-2">
                <template #prepend>
                  <v-avatar color="primary" size="48">
                    <span class="text-h6">{{ userInitials }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title class="font-weight-bold">
                  {{ userName }}
                </v-list-item-title>
                <v-list-item-subtitle>{{ userEmail }}</v-list-item-subtitle>
              </v-list-item>
            </v-list>
            <v-divider />
            <v-list density="compact">
              <v-list-item prepend-icon="mdi-account" to="/profile">
                Mon profil
              </v-list-item>
              <v-list-item prepend-icon="mdi-cog" to="/settings">
                Paramètres
              </v-list-item>
              <v-list-item prepend-icon="mdi-lock" @click="handleLockSession">
                Verrouiller ma session
              </v-list-item>
            </v-list>
            <v-divider />
            <v-list density="compact">
              <v-list-item
                class="text-error"
                prepend-icon="mdi-logout"
                @click="handleLogout"
              >
                Déconnexion
              </v-list-item>
            </v-list>
          </v-card>
        </v-menu>
      </v-container>
    </v-app-bar>

    <!-- Main Content -->
    <v-main>
      <slot />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useTheme } from 'vuetify'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useMenu } from '@/composables/useMenu'
  import { useLockScreenStore } from '@/stores/lockScreen'

  // const props = defineProps<{
  //   currentPage: string
  // }>()
  const { currentPage } = defineProps<{
    currentPage: string
  }>()

  const router = useRouter()
  const route = useRoute()
  const theme = useTheme()
  const lockScreenStore = useLockScreenStore()
  const darkMode = ref(theme.global.current.value.dark)
  const { menuItems } = useMenu()
  // ✅ DEBUG : Vérifier le rôle au montage
  onMounted(() => {
    const userData = JSON.parse(localStorage.getItem('user') || '{}')
    console.log('🔍 User data:', userData)
    console.log('🔍 User role:', userData.role)
    console.log('🔍 Is superadmin?', userData.role === 'superadmin')
  })

  // Mock notifications
  const notifications = ref([
    {
      id: 1,
      title: 'Nouvelle inscription',
      message: 'SOMDIAA a soumis une demande KYC',
      time: 'Il y a 2h',
      icon: 'mdi-account-plus',
      color: 'primary',
      read: false,
    },
    {
      id: 2,
      title: 'Abonnement expiré',
      message: 'PALM CI - Renouvellement requis',
      time: 'Il y a 5h',
      icon: 'mdi-alert-circle',
      color: 'warning',
      read: false,
    },
    {
      id: 3,
      title: 'Paiement reçu',
      message: 'FILTISAC SA - 89 000 FCFA',
      time: 'Hier',
      icon: 'mdi-cash',
      color: 'success',
      read: true,
    },
  ])

  // User data from localStorage
  // const userData = JSON.parse(localStorage.getItem('user') || '{}')
  // const userName = computed(() => userData.name || 'Utilisateur')
  // const userEmail = computed(() => userData.email || '')
  // const userInitials = computed(() => {
  //   const names = userName.value.split(' ')
  //   return names.map(n => n[0]).join('').toUpperCase().slice(0, 2)
  // })

  // const roleLabel = computed(() => {
  //   if (userData.role === 'superadmin') return 'Super Admin'
  //   if (userData.role === 'clienta') return 'Admin Entreprise'
  //   return 'Utilisateur'
  // })

  const user = computed(() => {
    try {
      return JSON.parse(localStorage.getItem('user') || '{}')
    } catch (error) {
      console.error('Erreur lors de la lecture du localStorage:', error)
      return {}
    }
  })

  const userRole = computed(() => user.value.role || 'clienta')
  const userName = computed(() => user.value.name || 'Utilisateur')
  const userEmail = computed(() => user.value.email || '')
  const userInitials = computed(() => {
    const names = userName.value.split(' ')
    // return names.map(n => n[0]).join('').toUpperCase().slice(0, 2)
    return names.map((name: string) => name[0]).join('').toUpperCase().slice(0, 2)
  })

  const roleLabel = computed(() => {
    if (userRole.value === 'superadmin') return 'Super Admin'
    if (userRole.value === 'clienta') return 'Admin Entreprise'
    return 'Utilisateur'
  })

  interface MenuItem {
    id: string
    label: string
    icon: string
    route: string
    badge?: string
    children?: any[]
  }

  // Super Admin menu items
  const superAdminMenuItems = computed<MenuItem[]>(() => {
    if (userRole.value !== 'superadmin') return []
    return [
      { id: 'dashboard', label: 'Dashboard', icon: 'mdi-view-dashboard', route: '/superadmin/dashboard' },
      { id: 'kyc', label: 'Valider Inscriptions', icon: 'mdi-file-document-check', route: '/superadmin/kyc', badge: '8' },
      { id: 'companies', label: 'Gérer Entreprises', icon: 'mdi-office-building', route: '/superadmin/companies' },
      { id: 'subscriptions', label: 'Abonnements', icon: 'mdi-credit-card-check', route: '/superadmin/subscriptions' },
      { id: 'offers', label: 'Gérer Offres', icon: 'mdi-package-variant', route: '/superadmin/offers' },
      { id: 'norms', label: 'Gérer Normes', icon: 'mdi-certificate', route: '/superadmin/norms' },
      { id: 'settings', label: 'Paramètres', icon: 'mdi-cog', route: '/superadmin/settings' },
    ]
  })

  // Client A menu items - now based on subscription status
  const clientAMenuItems = computed<MenuItem[]>(() => {
    if (userRole.value === 'superadmin') return []

    // Use menu items from useMenu composable (filtered by subscription)
    return menuItems.value.map((item, index) => ({
      id: item.route.split('/').pop() || `item-${index}`,
      label: item.label,
      icon: item.icon,
      route: item.route,
      badge: (item as any).badge,
      children: item.children,
    }))
  })

  function toggleDarkMode () {
    darkMode.value = !darkMode.value
    theme.global.name.value = darkMode.value ? 'dark' : 'light'
  }

  function handleLockSession () {
    // Verrouiller la session avec le chemin actuel ET l'email
    lockScreenStore.lockSession(route.path, userEmail.value)
    // Rediriger vers la page de verrouillage
    router.push('/lock-screen')
  }

  function handleLogout () {
    localStorage.removeItem('accessToken')
    localStorage.removeItem('user')
    router.push('/auth/login')
  }
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.border-t {
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.cursor-pointer {
  cursor: pointer;
}
</style>
