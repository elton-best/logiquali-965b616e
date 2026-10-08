<script lang="ts" setup>
  import { onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import { useAuthStore } from '@/stores/auth'
  import LandingPage from './LandingPage.vue'

  const router = useRouter()
  const authStore = useAuthStore()

  onMounted(() => {
    if (!authStore.isAuthenticated) {
      authStore.initAuth()
    }
    // Always allow access to landing page, even when authenticated.
  })
</script>

<template>
  <LandingPage />
</template>
