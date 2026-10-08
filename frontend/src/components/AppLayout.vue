<template>
  <v-app>
    <!-- App Bar -->
    <v-app-bar class="border-b" color="white" elevation="0">
      <template #prepend>
        <v-app-bar-nav-icon class="d-md-none" @click="drawer = !drawer" />
        <div class="d-flex align-center ml-4">
          <AppLogo show-text subtitle="Plateforme QHSE" variant="mark" />
        </div>
      </template>

      <v-spacer />

      <template #append>
        <!-- Notifications -->
        <v-btn class="mr-2" icon variant="text">
          <v-badge color="error" content="3" dot>
            <v-icon>mdi-bell-outline</v-icon>
          </v-badge>
        </v-btn>

        <!-- User Menu -->
        <v-menu>
          <template #activator="{ props }">
            <v-btn v-bind="props" class="mr-2" variant="text">
              <v-avatar class="mr-2" color="primary" size="32">
                <span class="text-caption">{{ userInitials }}</span>
              </v-avatar>
              <span class="text-body-2 d-none d-sm-inline">{{ userName }}</span>
              <v-icon class="ml-1" size="small">mdi-chevron-down</v-icon>
            </v-btn>
          </template>
          <v-list>
            <v-list-item prepend-icon="mdi-account" :to="`/${userRole}/profile`">
              <v-list-item-title>Mon Profil</v-list-item-title>
            </v-list-item>
            <v-list-item prepend-icon="mdi-cog" :to="`/${userRole}/settings`">
              <v-list-item-title>Paramètres</v-list-item-title>
            </v-list-item>
            <v-divider />
            <v-list-item class="text-error" prepend-icon="mdi-logout" @click="handleLogout">
              <v-list-item-title>Déconnexion</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </template>
    </v-app-bar>

    <!-- Navigation Drawer -->
    <v-navigation-drawer v-model="drawer" permanent :rail="rail" @click="rail = false">
      <!-- Company Info -->
      <div class="pa-4 border-b">
        <div v-if="!rail">
          <div class="text-overline text-medium-emphasis mb-1">Entreprise</div>
          <div class="text-body-1 font-weight-bold">{{ companyName }}</div>
          <div class="text-caption text-medium-emphasis">{{ subscriptionStatus }}</div>
        </div>
        <v-btn
          v-else
          icon
          size="small"
          variant="text"
          @click.stop="rail = !rail"
        >
          <v-icon>mdi-menu</v-icon>
        </v-btn>
      </div>

      <!-- Navigation Menu -->
      <v-list density="compact" nav>
        <v-list-item
          v-for="item in menuItems"
          :key="item.id"
          class="mx-2 mb-1"
          :prepend-icon="item.icon"
          rounded="lg"
          :to="item.route"
          :value="item.id"
        >
          <v-list-item-title>{{ item.label }}</v-list-item-title>
          <template v-if="item.badge" #append>
            <v-badge color="error" :content="item.badge" inline />
          </template>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- Main Content -->
    <v-main>
      <slot />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()

  const drawer = ref(true)
  const rail = ref(false)

  const userRole = computed(() => {
    if (authStore.user?.user_type === 'super_admin') return 'superadmin'
    if (authStore.user?.user_type === 'company') return 'company'
    if (authStore.user?.user_type === 'clientb') return 'clientb'
    return 'company'
  })

  const userName = computed(() => authStore.userName || 'Utilisateur')
  const userInitials = computed(() => {
    const name = userName.value
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
  })

  const companyName = computed(() => authStore.enterpriseName)

  const subscriptionStatus = computed(() => authStore.subscriptionStatus)

  const menuItems = computed(() => {
    if (userRole.value === 'superadmin') {
      return [
        { id: 'dashboard', label: 'Dashboard', icon: 'mdi-view-dashboard', route: '/superadmin/dashboard' },
        { id: 'companies', label: 'Entreprises', icon: 'mdi-office-building', route: '/superadmin/companies' },
        { id: 'kyc', label: 'KYC', icon: 'mdi-account-check', route: '/superadmin/kyc' },
        { id: 'norms', label: 'Normes', icon: 'mdi-book-open-variant', route: '/superadmin/norms' },
      ]
    }

    return [
      { id: 'dashboard', label: 'Dashboard', icon: 'mdi-view-dashboard', route: '/company/dashboard' },
      { id: 'documents', label: 'Documents', icon: 'mdi-file-document', route: '/company/documents' },
      { id: 'processes', label: 'Processus', icon: 'mdi-sitemap', route: '/company/processes' },
      { id: 'nonconformities', label: 'Non-Conformités', icon: 'mdi-alert-circle', route: '/company/nonconformities', badge: '5' },
      { id: 'audits', label: 'Audits', icon: 'mdi-clipboard-check', route: '/company/audits' },
      { id: 'risks', label: 'Risques', icon: 'mdi-alert-octagon', route: '/company/risks' },
      { id: 'indicators', label: 'Indicateurs', icon: 'mdi-chart-line', route: '/company/indicators' },
      { id: 'users', label: 'Collaborateurs', icon: 'mdi-account-group', route: '/company/users' },
      { id: 'sites', label: 'Sites', icon: 'mdi-map-marker', route: '/company/sites' },
      { id: 'subscriptions', label: 'Abonnements', icon: 'mdi-credit-card', route: '/company/subscription' },
    ]
  })

  function handleLogout () {
    authStore.clearAuth()
    router.push('/auth/login')
  }

  onMounted(() => {
    // Restaurer auth si nécessaire
    if (!authStore.isAuthenticated) {
      authStore.initAuth()
    }
  })
</script>
