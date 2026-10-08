<template>
  <v-app>
    <v-main class="d-flex align-center" style="min-height: 100vh; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
      <v-container>
        <v-row justify="center">
          <v-col cols="12" lg="5" md="6" xl="4">
            <EmailVerificationSuccessHeader />

            <EmailVerificationSuccessCard
              :already-verified="alreadyVerified"
              :countdown="countdown"
              :is-navigating="isNavigating"
              @login="goToLogin"
            />

            <EmailVerificationTipCard />
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { onMounted, onUnmounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import EmailVerificationSuccessCard from '@/pages/auth/components/email-verification-result/EmailVerificationSuccessCard.vue'
  import EmailVerificationSuccessHeader from '@/pages/auth/components/email-verification-result/EmailVerificationSuccessHeader.vue'
  import EmailVerificationTipCard from '@/pages/auth/components/email-verification-result/EmailVerificationTipCard.vue'

  const route = useRoute()
  const router = useRouter()
  const countdown = ref(10)
  const alreadyVerified = ref(false)
  const isNavigating = ref(false)
  const redirectInterval = ref<ReturnType<typeof setInterval> | null>(null)

  async function goToLogin () {
    if (isNavigating.value) return
    isNavigating.value = true
    await router.push({
      path: '/auth/login',
      query: { force_login: '1', verified: '1' },
    })
  }

  onMounted(() => {
    const status = route.query.status

    if (status === 'already_verified') {
      alreadyVerified.value = true
    }

    // Auto-redirect countdown
    redirectInterval.value = setInterval(() => {
      countdown.value--
      if (countdown.value === 0) {
        if (redirectInterval.value) {
          clearInterval(redirectInterval.value)
          redirectInterval.value = null
        }
        goToLogin()
      }
    }, 1000)
  })

  onUnmounted(() => {
    if (redirectInterval.value) {
      clearInterval(redirectInterval.value)
      redirectInterval.value = null
    }
  })
</script>

<style scoped>
:deep(.email-verified-loader-scope) {
  position: relative;
}

:deep(.email-verified-loader-overlay) {
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
