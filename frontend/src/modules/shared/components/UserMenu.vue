<template>
  <v-menu offset-y>
    <template #activator="{ props: activatorProps }">
      <v-avatar class="cursor-pointer" color="primary" size="40" v-bind="activatorProps">
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
        <v-list-item prepend-icon="mdi-account" :to="profileRoute">
          Mon profil
        </v-list-item>
        <v-list-item prepend-icon="mdi-cog" :to="settingsRoute">
          Paramètres
        </v-list-item>
        <v-list-item prepend-icon="mdi-lock" @click="handleLockSession">
          Verrouiller ma session
        </v-list-item>
      </v-list>
      <v-divider />
      <v-list density="compact">
        <v-list-item class="text-error" prepend-icon="mdi-logout" @click="handleLogout">
          Déconnexion
        </v-list-item>
      </v-list>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
  import { useRoute, useRouter } from 'vue-router'
  import { useLockScreenStore } from '@/stores/lockScreen'

  const props = defineProps<{
    userName: string
    userEmail: string
    userInitials: string
    profileRoute?: string
    settingsRoute?: string
  }>()

  const router = useRouter()
  const route = useRoute()
  const lockScreenStore = useLockScreenStore()

  function handleLockSession () {
    // Utiliser l'email passé en prop
    lockScreenStore.lockSession(route.path, props.userEmail)
    router.push('/lock-screen')
  }

  function handleLogout () {
    localStorage.removeItem('accessToken')
    localStorage.removeItem('user')
    router.push('/auth/login')
  }
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>
