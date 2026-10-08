<template>
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

<script setup lang="ts">
  import { useRouter } from 'vue-router'

  defineProps<{
    userName: string
    userEmail: string
    userInitials: string
  }>()

  const router = useRouter()

  function handleLogout () {
    localStorage.removeItem('accessToken')
    localStorage.removeItem('user')
    router.push('/auth/login')
  }
</script>

<style scoped>
.border-t {
  border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>
