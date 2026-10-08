<template>
  <v-app>
    <!-- Sidebar Navigation -->
    <v-navigation-drawer
      v-model="drawer"
      class="app-sidebar"
      color="#1E293B"
      :permanent="!isMobile"
      :temporary="isMobile"
      :width="260"
    >
      <!-- Logo Section -->
      <div class="sidebar-header">
        <QualiBestLogo :show-text="true" size="md" />
      </div>

      <!-- Navigation Menu -->
      <v-list class="sidebar-menu" density="compact" nav>
        <template v-for="(item, index) in menuItems" :key="index">
          <!-- Menu Item with Children (Group) -->
          <v-list-group v-if="item.children" :value="item.label">
            <template #activator="{ props: activatorProps }">
              <v-list-item
                v-bind="activatorProps"
                class="menu-item"
                :prepend-icon="item.icon"
                :title="undefined"
              />
            </template>

            <v-list-item
              v-for="(child, childIndex) in item.children"
              :key="childIndex"
              class="menu-item menu-item-child"
              :prepend-icon="child.icon"
              :title="undefined"
              :to="child.route"
            />
          </v-list-group>

          <!-- Single Menu Item -->
          <v-list-item
            v-else
            class="menu-item"
            :prepend-icon="item.icon"
            :title="undefined"
            :to="item.route"
          />
        </template>
      </v-list>
    </v-navigation-drawer>

    <!-- Top Bar -->
    <v-app-bar
      class="app-topbar"
      color="white"
      elevation="0"
      :height="64"
    >
      <!-- Mobile Menu Toggle -->
      <v-app-bar-nav-icon
        v-if="isMobile"
        @click="drawer = !drawer"
      />

      <!-- Left: Breadcrumb/Title Slot -->
      <div class="topbar-left">
        <slot name="breadcrumb">
          <h2 class="page-title">{{ pageTitle }}</h2>
        </slot>
      </div>

      <v-spacer />

      <!-- Center: Search Bar -->
      <div class="topbar-center">
        <v-text-field
          v-model="searchQuery"
          class="search-bar"
          clearable
          density="compact"
          hide-details
          placeholder="Rechercher..."
          prepend-inner-icon="mdi-magnify"
          single-line
          variant="outlined"
        />
      </div>

      <v-spacer />

      <!-- Right: Actions -->
      <div class="topbar-right">
        <!-- Notifications -->
        <v-badge
          color="error"
          :content="notificationCount"
          :model-value="notificationCount > 0"
          overlap
        >
          <v-btn icon size="small" variant="text">
            <v-icon>mdi-bell-outline</v-icon>
          </v-btn>
        </v-badge>

        <!-- Language Switcher -->
        <v-menu offset-y>
          <template #activator="{ props: menuProps }">
            <v-btn
              v-bind="menuProps"
              class="language-btn"
              icon
              size="small"
              variant="text"
            >
              <v-icon>{{ currentLanguage === 'fr' ? 'mdi-flag' : 'mdi-flag-outline' }}</v-icon>
            </v-btn>
          </template>
          <v-list density="compact">
            <v-list-item
              v-for="lang in languages"
              :key="lang.code"
              @click="changeLanguage(lang.code)"
            >
              <template #prepend>
                <span class="flag-icon">{{ lang.flag }}</span>
              </template>
              <v-list-item-title>{{ lang.label }}</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>

        <!-- User Menu -->
        <v-menu offset-y>
          <template #activator="{ props: menuProps }">
            <v-btn
              v-bind="menuProps"
              class="user-menu-btn"
              variant="text"
            >
              <v-avatar class="mr-2" size="32">
                <v-img
                  v-if="user?.avatar"
                  :alt="user.name"
                  :src="user.avatar"
                />
                <v-icon v-else>mdi-account-circle</v-icon>
              </v-avatar>
              <span class="user-name">{{ user?.name }}</span>
              <v-icon class="ml-1" size="small">mdi-chevron-down</v-icon>
            </v-btn>
          </template>

          <v-list class="user-dropdown" density="compact">
            <v-list-item>
              <v-list-item-title class="user-email">{{ user?.email }}</v-list-item-title>
            </v-list-item>
            <v-divider class="my-2" />
            <v-list-item @click="navigateTo('profile')">
              <template #prepend>
                <v-icon>mdi-account</v-icon>
              </template>
              <v-list-item-title>Mon profil</v-list-item-title>
            </v-list-item>
            <v-list-item @click="navigateTo('settings')">
              <template #prepend>
                <v-icon>mdi-cog</v-icon>
              </template>
              <v-list-item-title>Paramètres</v-list-item-title>
            </v-list-item>
            <v-list-item @click="handleLockSession">
              <template #prepend>
                <v-icon>mdi-lock</v-icon>
              </template>
              <v-list-item-title>Verrouiller ma session</v-list-item-title>
            </v-list-item>
            <v-divider class="my-2" />
            <v-list-item class="logout-item" @click="handleLogout">
              <template #prepend>
                <v-icon color="error">mdi-logout</v-icon>
              </template>
              <v-list-item-title class="text-error">Déconnexion</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-menu>
      </div>
    </v-app-bar>

    <!-- Main Content Area -->
    <v-main class="app-main">
      <slot />
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useDisplay } from 'vuetify'
  import QualiBestLogo from '@/components/QualiBestLogo.vue'
  import { useLockScreenStore } from '@/stores/lockScreen'

  export interface MenuItem {
    icon: string
    label: string
    route: string
    children?: MenuItem[]
  }

  export interface User {
    name: string
    email: string
    avatar?: string
  }

  interface Props {
    userType?: 'clienta' | 'super_admin' | 'client_b'
    menuItems: MenuItem[]
    user?: User
    pageTitle?: string
  }

  const _props = withDefaults(defineProps<Props>(), {
    userType: 'clienta',
    pageTitle: 'Dashboard',
  })

  const emit = defineEmits<{
    'logout': []
    'language-change': [lang: string]
  }>()

  const router = useRouter()
  const route = useRoute()
  const { mobile } = useDisplay()
  const lockScreenStore = useLockScreenStore()

  const drawer = ref(!mobile.value)
  const searchQuery = ref('')
  const currentLanguage = ref('fr')
  const notificationCount = ref(3)

  const isMobile = computed(() => mobile.value)

  const languages = [
    { code: 'fr', label: 'Français', flag: '🇫🇷' },
    { code: 'en', label: 'English', flag: '🇬🇧' },
  ]

  function changeLanguage (lang: string) {
    currentLanguage.value = lang
    emit('language-change', lang)
  }

  function navigateTo (routePath: string) {
    router.push(`/${routePath}`)
  }

  function handleLockSession () {
    // Utiliser l'email du user passé en props ou depuis authStore
    const userEmail = _props.user?.email || ''
    if (userEmail) {
      lockScreenStore.lockSession(route.path, userEmail)
    } else {
      // Fallback: récupérer depuis localStorage
      const storedUser = localStorage.getItem('user')
      const email = storedUser ? JSON.parse(storedUser).email : ''
      lockScreenStore.lockSession(route.path, email)
    }
    router.push('/lock-screen')
  }

  function handleLogout () {
    emit('logout')
  }

  onMounted(() => {
    drawer.value = !mobile.value
  })
</script>

<style scoped>
.app-sidebar {
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-header {
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.sidebar-menu {
  padding: 12px 8px;
}

.menu-item {
  border-radius: 8px;
  margin-bottom: 4px;
  color: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
}

.menu-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
  color: white;
}

.menu-item.v-list-item--active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(147, 51, 234, 0.15) 100%);
  color: white;
}

.menu-item.v-list-item--active :deep(.v-icon) {
  color: #6366F1;
}

.menu-item-child {
  padding-left: 48px !important;
  font-size: 0.875rem;
}

.app-topbar {
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95) !important;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.topbar-left {
  min-width: 200px;
  padding-left: 16px;
}

.page-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1E293B;
}

.topbar-center {
  max-width: 600px;
  width: 100%;
  padding: 0 24px;
}

.search-bar {
  border-radius: 12px;
}

.search-bar :deep(.v-field) {
  border-radius: 12px;
  background-color: #F8FAFC;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-right: 16px;
}

.user-menu-btn {
  text-transform: none;
  letter-spacing: normal;
}

.user-name {
  font-weight: 500;
  color: #1E293B;
}

.user-dropdown {
  min-width: 220px;
}

.user-email {
  font-size: 0.75rem;
  color: #64748B;
  font-weight: 400;
}

.logout-item:hover {
  background-color: rgba(239, 68, 68, 0.05);
}

.flag-icon {
  font-size: 1.25rem;
  margin-right: 8px;
}

.language-btn {
  margin: 0 4px;
}

.app-main {
  background-color: #F8FAFC;
  min-height: calc(100vh - 64px);
}

/* Mobile Responsive */
@media (max-width: 960px) {
  .topbar-center {
    display: none;
  }

  .user-name {
    display: none;
  }
}
</style>
