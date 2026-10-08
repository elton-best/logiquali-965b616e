<template>
  <v-app>
    <v-main class="d-flex align-center" style="min-height: 100vh; background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-secondary)) 100%);">
      <v-container>
        <v-row justify="center">
          <v-col cols="12" lg="5" md="6" xl="4">
            <SignupSuccessHeader :type="type" />

            <SignupSuccessCard
              :is-navigating="isNavigating"
              :type="type"
              @dashboard="goToClientDashboard"
              @login="goToLogin"
            />
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import SignupSuccessCard from '@/pages/auth/components/signup-status/SignupSuccessCard.vue'
  import SignupSuccessHeader from '@/pages/auth/components/signup-status/SignupSuccessHeader.vue'

  const route = useRoute()
  const router = useRouter()
  const type = route.query.type as string || 'client'
  const isNavigating = ref(false)

  async function goToLogin () {
    isNavigating.value = true
    await router.push('/auth/login')
  }

  async function goToClientDashboard () {
    isNavigating.value = true
    await router.push('/clientb/dashboard')
  }
</script>

<style scoped>
:deep(.signup-success-loader-scope) {
  position: relative;
}

:deep(.signup-success-loader-overlay) {
  position: absolute;
  inset: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
  background: rgba(255, 255, 255, 0.72);
}
</style>
