<template>
  <v-app-bar app class="md3-navbar" elevation="0" height="64">
    <v-container class="d-flex align-center" fluid>
      <router-link class="logo-container" to="/">
        <AppLogo class="navbar-logo" clickable subtitle="by Best Experts Group" variant="full" />
      </router-link>

      <v-spacer />

      <nav class="hidden-md-and-down nav-links">
        <v-btn
          v-for="item in navItems"
          :key="item.text"
          class="nav-btn-md3"
          variant="text"
          @click="emit('scroll', item.target)"
        >
          {{ item.text }}
        </v-btn>
      </nav>

      <div class="hidden-md-and-down d-flex align-center ml-4">
        <template v-if="isAuthenticated">
          <v-menu location="bottom end" open-on-hover>
            <template #activator="{ props }">
              <v-btn v-bind="props" class="profile-btn-md3" variant="text">
                <v-avatar class="mr-2" color="primary" size="32">
                  <span class="text-white text-caption">{{ userInitials }}</span>
                </v-avatar>
                <span class="text-body-2 font-weight-medium">{{ userDisplayName }}</span>
                <v-icon class="ml-2" size="18">mdi-chevron-down</v-icon>
              </v-btn>
            </template>
            <v-list density="compact" min-width="220">
              <v-list-item>
                <v-list-item-title class="text-caption text-medium-emphasis">
                  Connecté
                </v-list-item-title>
                <v-list-item-subtitle class="text-body-2">
                  {{ userEmail || 'Compte utilisateur' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-1" />
              <v-list-item @click="emit('navigate', 'dashboard')">
                <template #prepend>
                  <v-icon size="18">mdi-view-dashboard</v-icon>
                </template>
                <v-list-item-title>Tableau de bord</v-list-item-title>
              </v-list-item>
              <v-list-item @click="emit('navigate', 'logout')">
                <template #prepend>
                  <v-icon color="error" size="18">mdi-logout</v-icon>
                </template>
                <v-list-item-title class="text-error">Déconnexion</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
        <template v-else>
          <v-btn class="mr-3 login-btn-md3" variant="text" @click="emit('navigate', 'login')">
            Connexion
          </v-btn>

          <v-btn class="cta-btn-md3" color="primary" elevation="0" @click="emit('navigate', 'signup')">
            Commencer
          </v-btn>
        </template>
      </div>

      <v-app-bar-nav-icon class="hidden-lg-and-up" @click="emit('toggle-drawer')" />
    </v-container>
  </v-app-bar>

  <v-navigation-drawer v-model="drawerValue" location="right" temporary>
    <v-list>
      <v-list-item v-for="item in navItems" :key="item.text" @click="handleDrawerNav(item.target)">
        <v-list-item-title>{{ item.text }}</v-list-item-title>
      </v-list-item>

      <v-divider class="my-2" />

      <template v-if="isAuthenticated">
        <v-list-item>
          <v-list-item-title class="text-caption text-medium-emphasis">Connecté</v-list-item-title>
          <v-list-item-subtitle>{{ userDisplayName }}</v-list-item-subtitle>
        </v-list-item>
        <v-list-item @click="emit('navigate', 'dashboard')">
          <v-list-item-title class="text-primary">Tableau de bord</v-list-item-title>
        </v-list-item>
        <v-list-item @click="emit('navigate', 'logout')">
          <v-list-item-title class="text-error">Déconnexion</v-list-item-title>
        </v-list-item>
      </template>
      <template v-else>
        <v-list-item @click="emit('navigate', 'login')">
          <v-list-item-title class="text-primary">Connexion</v-list-item-title>
        </v-list-item>

        <v-list-item @click="emit('navigate', 'signup')">
          <v-list-item-title class="text-primary font-weight-bold">Commencer</v-list-item-title>
        </v-list-item>
      </template>
    </v-list>
  </v-navigation-drawer>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import { computed } from 'vue'
  import AppLogo from '@/components/branding/AppLogo.vue'

  type NavItem = { text: string, target: string }

  const props = defineProps({
    navItems: {
      type: Array as PropType<NavItem[]>,
      required: true,
    },
    isAuthenticated: {
      type: Boolean,
      required: true,
    },
    userInitials: {
      type: String,
      required: true,
    },
    userDisplayName: {
      type: String,
      required: true,
    },
    userEmail: {
      type: String,
      required: true,
    },
    drawer: {
      type: Boolean,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'update:drawer', value: boolean): void
    (event: 'toggle-drawer'): void
    (event: 'scroll', value: string): void
    (event: 'navigate', value: string): void
  }>()

  const drawerValue = computed({
    get: () => props.drawer,
    set: value => emit('update:drawer', value),
  })

  function handleDrawerNav (target: string) {
    emit('scroll', target)
    emit('update:drawer', false)
  }
</script>

<style scoped>
.logo-container {
  display: inline-flex;
  align-items: center;
}

:deep(.navbar-logo.app-logo--full .app-logo__image) {
  width: 138px;
  min-width: 138px;
}
</style>
