<template>
  <v-container class="d-flex align-center justify-center" fill-height>
    <v-card class="pa-8 text-center" elevation="4" max-width="600">
      <v-icon class="mb-4" color="error" size="80">mdi-lock-alert-outline</v-icon>

      <h1 class="text-h3 font-weight-bold mb-4">
        403
      </h1>

      <h2 class="text-h5 mb-4">
        Action non autorisée
      </h2>

      <p class="text-body-1 text-medium-emphasis mb-6">
        Action non autorisée. Vous n'avez pas les permissions nécessaires.
      </p>

      <div class="d-flex gap-4 justify-center">
        <v-btn
          color="primary"
          prepend-icon="mdi-home"
          @click="goHome"
        >
          Retour à l'accueil
        </v-btn>

        <v-btn
          color="primary"
          prepend-icon="mdi-logout"
          variant="outlined"
          @click="handleLogout"
        >
          Se déconnecter
        </v-btn>
      </div>

      <v-divider class="my-6" />

      <div class="text-caption text-medium-emphasis">
        <div class="mb-2">Besoin d'aide ?</div>
        <div>
          Contactez-nous à
          <a class="text-primary" href="mailto:support@BestQHSE.com">support@BestQHSE.com</a>
        </div>
      </div>
    </v-card>
  </v-container>
</template>

<script setup lang="ts">
  import { useRouter } from 'vue-router'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()

  function goHome () {
    const dashboardRoute = authStore.getDashboardRoute()
    if (dashboardRoute === '/auth/login') {
      router.push('/')
    } else {
      router.push(dashboardRoute)
    }
  }

  function handleLogout () {
    authStore.clearAuth()
    toast.success('Déconnexion réussie')
    router.push('/')
  }
</script>

<style scoped>
.gap-4 {
  gap: 1rem;
}
</style>
