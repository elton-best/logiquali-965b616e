<template>
  <v-app>
    <v-navigation-drawer
      v-model="drawer"
      class="border-e"
      color="surface"
      elevation="0"
      :permanent="!mobile"
      :rail="rail"
      :temporary="mobile"
    >
      <!-- Logo Section -->
      <div class="pa-4 d-flex align-center" :class="rail ? 'justify-center' : 'justify-space-between'">
        <div class="d-flex align-center">
          <AppLogo :variant="rail ? 'mark' : 'full'" />
          <transition name="fade">
            <div v-if="!rail" class="ms-3">
              <div class="text-caption text-medium-emphasis">Espace Client</div>
            </div>
          </transition>
        </div>
      </div>

      <v-divider />

      <!-- Navigation Menu -->
      <v-list
        aria-label="Menu principal"
        class="pa-2"
        density="comfortable"
        nav
        role="navigation"
      >
        <v-list-item
          v-for="item in menuItems"
          :key="item.path"
          :active="currentPage === item.path"
          :aria-label="item.title"
          class="mb-1 menu-item-animated"
          color="primary"
          :prepend-icon="item.icon"
          role="menuitem"
          rounded="lg"
          :title="item.title"
          :to="item.path"
          :value="item.path"
        >
          <template v-if="item.badge" #append>
            <v-badge
              color="error"
              :content="item.badge"
              inline
            />
          </template>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>

    <!-- Topbar -->
    <v-app-bar class="border-b" color="surface" elevation="0">
      <v-app-bar-nav-icon
        v-if="mobile"
        @click="drawer = !drawer"
      />

      <v-btn
        v-else
        :icon="rail ? 'mdi-menu' : 'mdi-menu-open'"
        @click="rail = !rail"
      />

      <v-toolbar-title class="text-h6 font-weight-medium ms-2">
        {{ pageTitle }}
      </v-toolbar-title>

      <v-spacer />

      <!-- Search (optional) -->
      <v-text-field
        v-if="!mobile"
        v-model="searchQuery"
        aria-label="Rechercher"
        class="me-4"
        clearable
        density="compact"
        hide-details
        :loading="isSearching"
        placeholder="Rechercher..."
        prepend-inner-icon="mdi-magnify"
        single-line
        style="max-width: 300px"
        variant="outlined"
        @update:model-value="debouncedSearch"
      />

      <!-- Notifications -->
      <NotificationCenter class="me-2" />

      <!-- Dark Mode Toggle -->
      <v-btn
        class="me-2"
        :icon="theme.global.current.value.dark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
        @click="toggleTheme"
      />

      <!-- User Menu -->
      <v-menu location="bottom end">
        <template #activator="{ props: menuProps }">
          <v-btn v-bind="menuProps" class="text-none">
            <v-avatar class="me-2" color="primary" size="32">
              <span class="text-white text-subtitle-2">{{ userInitials }}</span>
            </v-avatar>
            <span v-if="!mobile" class="me-1">{{ user.name }}</span>
            <v-icon>mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list width="250">
          <v-list-item>
            <template #prepend>
              <v-avatar color="primary" size="48">
                <span class="text-white">{{ userInitials }}</span>
              </v-avatar>
            </template>
            <v-list-item-title class="font-weight-medium">{{ user.name }}</v-list-item-title>
            <v-list-item-subtitle>{{ user.email }}</v-list-item-subtitle>
          </v-list-item>
          <v-divider class="my-2" />
          <v-list-item
            prepend-icon="mdi-account-outline"
            title="Mon Profil"
            to="/clientb/profile"
          />
          <v-list-item
            prepend-icon="mdi-cog-outline"
            title="Paramètres"
          />
          <v-list-item
            prepend-icon="mdi-lock"
            title="Verrouiller ma session"
            @click="handleLockSession"
          />
          <v-divider class="my-2" />
          <v-list-item
            prepend-icon="mdi-logout-variant"
            title="Déconnexion"
            @click="handleLogout"
          />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Main Content -->
    <v-main class="bg-surface-variant">
      <div class="pa-4 pa-md-6 page-wrapper">
        <slot />
      </div>

      <!-- Scroll to top button -->
      <v-btn
        v-if="true"
        aria-label="Retour en haut"
        class="scroll-to-top"
        color="primary"
        elevation="4"
        icon
        size="large"
        @click="scrollToTop()"
      >
        <v-icon>mdi-chevron-up</v-icon>
      </v-btn>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useDisplay, useTheme } from 'vuetify'
  import AppLogo from '@/components/branding/AppLogo.vue'
  import { useAuth } from '@/composables/useAuth'
  import { useDebounce } from '@/modules/shared/composables/useDebounce'
  import { useSmoothScroll } from '@/modules/shared/composables/useSmoothScroll'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { useLockScreenStore } from '@/stores/lockScreen'
  import NotificationCenter from './NotificationCenter.vue'

  interface Props {
    currentPage?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    currentPage: 'dashboard',
  })

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const lockScreenStore = useLockScreenStore()
  const { logout } = useAuth()
  const theme = useTheme()
  const { mobile } = useDisplay()
  const toast = useToast()
  const { scrollToTop } = useSmoothScroll()

  const drawer = ref(true)
  const rail = ref(false)
  const searchQuery = ref('')
  const isSearching = ref(false)
  const userEmail = computed(() => authStore.user?.email || '')

  // User data from auth store
  const user = computed(() => {
    const authUser = authStore.user
    return {
      name: authUser?.name || authUser?.username || 'Client',
      email: authUser?.email || '',
      unreadComplaints: 2,
    }
  })

  const userInitials = computed(() => {
    const name = user.value.name
    if (!name) return 'C'
    const names = name.split(' ')
    return names.map(n => n[0]).join('').toUpperCase().slice(0, 2)
  })

  // Menu items
  const menuItems = computed(() => [
    {
      title: 'Dashboard',
      icon: 'mdi-view-dashboard-outline',
      path: '/clientb/dashboard',
    },
    {
      title: 'Mes Plaintes',
      icon: 'mdi-file-document-outline',
      path: '/clientb/complaints',
      badge: user.value.unreadComplaints > 0 ? user.value.unreadComplaints : undefined,
    },
    {
      title: 'Notifications',
      icon: 'mdi-bell-outline',
      path: '/clientb/notifications',
    },
    {
      title: 'Satisfaction Client',
      icon: 'mdi-clipboard-check-outline',
      path: '/clientb/satisfaction-forms',
    },
    /* {
      title: 'Enquêtes de satisfaction',
      icon: 'mdi-clipboard-text-outline',
      path: '/clientb/surveys',
    }, */
    {
      title: 'Mon Profil',
      icon: 'mdi-account-outline',
      path: '/clientb/profile',
    },
    {
      title: 'Aide & Support',
      icon: 'mdi-help-circle-outline',
      path: '/clientb/support',
    },
  ])

  // Page title based on current page
  const pageTitle = computed(() => {
    const item = menuItems.value.find(i => i.path === props.currentPage)
    return item ? item.title : 'Dashboard'
  })

  function toggleTheme () {
    theme.global.name.value = theme.global.current.value.dark ? 'light' : 'dark'
    toast.info(theme.global.current.value.dark ? 'Mode sombre activé' : 'Mode clair activé', undefined, 2000)
  }

  async function handleLogout () {
    await logout()
  }

  function handleLockSession () {
    lockScreenStore.lockSession(route.path, userEmail.value)
    router.push('/lock-screen')
  }

  // Debounced search
  function performSearch () {
    if (!searchQuery.value.trim()) {
      isSearching.value = false
      return
    }
    isSearching.value = true
    setTimeout(() => {
      console.log('Searching for:', searchQuery.value)
      isSearching.value = false
    }, 1000)
  }

  const debouncedSearch = useDebounce(performSearch, 500)

  onMounted(() => {
    scrollToTop(0)
  })
</script>

<style scoped>
.logo-icon {
  transition: transform 0.3s ease;
}

.logo-icon:hover {
  transform: rotate(360deg);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.border-e {
  border-right: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* Page wrapper with fade-in animation */
.page-wrapper {
  animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Menu item animations */
.menu-item-animated {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  margin-bottom: 4px;
}

.menu-item-animated:hover {
  transform: translateX(2px);
  background: rgba(var(--v-theme-primary), 0.08);
}

.menu-item-animated:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

/* Amélioration des menus déroulants */
:deep(.v-menu > .v-overlay__content) {
  border-radius: 12px !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
  border: 1px solid rgba(var(--v-border-color), 0.1);
  margin-top: 8px;
}

:deep(.v-list) {
  padding: 8px;
}

:deep(.v-list-item) {
  border-radius: 8px;
  margin-bottom: 2px;
  min-height: 40px;
}

:deep(.v-list-item:hover) {
  background: rgba(var(--v-theme-primary), 0.08);
}

:deep(.v-list-subheader) {
  padding: 8px 12px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(var(--v-theme-on-surface), 0.6);
}

/* Scroll to top button */
.scroll-to-top {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-to-top:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(68, 113, 196, 0.3) !important;
}

.scroll-to-top:focus-visible {
  outline: 2px solid rgb(var(--v-theme-primary));
  outline-offset: 2px;
}

/* Smooth scrolling */
html {
  scroll-behavior: smooth;
}
</style>
