<template>
  <v-app-bar class="border-b app-bar" elevation="0" height="64">
    <v-container class="d-flex align-center px-4 px-md-6" fluid>
      <v-app-bar-nav-icon
        v-if="isMobile"
        class="mr-2"
        @click="$emit('toggle-drawer')"
      />

      <!-- Breadcrumbs -->
      <v-breadcrumbs
        v-if="breadcrumbs.length > 0"
        class="pa-0"
        density="compact"
        :items="breadcrumbs"
      >
        <template #divider>
          <v-icon>mdi-chevron-right</v-icon>
        </template>
      </v-breadcrumbs>

      <v-spacer />

      <!-- Site selector global (Option A) -->
      <SiteSelector v-if="showSiteSelector" class="mr-4" />

      <v-chip
        v-if="signatureMissing"
        class="mr-3"
        color="warning"
        prepend-icon="mdi-draw-pen"
        size="small"
        variant="tonal"
      >
        Signature à compléter
      </v-chip>

      <!-- Search -->
      <v-text-field
        aria-label="Rechercher"
        class="mr-4 search-field"
        clearable
        density="compact"
        hide-details
        :loading="isSearching"
        :model-value="searchQuery"
        placeholder="Rechercher..."
        prepend-inner-icon="mdi-magnify"
        single-line
        style="max-width: 300px"
        variant="outlined"
        @update:model-value="$emit('update:search-query', $event)"
      />

      <!-- Notifications -->
      <NotificationCenter class="mr-2" />

      <LanguageSwitcher class="mr-2" />

      <!-- Dark mode toggle -->
      <v-btn
        class="mr-2"
        icon
        variant="text"
        @click="$emit('toggle-dark-mode')"
      >
        <v-icon>{{
          darkMode ? "mdi-white-balance-sunny" : "mdi-moon-waning-crescent"
        }}</v-icon>
      </v-btn>

      <!-- User menu -->
      <v-menu offset-y>
        <template #activator="{ props }">
          <v-avatar
            class="cursor-pointer"
            color="primary"
            size="40"
            v-bind="props"
          >
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
              <v-list-item-title class="font-weight-bold">{{
                userName
              }}</v-list-item-title>
              <v-list-item-subtitle>{{ userEmail }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
          <v-divider />
          <v-list density="compact">
            <v-list-item
              prepend-icon="mdi-account-outline"
              to="/company/settings?tab=profil"
            >
              Mon profil
            </v-list-item>
            <v-list-item
              v-if="canAccessMenuItem(['settings.read'])"
              prepend-icon="mdi-cog-outline"
              to="/company/settings"
            >
              Paramètres
            </v-list-item>
            <v-list-item prepend-icon="mdi-lock" @click="$emit('lock-session')">
              Verrouiller ma session
            </v-list-item>
          </v-list>
          <v-divider />
          <v-list density="compact">
            <v-list-item
              class="text-error"
              prepend-icon="mdi-logout"
              @click="$emit('logout')"
            >
              Déconnexion
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>
    </v-container>
  </v-app-bar>
</template>

<script setup lang="ts">
  import { computed } from 'vue'
  import NotificationCenter from '@/components/NotificationCenter.vue'
  import LanguageSwitcher from '@/components/ui/LanguageSwitcher.vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { expandPermissionAliases } from '@/utils/permissions'
  import SiteSelector from './SiteSelector.vue'

  interface Breadcrumb {
    title: string
    disabled: boolean
    href?: string
  }

  defineProps<{
    isMobile: boolean
    breadcrumbs: Breadcrumb[]
    searchQuery: string
    isSearching: boolean
    darkMode: boolean
    userName: string
    userEmail: string
    userInitials: string
    signatureMissing?: boolean
  }>()

  const authStore = useAuthStore()
  const user = computed(() => authStore.user || {})
  function hasPermission (permissionName: string): boolean {
    const normalizedTarget = String(permissionName || '').trim().toLowerCase()
    if (!normalizedTarget) {
      return false
    }

    return getEffectivePermissionSet().has(normalizedTarget)
  }

  const showSiteSelector = computed(() => {
    if ((user.value as any)?.user_type !== 'company') {
      return false
    }

    return hasRole('admin_entreprise') || hasPermission('sites.read')
  })

  function getEffectivePermissionSet (): Set<string> {
    return getNavigationPermissionSet(user.value as any)
  }

  function hasRole (roleName: string): boolean {
    const roleNames = Array.isArray((user.value as any)?.role_names)
      ? (user.value as any).role_names
      : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = (user.value as any)?.roles
    if (!Array.isArray(roles)) {
      return false
    }
    return roles.some(
      (role: any) =>
        role?.name === roleName
        || role?.attributes?.name === roleName
        || role === roleName,
    )
  }

  function canAccessMenuItem (requiredPermissions: string[]): boolean {
    const userType = (user.value as any)?.user_type
    if (userType === 'super_admin' || hasRole('admin_entreprise')) {
      return true
    }

    const directPermissions = getEffectivePermissionSet()
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias =>
        directPermissions.has(alias),
      ),
    )
  }

  defineEmits<{
    'toggle-drawer': []
    'update:search-query': [value: string]
    'toggle-dark-mode': []
    'lock-session': []
    'logout': []
  }>()
</script>

<style scoped>
.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

.cursor-pointer {
  cursor: pointer;
}

.app-bar {
  backdrop-filter: blur(6px);
}

@media (max-width: 960px) {
  .search-field {
    max-width: 180px !important;
  }
}

@media (max-width: 600px) {
  .search-field {
    display: none;
  }
}
</style>
