<template>
  <v-app>
    <v-main class="d-flex align-center" style="min-height: 100vh; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
      <v-container>
        <v-row justify="center">
          <v-col cols="12" lg="5" md="6" xl="4">
            <EmailVerificationFailedHeader />

            <EmailVerificationFailedCard
              v-model:email="email"
              :error-message="errorMessage"
              :error-type="errorType"
              :is-navigating="isNavigating"
              :resend-loading="resendLoading"
              :resend-success="resendSuccess"
              @login="goToLogin"
              @resend="requestNewVerificationLink"
              @support="contactSupport"
            />
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import EmailVerificationFailedCard from '@/pages/auth/components/email-verification-result/EmailVerificationFailedCard.vue'
  import EmailVerificationFailedHeader from '@/pages/auth/components/email-verification-result/EmailVerificationFailedHeader.vue'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const errorType = ref(route.query.error as string || 'unknown')
  const errorMessage = ref(route.query.message as string || '')
  const isNavigating = ref(false)
  const resendLoading = ref(false)
  const resendSuccess = ref('')
  const email = ref((route.query.email as string) || localStorage.getItem('pendingVerificationEmail') || '')

  async function goToLogin () {
    isNavigating.value = true
    await router.push('/auth/login')
  }

  function contactSupport () {
    const subject = encodeURIComponent('Problème de vérification email')
    const body = encodeURIComponent(`Type d'erreur: ${errorType.value}\nMessage: ${errorMessage.value}`)
    window.location.href = `mailto:support@BestQHSE.com?subject=${subject}&body=${body}`
  }

  async function requestNewVerificationLink () {
    if (!email.value || !/.+@.+\..+/.test(email.value)) {
      toast.error('Veuillez saisir une adresse email valide.')
      return
    }

    try {
      resendLoading.value = true
      resendSuccess.value = ''
      await api.post('/auth/resend-verification/public', {
        email: email.value,
      })
      localStorage.setItem('pendingVerificationEmail', email.value)
      resendSuccess.value = 'Si un compte existe pour cette adresse, un nouveau lien vient d’être envoyé.'
      toast.success('Demande envoyée.')
    } catch (error: any) {
      toast.error(error?.response?.data?.message || 'Impossible de renvoyer le lien pour le moment.')
    } finally {
      resendLoading.value = false
    }
  }

  onMounted(() => {
    console.error('Email verification failed:', errorType.value, errorMessage.value)
  })
</script>

<style scoped>
:deep(.email-failed-loader-scope) {
  position: relative;
}

:deep(.email-failed-loader-overlay) {
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
